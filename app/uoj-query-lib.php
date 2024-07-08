<?php
define('SUBMISSION_NONE_LIMIT', 0);
define('SUBMISSION_STATUS_LIMIT', 1);
define('SUBMISSION_CODE_LIMIT', 2);
define('SUBMISSION_ALL_LIMIT', SUBMISSION_STATUS_LIMIT | SUBMISSION_CODE_LIMIT);

function hasBeenInProblemPermissions($user, $problem) {
	return $user != null && DB::selectFirst("select * from problems_permissions where username = '{$user['username']}' and problem_id = {$problem['id']}");
}

function hasProblemPermission($user, $problem) {
	return $user != null && (isProblemManager($user) || hasBeenInProblemPermissions($user, $problem));
}

// 0 : not myself, not AC; 1 : myself, not AC; 2 : not myself, AC; 3 : myself, AC
function hasViewPermission($type, $user, $problem, $submission) {
	if ($type === 'ALL') $type = 15;
	else if ($type === 'ALL_AFTER_AC') $type = 12;
	else if ($type === 'SELF') $type = 10;
	else if (!(is_int($type) and (0 <= $type) and ($type <= 15))) $type = 0;
	if ($type === 0) return false;
	if ($type === 5) return $submission['submitter'] !== $user['username'];
	if ($type === 10) return $submission['submitter'] === $user['username'];
	if ($type === 15) return true;
	return (bool)($type >> ((int)($submission['submitter'] === $user['username']) | (hasAC($user, $problem) ? 2 : 0)) & 1);
}

function hasContestPermission($user, $contest) {
	return $user != null && (isSuperUser($user) || DB::selectFirst("select * from contests_permissions where username = '{$user['username']}' and contest_id = {$contest['id']}"));
}

function hasViewJudgerInfoPermission($user) {
	return $user != null && (isSuperUser($user));
}

function hasRegistered($user, $contest) {
	return DB::selectFirst("select * from contests_registrants where username = '{$user['username']}' and contest_id = {$contest['id']}");
}

function hasLeastOneRegistered($user, $contest) {
	$groups = DB::select("select 1 from contests_registrants where contest_id = {$contest['id']} and exists (select 1 from group_members where group_members.group_name = contests_registrants.username and group_members.username = '{$user['username']}' and group_members.member_state != 'W')");
	return DB::fetch($groups);
}

function hasOverRegistered($user, $contest) {
	$groups = DB::select("select 1 from contests_registrants where contest_id = {$contest['id']} and exists (select 1 from group_members where group_members.group_name = contests_registrants.username and group_members.username = '{$user['username']}' and group_members.member_state != 'W')");
	return DB::fetch($groups) and DB::fetch($groups);
}

function hasAC($user, $problem) {
	return DB::selectFirst("select * from best_ac_submissions where submitter = '{$user['username']}' and problem_id = {$problem['id']}");
}

function queryUser($username) {
	if (!validateUsername($username)) {
		return null;
	}
	$res = DB::selectFirst("select * from user_info where username = '$username'", MYSQLI_ASSOC);
	if ($res) {
		$res['extra_config'] = json_decode($res['extra_config'], true);
	}
	return $res;
}

function queryUserMotto($username) {
	if (!validateUsername($username)) {
		return null;
	}
	$res = DB::selectFirst("select extra_config from user_info where username = '$username'", MYSQLI_ASSOC);
	if ($res) {
		return json_decode($res['extra_config'], true)['motto'];
	}
	return null;
}

function queryGroup($groupname) {
	if (!validateUsername($groupname)) {
		return null;
	}
	return DB::selectFirst("select * from group_info where group_name = '$groupname'", MYSQLI_ASSOC);
}

function queryProblemContent($id) {
	return DB::selectFirst("select * from problems_contents where id = $id", MYSQLI_ASSOC);
}

