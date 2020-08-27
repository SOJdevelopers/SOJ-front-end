<?php

function hasProblemPermission($user, $problem) {
	return $user != null && (isProblemManager($user) || DB::selectFirst("select * from problems_permissions where username = '{$user['username']}' and problem_id = {$problem['id']}"));
}

function hasViewPermission($str, $user, $problem, $submission) {
	if ($str === 'ALL')
		return true;
	if ($str === 'ALL_AFTER_AC')
		return hasAC($user, $problem);
	if ($str === 'SELF')
		return $submission['submitter'] === $user['username'];
	return false;
}

function hasContestPermission($user, $contest) {
	return $user != null && (isSuperUser($user) || DB::selectFirst("select * from contests_permissions where username = '{$user['username']}' and contest_id = {$contest['id']}"));
}

function hasRegistered($user, $contest) {
	return DB::selectFirst("select * from contests_registrants where username = '{$user['username']}' and contest_id = {$contest['id']}");
}

function hasAC($user, $problem) {
	return DB::selectFirst("select * from best_ac_submissions where submitter = '{$user['username']}' and problem_id = {$problem['id']}");
}

function queryUser($username) {
	if (!validateUsername($username)) {
		return null;
	}
	return DB::selectFirst("select * from user_info where username = '$username'", MYSQL_ASSOC);
}

function queryGroup($groupname) {
	if (!validateUsername($groupname)) {
		return null;
	}
	return DB::selectFirst("select * from group_info where group_name = '$groupname'", MYSQL_ASSOC);
}

function queryProblemContent($id) {
	return DB::selectFirst("select * from problems_contents where id = $id", MYSQL_ASSOC);
}

function queryProblemBrief($id) {
	return DB::selectFirst("select * from problems where id = $id", MYSQL_ASSOC);
}

function queryProblemTags($id) {
	$result = DB::select("select tag from problems_tags where problem_id = $id order by id");
	for ($tags = array(); $row = DB::fetch($result, MYSQL_NUM); $tags[] = $row[0]);
	return $tags;
}

function queryContestProblemRank($contest, $problem) {
	if (!DB::selectFirst("select * from contests_problems where contest_id = {$contest['id']} and problem_id = {$problem['id']}")) {
		return null;
	}
	return DB::selectCount("select count(*) from contests_problems where contest_id = {$contest['id']} and problem_id <= {$problem['id']}");
}

function querySubmission($id) {
	return DB::selectFirst("select * from submissions where id = $id", MYSQL_ASSOC);
}

function queryHack($id) {
	return DB::selectFirst("select * from hacks where id = $id", MYSQL_ASSOC);
}

function queryContest($id) {
	return DB::selectFirst("select * from contests where id = $id", MYSQL_ASSOC);
}

function queryContestProblem($id) {
	return DB::selectFirst("select * from contest_problems where contest_id = $id", MYSQL_ASSOC);
}

function queryZanVal($id, $type, $user) {
	if (!$user) {
		return 0;
	}
	$esc_type = DB::escape($type);
	$row = DB::selectFirst("select val from click_zans where username = '{$user['username']}' and type = '$esc_type' and target_id = '$id'");
	if (!$row) {
		return 0;
	}
	return $row['val'];
}

function queryBlog($id) {
	return DB::selectFirst("select * from blogs where id = '$id'", MYSQL_ASSOC);
}

function queryBlogTags($id) {
	$result = DB::select("select tag from blogs_tags where blog_id = $id order by id");
	for ($tags = array(); $row = DB::fetch($result, MYSQL_NUM); $tags[] = $row[0]);
	return $tags;
}

function queryBlogComment($id) {
	return DB::selectFirst("select * from blogs_comments where id = '$id'", MYSQL_ASSOC);
}

function checkGroup($problem, $user) {
	return DB::selectFirst("select * from problems_visibility where problem_id = {$problem['id']} and exists (select 1 from group_members where group_members.group_name = problems_visibility.group_name and group_members.username = '{$user['username']}' and group_members.member_state != 'W')");
}

function isProblemVisibleToUser($problem, $user) {
	return (!$problem['is_hidden'] && checkGroup($problem, $user)) || hasProblemPermission($user, $problem);
}

function isContestProblemVisibleToUser($problem, $contest, $user) {
	if (isProblemVisibleToUser($problem, $user)) {
		return true;
	}
	if ($contest['cur_progress'] >= CONTEST_PENDING_FINAL_TEST) {
		return checkGroup($problem, $user);
	}
	if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
		return false;
	}
	return hasRegistered($user, $contest);
}

function isSubmissionFullVisibleToUser($submission, $contest, $problem, $user) {
	if (isProblemManager($user)) {
		return true;
	} elseif (!$contest) {
		return true;
	} elseif ($contest['cur_progress'] > CONTEST_IN_PROGRESS) {
		return true;
	} elseif ($submission['submitter'] === $user['username']) {
		return true;
	} elseif ($group = queryGroup($submission['submitter']) and isGroupMember($user, $group)) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}

function isHackFullVisibleToUser($hack, $contest, $problem, $user) {
	if (isProblemManager($user)) {
		return true;
	} elseif (!$contest) {
		return true;
	} elseif ($contest['cur_progress'] > CONTEST_IN_PROGRESS) {
		return true;
	} elseif ($hack['hacker'] === $user['username']) {
		return true;
	} else {
		return hasProblemPermission($user, $problem);
	}
}

function isOurSubmission($user, $submission) {
	if ($submission['submitter'] === $user['username']) return true;
	return $group = queryGroup($submission['submitter']) and isGroupMember($user, $group);
}

function deleteBlog($id) {
	if (!validateUInt($id)) {
		return;
	}
	DB::delete("delete from click_zans where type = 'B' and target_id = $id");
	DB::delete("delete from click_zans where type = 'BC' and target_id in (select id from blogs_comments where blog_id = $id)");
	DB::delete("delete from blogs where id = $id");
	DB::delete("delete from blogs_comments where blog_id = $id");
	DB::delete("delete from important_blogs where blog_id = $id");
	DB::delete("delete from blogs_tags where blog_id = $id");
}

function deleteBlogComment($id) {
	if (!validateUInt($id)) {
		return;
	}
	DB::delete("delete from click_zans where type = 'BC' and target_id in (select id from blogs_comments where reply_id = $id)");
	DB::delete("delete from click_zans where type = 'BC' and target_id = $id");
	DB::delete("delete from blogs_comments where reply_id = $id");
	DB::delete("delete from blogs_comments where id = $id");
}

function deleteSubmission($submission) {
	$content = json_decode($submission['content'], true);
	unlink(UOJContext::storagePath() . $content['file_name']);
	DB::delete("delete from submissions where id = {$submission['id']}");
	updateBestACSubmissions($submission['submitter'], $submission['problem_id']);
}

function deleteUser($user) {
	DB::delete("delete from user_info where username = '$user'");
}
