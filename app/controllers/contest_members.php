<?php
	requirePHPLib('form');

	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}

	if (!Auth::check()) {
		redirectToLogin();
	}

	genMoreContestInfo($contest);

	$has_contest_permission = hasContestPermission($myUser, $contest);
	$rgroup = isset($contest['extra_config']['is_group_contest']);
	$show_ip = $has_contest_permission;

	if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
		$iHasRegistered = hasRegistered($myUser, $contest);
	
		if ($iHasRegistered) {
			$unregister_form = new UOJForm('unregister');
			$unregister_form->handle = function() {
				global $myUser, $contest;
				DB::delete("delete from contests_registrants where username = '{$myUser['username']}' and contest_id = {$contest['id']}");
				updateContestPlayerNum($contest);
			};
			$unregister_form->submit_button_config['class_str'] = 'btn btn-danger btn-xs';
			$unregister_form->submit_button_config['text'] = '取消报名';
			$unregister_form->succ_href = '/contests';
		
			$unregister_form->runAtServer();
		}
	}

	if ($has_contest_permission) {
		$pre_rating_form = new UOJForm('pre_rating');
		$pre_rating_form->handle = function() {
			global $contest, $rgroup;
			foreach (DB::selectAll("select * from contests_registrants where contest_id = {$contest['id']}") as $reg) {
				if ($rgroup) {
					$group = queryGroup($reg['groupname']);
					DB::update("update contests_registrants set user_rating = {$group['rating']} where contest_id = {$contest['id']} and username = '{$group['group_name']}'");
				} else {
					$user = queryUser($reg['username']);
					DB::update("update contests_registrants set user_rating = {$user['rating']} where contest_id = {$contest['id']} and username = '{$user['username']}'");
				}
			}
		};
		$pre_rating_form->submit_button_config['align'] = 'right';
		$pre_rating_form->submit_button_config['class_str'] = 'btn btn-warning';
		$pre_rating_form->submit_button_config['text'] = '重新计算参赛前的 rating';
		$pre_rating_form->submit_button_config['smart_confirm'] = '';
		
		$pre_rating_form->runAtServer();
	}
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - ' . UOJLocale::get('contests::contest registrants')) ?>

<h1 class="text-center"><?= $contest['name'] ?></h1>
<?php
	if ($contest['cur_progress'] == CONTEST_NOT_STARTED) {
		if ($rgroup) {
			$cnt = DB::selectCount("select count(*) from contests_registrants where contest_id = {$contest['id']} and exists (select 1 from group_members where group_members.group_name = contests_registrants.username and group_members.username = '{$myUser['username']}' and group_members.member_state != 'W')");
			echo '<div>您所在的组中有 <strong style="color: red">', $cnt, '</strong> 个报名比赛，要顺利参加比赛，请务必保证该数为 1。<a style="color: red" href="/contest/', $contest['id'], '/register">点此</a>更改报名组。</div>';
		} else {
			if ($iHasRegistered) {
				echo '<div class="pull-right">';
				$unregister_form->printHTML();
				echo '</div>';
				echo '<div><a style="color: green">已报名</a></div>';
			} else {
				echo '<div>当前尚未报名，您可以<a style="color: red" href="/contest/', $contest['id'], '/register">报名</a>。</div>';
			}
		}
		echo '<div class="top-buffer-sm"></div>';
	}

	$header_row = '<tr>';
	$header_row = '<th>#</th>';

	if ($show_ip and !$rgroup) {
		$header_row .= '<th>' . UOJLocale::get('username') . '</th>';
		$header_row .= '<th>' . UOJLocale::get('realname') . '</th>';
		$header_row .= '<th>remote_addr</th>';
		$header_row .= '<th>http_x_forwarded_for</th>';
	
		$ip_owner = array();
		foreach (DB::selectAll("select * from contests_registrants where contest_id = {$contest['id']} order by user_rating asc, username desc") as $reg) {
			$user = queryUser($reg['username']);
			$ip_owner[ $user['remote_addr'] . '@' . $user['http_x_forwarded_for'] ] = $reg['username'];
		}
	} else {
		$header_row .= '<th>' . UOJLocale::get($rgroup ? 'groupname' : 'username') . '</th>';
	}
	$header_row .= '<th>rating</th>';
	$headerrRow .= '</tr>';
	
	echoLongTable(array('*'), 'contests_registrants', "contest_id = {$contest['id']}", 'order by user_rating desc, username asc',
		$header_row,
		function($contest, $num) {
			global $myUser, $show_ip, $ip_owner, $rgroup;

			if ($rgroup) {
				$group = queryGroup($contest['username']);
				$group_link = getGroupLink($contest['username'], $contest['user_rating']);
				if (isGroupManager(Auth::user(), $group)) {
					echo '<tr class="info">';
				} elseif (isGroupMember(Auth::user(), $group)) {
					echo '<tr class="success">';
				} else {
					echo '<tr>';
				}
				echo '<td>', $num, '</td>';
				echo '<td>', $group_link, '</td>';
				echo '<td>', $contest['user_rating'], '</td>';
				echo '</tr>';
			} else {
				$user = queryUser($contest['username']);
				$user_link = getUserLink($contest['username'], $contest['user_rating']);
				if (!$show_ip) {
					echo '<tr>';
				} else {
					$user_ip = $user['remote_addr'] . '@' . $user['http_x_forwarded_for'];
					if ($user_ip !== '@' and $ip_owner[$user_ip] !== $user['username']) {
						echo '<tr class="danger">';
					} else {
						echo '<tr>';
					}
				}
				echo '<td>', $num, '</td>';
				echo '<td>', $user_link, '</td>';
				if ($show_ip) {
					echo '<td>', $user['real_name'], '</td>';
					echo '<td>', HTML::escape($user['remote_addr']), '</td>';
					echo '<td>', HTML::escape($user['http_x_forwarded_for']), '</td>';
				}
				echo '<td>', $contest['user_rating'], '</td>';
				echo '</tr>';
			}
		},
		array('page_len' => 100,
			'get_row_index' => '',
			'print_after_table' => function() {
				global $pre_rating_form;
				if (isset($pre_rating_form)) {
					$pre_rating_form->printHTML();
				}
			}
		)
	);
?>
<?php echoUOJPageFooter() ?>