function queryProblemBrief($id) {
	return DB::selectFirst("select * from problems where id = $id", MYSQLI_ASSOC);
}

function queryProblemTags($id) {
	$result = DB::select("select tag from problems_tags where problem_id = $id order by id");
	for ($tags = array(); $row = DB::fetch($result, MYSQLI_NUM); $tags[] = $row[0]);
	return $tags;
}

function queryContestProblemRank($contest, $problem) {
	if (!DB::selectFirst("select * from contests_problems where contest_id = {$contest['id']} and problem_id = {$problem['id']}")) {
		return null;
	}
	return DB::selectCount("select count(*) from contests_problems where contest_id = {$contest['id']} and problem_id <= {$problem['id']}");
}

function querySubmission($id) {
	return DB::selectFirst("select * from submissions where id = $id", MYSQLI_ASSOC);
}

function queryJudgement($id) {
	return DB::selectFirst("select * from submissions_history where id = $id", MYSQLI_ASSOC);
}

function queryHack($id) {
	return DB::selectFirst("select * from hacks where id = $id", MYSQLI_ASSOC);
}

function queryContest($id) {
	return DB::selectFirst("select * from contests where id = $id", MYSQLI_ASSOC);
}

function queryContestProblem($id) {
	return DB::selectFirst("select * from contest_problems where contest_id = $id", MYSQLI_ASSOC);
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
	return DB::selectFirst("select * from blogs where id = '$id'", MYSQLI_ASSOC);
}

function queryBlogTags($id) {
	$result = DB::select("select tag from blogs_tags where blog_id = $id order by id");
	for ($tags = array(); $row = DB::fetch($result, MYSQLI_NUM); $tags[] = $row[0]);
	return $tags;
}

function queryBlogComment($id) {
	return DB::selectFirst("select * from blogs_comments where id = '$id'", MYSQLI_ASSOC);
}

function checkGroup($user, $problem) {
	return isProblemManager($user) or DB::selectFirst("select * from problems_visibility where problem_id = {$problem['id']} and exists (select 1 from group_members where group_members.group_name = problems_visibility.group_name and group_members.username = '{$user['username']}' and group_members.member_state != 'W')");
}

function checkContestGroup($user, $contest) {
	return isSuperUser($user) or DB::selectFirst("select * from contests_visibility where contest_id = {$contest['id']} and exists (select 1 from group_members where group_members.group_name = contests_visibility.group_name and group_members.username = '{$user['username']}' and group_members.member_state != 'W')");
}

function checkBlogGroup($user, $blog) {
	return isSuperUser($user) or DB::selectFirst("select * from blogs_visibility where blog_id = {$blog['id']} and exists (select 1 from group_members where group_members.group_name = blogs_visibility.group_name and group_members.username = '{$user['username']}' and group_members.member_state != 'W')");
}

function isOurSubmission($user, $submission) {
	if ($submission['submitter'] === $user['username']) return true;
	return $group = queryGroup($submission['submitter']) and isGroupMember($user, $group);
}

function isProblemVisible($user, $problem, $contest = null) {
	if (hasProblemPermission($user, $problem)) {
		return true;
	} elseif (!checkGroup($user, $problem)) {
		return false;
	} elseif (!$problem['is_hidden']) {
		return true;
	} elseif (!$contest) {
		return false;
	} elseif ($contest['cur_progress'] > CONTEST_IN_PROGRESS and checkContestGroup($user, $contest)) {
		return true;
	} elseif (hasContestPermission($user, $contest)) {
		return true;
	} else {
		return $contest['cur_progress'] === CONTEST_IN_PROGRESS and hasRegistered($user, $contest);
	}
}

