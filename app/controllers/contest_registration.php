<?php
	requirePHPLib('form');

	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}

	if (!Auth::check()) {
		redirectToLogin();
	}

	genMoreContestInfo($contest);
	$rgroup = isset($contest['extra_config']['is_group_contest']);
	
	if ($rgroup) {
		if (hasContestPermission(Auth::user(), $contest) || $contest['cur_progress'] > CONTEST_NOT_STARTED) {
			redirectTo('/contests');
		}

		$register_form = newAddDelCmdForm('members',
			function($groupname, $type) {
				if (!($g = queryGroup($groupname))) return "不存在名为 {$groupname} 的组";
				return isGroupManager(Auth::user(), $g) ? '' : "您不是组 {$g['group_name']} 的管理员，无法对其进行操作";
			},
			function($type, $groupname) {
				global $contest;
				$g = queryGroup($groupname);
				if ($type === '+') {
					DB::insert("insert into contests_registrants (username, user_rating, contest_id, has_participated) values ('{$g['group_name']}', {$g['rating']}, {$contest['id']}, 0)");
				} elseif ($type === '-') {
					DB::delete("delete from contests_registrants where username = '{$g['group_name']}' and contest_id = {$contest['id']}");
				}
				updateContestPlayerNum($contest);
			}
		);

		$register_form->runAtServer();
	} else {
		if (hasContestPermission(Auth::user(), $contest) || $contest['cur_progress'] > CONTEST_IN_PROGRESS || hasRegistered(Auth::user(), $contest)) {
			redirectTo('/contests');
		}
	
		$register_form = new UOJForm('register');
		$register_form->handle = function() {
			global $myUser, $contest;
			DB::insert("insert into contests_registrants (username, user_rating, contest_id, has_participated) values ('{$myUser['username']}', {$myUser['rating']}, {$contest['id']}, 0)");
			updateContestPlayerNum($contest);
		};
		$register_form->submit_button_config['class_str'] = 'btn btn-primary';
		$register_form->submit_button_config['text'] = '报名比赛';
		$register_form->succ_href = '/contests';
		
		$register_form->runAtServer();
	}
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - 报名') ?>
<h2 class="page-header">比赛规则</h2>
<ul>
<?php if ($contest['extra_config']['standings_version'] === 5) { ?>
	<li>比赛报名后不算正式参赛，报名后进了比赛页面也不算参赛，<strong>看了题目才算正式参赛</strong>。如果未正式参赛则不算 rating。</li>
	<li>比赛中途可以提交，提交结果只有 Accepted 和 Unaccepted，若同一题有多次提交，按<strong>第一次 AC 的提交</strong>计算，如果没有 AC，按<strong>最后一次不是 Compile Error 的提交</strong>计算。(其实 SOJ 会自动无视你所有 Compile Error 的提交当作没看见)</li>
	<li>比赛中途提交后，可以看到<strong>最终</strong>的结果和实时榜单。</li>
	<li>每道题的罚时定义为完成该题所花的时间，加上第一次 AC 前的非 CE 提交次数的 1200 倍 (单位：秒)，若该题未 AC 则不计入罚时。</li>
	<li>比赛排名按 AC 题数为第一关键字，所有题目的罚时之和为第二关键字进行。</li>
<?php } elseif ($contest['extra_config']['standings_version'] === 4) { ?>
	<li>比赛报名后不算正式参赛，报名后进了比赛页面也不算参赛，<strong>看了题目才算正式参赛</strong>。如果未正式参赛则不算 rating。</li>
	<li>比赛中途可以提交，若同一题有多次提交按<strong>最后一次不是 Compile Error 的提交</strong>算成绩。(其实 SOJ 会自动无视你所有 Compile Error 的提交当作没看见)</li>
	<li>比赛中途提交后，可以看到<strong>测样例</strong>的结果。(若为提交答案题则对于每个测试点，该测试点有分则该测试点为满分)</li>
	<li>比赛结束后会进行最终测试，最终测试后的排名为最终排名。</li>
	<li>比赛排名按分数为第一关键字，完成题目的总时间为第二关键字。完成题目的总时间等于完成每道题所花时间的最大值 (无视掉爆零的题目)。</li>
<?php } else { ?>
	<li>比赛报名后不算正式参赛，报名后进了比赛页面也不算参赛，<strong>看了题目才算正式参赛</strong>。如果未正式参赛则不算 rating。</li>
	<li>比赛中途可以提交，若同一题有多次提交按<strong>最后一次不是 Compile Error 的提交</strong>算成绩。(其实 SOJ 会自动无视你所有 Compile Error 的提交当作没看见)</li>
	<li>比赛中途提交后，可以看到<strong>测样例</strong>的结果。(若为提交答案题则对于每个测试点，该测试点有分则该测试点为满分)</li>
	<li>比赛结束后会进行最终测试，最终测试后的排名为最终排名。</li>
	<li>比赛排名按分数为第一关键字，完成题目的总时间为第二关键字。完成题目的总时间等于完成每道题所花时间之和 (无视掉爆零的题目)。</li>
<?php } ?>
<?php if ($rgroup) { ?>
	<li style="color: red">本次比赛以组为单位报名。对于以组为单位参加的比赛，请确保您加入的所有组中<strong>恰有一个</strong>报名比赛，且该组在比赛结束前<strong>无法加入/删除</strong>成员。</li>
<?php } ?>
<?php
	$limit = queryOnlymyselfLimit($contest);
	if ($limit === SUBMISSION_NONE_LIMIT) {
?>
	<li style="color: blue">本次比赛中，你只能看到自己的分数，不能看到其他选手的提交记录以及实时的排行榜。其他选手的提交记录会在<strong>等待评测</strong>时公布。</li>
<?php
	} elseif ($limit === SUBMISSION_STATUS_LIMIT) {
?>
	<li style="color: fuchsia">本次比赛中，你能看到所有人的分数及实时的排行榜，但是没有代码长度等详细信息。你可以依据他人分数决定比赛策略，但是无法得到代码方面的提示。</li>
<?php
	} else {
?>
	<li>请遵守比赛规则，一位选手在一场比赛内不得报名多个账号，选手之间不能交流或者抄袭代码，如果被检测到将以 0 分处理或者封禁。</li>
<?php } ?>
</ul>
<?php
	if ($rgroup) {
		echo '<h3>' . UOJLocale::get('groups which you belong to') . '</h3>';
		$header = '<tr>';
		$header .= '<th>' . UOJLocale::get('groupname') . '</th>';
		$header .= '<th>' . UOJLocale::get('your member type') . '</th>';
		$header .= '<th>' . UOJLocale::get('contests::is registered') . '</th>';
		$header .= '</tr>';
		$cond = "username = '{$myUser['username']}' and member_state != 'W'";

		echoLongTable(array('group_name', 'member_state'), 'group_members', $cond, 'order by group_name', $header, function($row) {
			global $contest;
			if ($row['member_state'] === 'A') {
				echo '<tr class="info">';
			} else {
				echo '<tr>';
			}
			echo '<td>', getGroupLink($row['group_name']), '</td>';
			echo '<td>', UOJLocale::get('ms ' . $row['member_state']), '</td>';
			$ir = DB::selectFirst("select * from contests_registrants where username = '{$row['group_name']}' and contest_id = {$contest['id']}");
			echo '<td>', $ir ? 'Yes' : 'No', '</td>';
			echo '</tr>';
		}, array('page_len' => 100));
?>
		<p class="text-center">命令格式：命令一行一个，+zhjc 表示把组 zhjc 加入参赛组，-zhjc 表示把组 zhjc 移出参赛组。<strong style="color: red">注意：你只能操作你所管理的组！</strong></p>
<?php
	}
	$register_form->printHTML();
?>
<?php echoUOJPageFooter() ?>