function queryOnlymyselfLimit($contest) {
	if (!isset($contest['extra_config']['only_myself'])) {
		return SUBMISSION_ALL_LIMIT;
	}
	$limit = $contest['extra_config']['only_myself'];
	if ($limit === true) {
		return SUBMISSION_NONE_LIMIT;
	} else if (is_string($limit)) {
		$limit = strtolower($limit);
		if ($limit === 'full') return SUBMISSION_NONE_LIMIT;
		if ($limit === 'partial') return SUBMISSION_STATUS_LIMIT;
		return SUBMISSION_ALL_LIMIT;
	} else {
		return SUBMISSION_ALL_LIMIT;
	}
}

function querySubmissionDetailPermission($user, $submission) {
	// For some reason, this function always returns non-zero value.
	if (isProblemVisible($user, queryProblemBrief($submission['problem_id']))) {
		return SUBMISSION_ALL_LIMIT;
	} else if ($submission['contest_id']) {
		$contest = queryContest($submission['contest_id']);
		genMoreContestInfo($contest);
		if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
			if (hasContestPermission($user, $contest)) {
				return SUBMISSION_ALL_LIMIT;
			} else if (isOurSubmission($user, $submission) && !hasOverRegistered($user, $contest)) {
				return SUBMISSION_ALL_LIMIT;
			} else {
				return queryOnlymyselfLimit($contest);
			}
		} else {
			return SUBMISSION_ALL_LIMIT;
		}
	}
	return SUBMISSION_NONE_LIMIT;
}

function queryRegisteredUser($user, $contest) {
	if (!hasRegistered($user, $contest)) {
		becomeMsgPage('<h1>比赛正在进行中</h1><p>很遗憾，您尚未报名。如果比赛尚未结束，你可以<a href="/contest/' . $contest['id'] . '/register">报名</a>。</p>');
	}
	return $user;
}

function queryRegisteredGroup($user, $contest, $silent = false) {
	$groups = DB::select("select username from contests_registrants where contest_id = {$contest['id']} and exists (select 1 from group_members where group_members.group_name = contests_registrants.username and group_members.username = '{$user['username']}' and group_members.member_state != 'W')");
	$group = DB::fetch($groups);
	if (!$group) {
		if ($silent) return false;
		becomeMsgPage('<h1>比赛正在进行中</h1><p>很遗憾，您所在的所有组均未报名。如果比赛尚未结束，你可以<a href="/contest/' . $contest['id'] . '/register">报名</a>。</p>');
	}
	if (DB::fetch($groups)) {
		if ($silent) return false;
		becomeMsgPage('<h1>比赛正在进行中</h1><p>很遗憾，您所在的组中有多于一个报名比赛，已违反比赛规则。比赛结束后再来看吧～</p>');
	}
	return queryGroup($group['username']);
}

function queryAuditLog($config) {
	$cond = '1';
	if (isset($config['cond'])) {
		$cond = $config['cond'];
	}
	$id_in_scope_name = 'id_in_scope';
	if (isset($config['id_in_scope_name'])) {
		$id_in_scope_name = $config['id_in_scope_name'];
	}
	$hiss = DB::select("select id, type, id_in_scope as {$id_in_scope_name}, time, actor, actor_remote_addr, actor_http_x_forwarded_for, reason, details from audit_log where {$cond}");
	$audit_log = array();
	while ($his = DB::fetch($hiss)) {
		$his['details'] = json_decode($his['details'], true);
		if (!$his['actor_http_x_forwarded_for'])
			$his['actor_http_x_forwarded_for'] = null;
		$audit_log[] = $his;
	}
	return $audit_log;
}

function sortAuditLogByTime(&$audit_log) {
	usort($audit_log, function($a, $b) {
		if (isset($a['id']) and isset($b['id'])) {
			return $b['id'] - $a['id'];
		}
		$time_a = new DateTime($a['time']);
		$time_b = new DateTime($b['time']);
		if ($time_a > $time_b)
			return -1;
		if ($time_b > $time_a)
			return 1;
		return 0;
	});
}

function getSubmissionJudgementAuditLog($submission_id) {
	$hiss = DB::select("select judge_time, id as judgement_id, judger_name, status, result_error, score, used_time, used_memory from submissions_history where submission_id = {$submission_id} order by judge_time desc");
	$audit_log = array();
	while ($his = DB::fetch($hiss)) {
		$audit_log[] = array(
			'time' => $his['judge_time'],
			'type' => 'judgement, auto',
			'submission_id' => $submission_id,
			'details' => $his
		);
	}
	return $audit_log;
}

function getMatchCondition($config, $conjunction = 'and') {
	if (!is_array($config))
		return getMatchCondition(array($config), $conjunction);
	$cond = array();
	foreach ($config as $conf) {
		switch ($conf['type']) {
			case 'combine_by_or':
				$cond[] = '(' . getMatchCondition($conf['word'], 'or') . ')';
				break;
			case 'combine_by_and':
				$cond[] = '(' . getMatchCondition($conf['word'], 'and') . ')';
				break;
			case '=':
				$cond[] = "type = '{$conf['word']}'";
				break;
			case 'like':
				$cond[] = "type like '{$conf['word']}'";
				break;
			case 'regexp':
				$cond[] = "type regexp '{$conf['word']}'";
				break;
		}
	}
	if ($cond)
		return join(' ' . $conjunction . ' ', $cond);
	return '1';
}

function getProblemAuditLog($config = array()) {
	$cond = array();
	$cond[] = "scope = 'problems'";
	if (isset($config['type']))
		$cond[] = getMatchCondition($config['type']);
	if (isset($config['problem_id']))
		$cond[] = "id_in_scope = {$config['problem_id']}";
	if (isset($config['start_time']))
		$cond[] = "time >= '{$config['start_time']}'";
	if ($cond)
		$cond = join(' and ', $cond);
	else
		$cond = '1';
	return queryAuditLog(array('cond' => $cond, 'id_in_scope_name' => 'problem_id'));
}

function getProblemDataChangesAuditLog($config = array()) {
	if (!isset($config['type']))
		$config['type'] = array();
	$config['type'][] = array('type' => 'combine_by_or', 'word' => array(
		array('type' => 'like', 'word' => 'clear data%'),
		array('type' => 'like', 'word' => 'data preparing%'),//'data preparing failed'
		array('type' => 'like', 'word' => 'add extra_test%')
	));
	return getProblemAuditLog($config);
}

function getProblemRejudgeAuditLog($config = array()) {
	if (!isset($config['type']))
		$config['type'] = array();
	$config['type'][] = array('type' => 'like', 'word' => 'rejudge%');
	return getProblemAuditLog($config);
}

function getProblemHackableStatusAuditLog($config = array()) {
	if (!isset($config['type']))
		$config['type'] = array();
	$config['type'][] = array('type' => 'like', 'word' => 'flip hackable-status%');
	return getProblemAuditLog($config);
}

function getSubmissionRejudgeAuditLog($submission) {
	$problem_log = getProblemRejudgeAuditLog(array('problem_id' => $submission['problem_id'], 'start_time' => $submission['submit_time']));
	$audit_log = queryAuditLog(array('cond' => "scope = 'submissions' and type = 'rejudge' and id_in_scope = {$submission['id']}", 'id_in_scope_name' => 'submission_id'));
	foreach ($problem_log as $log_now) {
		$log_now['submission_id'] = $submission['id'];
		$log_now['details']['problem_id'] = $log_now['problem_id'];
		$log_now['problem_id'] = null;
		$audit_log[] = $log_now;
	}
	sortAuditLogByTime($audit_log);
	return $audit_log;
}

function getHacksAuditLog($config = array()) {
	$cond = array();
	if (isset($config['problem_id']))
		$cond[] = "problem_id = {$config['problem_id']}";
	if (isset($config['submission_id']))
		$cond[] = "submission_id = {$config['submission_id']}";
	if ($cond)
		$cond = join(' and ', $cond);
	else
		$cond = '1';
	$hiss = DB::select("select id as hack_id, problem_id, contest_id, submission_id, hacker, owner, input, input_type, submit_time, judge_time, judger_name, success from hacks where {$cond}");
	$audit_log = array();
	while ($his = DB::fetch($hiss)) {
		$audit_log[] = array(
			'time' => $his['submit_time'],
			'type' => 'hack submit',
			'hack_id' => $his['hack_id'],
			'actor' => $his['hacker'],
			'details' => $his
		);
		$audit_log[] = array(
			'time' => $his['judge_time'],
			'type' => 'hack judgement, auto',
			'hack_id' => $his['hack_id'],
			'details' => $his
		);
	}
	sortAuditLogByTime($audit_log);
	return $audit_log;
}

function getSubmissionHacksAuditLog($submission) {
	$audit_log = getHacksAuditLog(array('submission_id' => $submission['id']));
	$problem_hack_status_log = getProblemHackableStatusAuditLog(array('problem_id' => $submission['problem_id'], 'start_time' => $submission['submit_time']));
	foreach ($problem_hack_status_log as $log_now) {
		$log_now['submission_id'] = $submission['id'];
		$log_now['details']['problem_id'] = $log_now['problem_id'];
		$log_now['problem_id'] = null;
		$audit_log[] = $log_now;
	}
	sortAuditLogByTime($audit_log);
	return $audit_log;
}

function getSubmissionHistoryAuditLog($submission) {
	$audit_log = array_merge(array_merge(getSubmissionJudgementAuditLog($submission['id']), getSubmissionRejudgeAuditLog($submission)), getSubmissionHacksAuditLog($submission));
	sortAuditLogByTime($audit_log);
	$audit_log[] = array(
		'time' => $submission['submit_time'],
		'type' => 'submit',
		'actor' => $submission['submitter'],
		'submission_id' => $submission['id']
	);
	return $audit_log;
}

function getSubmissionAuditLog($submission, $time_now) {
	$audit_log = array();
	$audit_log[] = array(
		'time' => $time_now,
		'type' => 'current_submission_status',
		'submission_id' => $submission['id']
	);
	$audit_log = array_merge($audit_log, getSubmissionHistoryAuditLog($submission));
	$audit_log = array_merge($audit_log, getProblemDataChangesAuditLog(array('problem_id' => $submission['problem_id'], 'start_time' => $submission['submit_time'])));
	sortAuditLogByTime($audit_log);
	return $audit_log;
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

function deleteBlogComment($id, $blog_id) {
	if (!validateUInt($id)) {
		return;
	}
	DB::delete("delete from click_zans where type = 'BC' and target_id in (select id from blogs_comments where reply_id = $id)");
	DB::delete("delete from click_zans where type = 'BC' and target_id = $id");
	DB::delete("delete from blogs_comments where reply_id = $id");
	DB::delete("delete from blogs_comments where id = $id");
	$r = DB::selectFirst("select id, post_time, poster from blogs_comments where blog_id = {$blog_id} order by id desc limit 1");
	if ($r) {
		DB::update("update blogs set latest_comment = '{$r['post_time']}', latest_commenter = '{$r['poster']}' where id = {$blog_id}");
	} else {
		DB::update("update blogs set latest_comment = post_time, latest_commenter = null where id = {$blog_id}");
	}
}

function deleteSubmission($submission, $log_config = array()) {
	insertAuditLog('submissions','remove',$submission['id'],isset($log_config['reason'])?$log_config['reason']:'','',$log_config);
	DB::insert("insert into removed_submissions select * from submissions where id = {$submission['id']}");
	DB::delete("delete from submissions where id = {$submission['id']}");
	updateBestACSubmissions($submission['submitter'], $submission['problem_id']);
}

function deleteUser($user) {
	DB::delete("delete from user_info where username = '$user'");
}
