<?php
	requirePHPLib('form');

	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}

	genMoreContestInfo($contest);
	$rgroup = isset($contest['extra_config']['is_group_contest']);

	if (!hasContestPermission(Auth::user(), $contest)) {
		become403Page();
	}

	$time_form = new UOJForm('time');
	$time_form->addInput(
		'name', 'text', '比赛标题', $contest['name'],
		function($str, &$vdata) {
			$purifier = HTML::purifier();
			$esc_str = $purifier->purify($str);
			if (!$esc_str) {
				return '标题不能为空';
			}
			if (strlen($esc_str) > 100) {
				return '不能超过 100 个字节';
			}
			$vdata['name'] = $esc_str;
			return '';
		},
		null
	);
	$time_form->addInput(
		'start_time', 'text', '开始时间', $contest['start_time_str'],
		function($str, &$vdata) {
			try {
				$vdata['start_time'] = new DateTime($str);
			} catch (Exception $e) {
				return '无效时间格式';
			}
			return '';
		},
		null
	);
	$time_form->addInput(
		'last_min', 'text', '时长（单位：分钟）', $contest['last_min'],
		function($str) {
			return !validateUInt($str) ? '必须为一个整数' : '';
		},
		null
	);
	$time_form->handle = function(&$vdata) {
		global $contest;
		$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');
		$esc_name = DB::escape($vdata['name']);

		DB::update("update contests set start_time = '$start_time_str', last_min = {$_POST['last_min']}, name = '$esc_name' where id = {$contest['id']}");
	};
	
	$managers_form = newAddDelCmdForm('managers',
		function($username) {
			if (!validateUsername($username) || !queryUser($username)) {
				return "不存在名为 {$username} 的用户";
			}
			return '';
		},
		function($type, $username) {
			global $contest;
			if ($type == '+') {
				DB::insert("insert into contests_permissions (contest_id, username) values ({$contest['id']}, '$username')");
				DB::delete("delete from contests_registrants where username = '{$username}' and contest_id = {$contest['id']}");
			} else if ($type == '-') {
				DB::delete("delete from contests_permissions where contest_id = {$contest['id']} and username = '$username'");
			}
			updateContestPlayerNum($contest);
		}
	);

	if ($contest['cur_progress'] < CONTEST_PENDING_FINAL_TEST) {
		$registrants_form = newAddDelCmdForm('registrants',
			function($name) {
				global $rgroup;
				if ($rgroup) {
					return queryGroup($name) ? '' : "不存在名为 {$name} 的组";
				} else {
					return queryUser($name) ? '' : "不存在名为 {$name} 的用户";
				}
			},
			function($type, $name) {
				global $contest, $rgroup;
				if ($rgroup) {
					$thatGroup = queryGroup($name);
					if ($type == '+') {
						DB::insert("insert into contests_registrants (username, user_rating, contest_id, has_participated) values ('{$thatGroup['group_name']}', {$thatGroup['rating']}, {$contest['id']}, 0)");
					} else if ($type == '-') {
						DB::delete("delete from contests_registrants where username = '{$thatGroup['group_name']}' and contest_id = {$contest['id']}");
					}
				} else {
					$thatUser = queryUser($name);
					if (!hasContestPermission($thatUser, $contest)) {
						if ($type == '+') {
							DB::insert("insert into contests_registrants (username, user_rating, contest_id, has_participated) values ('{$thatUser['username']}', {$thatUser['rating']}, {$contest['id']}, 0)");
						} else if ($type == '-') {
							DB::delete("delete from contests_registrants where username = '{$thatUser['username']}' and contest_id = {$contest['id']}");
						}
					}
				}
				updateContestPlayerNum($contest);
			}
		);
	}

	$problems_form = newAddDelCmdForm('problems',
		function($cmd) {
			if (!preg_match('/^(\d+)\s*(\[\S+\])?$/', $cmd, $matches)) {
				return '无效题号';
			}
			$problem_id = $matches[1];
			if (!validateUInt($problem_id) || !($problem = queryProblemBrief($problem_id))) {
				return "不存在题号为 {$problem_id} 的题";
			}
			if (!hasProblemPermission(Auth::user(), $problem)) {
				return "无权添加题号为 {$problem_id} 的题";
			}
			return '';
		},
		function($type, $cmd) {
			global $contest;
			
			if (!preg_match('/^(\d+)\s*(\[\S+\])?$/', $cmd, $matches)) {
				return '无效题号';
			}
			
			$problem_id = $matches[1];
			
			if ($type == '+') {
				DB::insert("insert into contests_problems (contest_id, problem_id) values ({$contest['id']}, '{$problem_id}')");
			} else if ($type == '-') {
				DB::delete("delete from contests_problems where contest_id = {$contest['id']} and problem_id = '{$problem_id}'");
			}
			
			if (isset($matches[2])) {
				switch ($matches[2]) {
					case '[sample]':
						unset($contest['extra_config']["problem_{$problem_id}"]);
						break;
					case '[full]':
						$contest['extra_config']["problem_{$problem_id}"] = 'full';
						break;
					case '[no-details]':
						$contest['extra_config']["problem_{$problem_id}"] = 'no-details';
						break;
				}
				$esc_extra_config = json_encode($contest['extra_config']);
				$esc_extra_config = DB::escape($esc_extra_config);
				DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
			}
		}
	);

	$raw_form = new UOJForm('raw');
	$esc_config = HTML::escape(json_encode(json_decode($contest['extra_config_str']), JSON_PRETTY_PRINT));
	$raw_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">比赛配置</label>
	<div class="col-sm-9">
		<div class="form-control-static"><pre>
$esc_config
</pre>
		</div>
	</div>
</div>
EOD
	);
	$raw_form->addVInput('extra_config', 'text', '比赛配置', $contest['extra_config_str'],
		function ($extra_config, &$vdata) {
			$extra_config = json_decode($extra_config, true);
			if ($extra_config === null) {
				return '不是合法的 JSON';
			}
			$vdata['extra_config'] = json_encode($extra_config);
		},
		null);
	$raw_form->handle = function(&$vdata) {
		global $contest;
		$esc_extra_config = DB::escape($vdata['extra_config']);
		DB::update("update contests set extra_config = '$esc_extra_config' where id = {$contest['id']}");
	};

	$time_form->runAtServer();
	$managers_form->runAtServer();
	if (isset($registrants_form)) $registrants_form->runAtServer();
	$problems_form->runAtServer();
	$raw_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($contest['name']) . ' - 比赛管理') ?>
<h1 class="page-header" align="center"><?=$contest['name']?> 管理</h1>
<ul class="nav nav-tabs" role="tablist">
	<li class="active"><a href="#tab-time" role="tab" data-toggle="tab">比赛时间</a></li>
	<li><a href="#tab-managers" role="tab" data-toggle="tab">管理者</a></li>
<?php if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) { ?>
	<li><a href="#tab-registrants" role="tab" data-toggle="tab">参赛者</a></li>
<?php } ?>
	<li><a href="#tab-problems" role="tab" data-toggle="tab">试题</a></li>
	<li><a href="#tab-raw" role="tab" data-toggle="tab">原始配置</a></li>
	<li><a href="/contest/<?= $contest['id'] ?>" role="tab">返回</a></li>
</ul>
<div class="tab-content top-buffer-sm">
	<div class="tab-pane active" id="tab-time">
		<?php $time_form->printHTML(); ?>
	</div>

	<div class="tab-pane" id="tab-managers">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>用户名</th>
				</tr>
			</thead>
			<tbody>
<?php
	$result = DB::select("select username from contests_permissions where contest_id = {$contest['id']}");
	for ($row_id = 1; $row = DB::fetch($result); ++$row_id)
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
?>
			</tbody>
		</table>
		<p class="text-center">命令格式：命令一行一个，+mike 表示把 mike 加入管理者，-mike 表示把 mike 从管理者中移除</p>
		<?php $managers_form->printHTML(); ?>
	</div>
<?php if (isset($registrants_form)) { ?>
	<div class="tab-pane" id="tab-registrants">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>用户名</th>
				</tr>
			</thead>
			<tbody>
<?php
	$result = DB::select("select username from contests_registrants where contest_id = {$contest['id']} order by user_rating desc");
	for ($row_id = 1; $row = DB::fetch($result); ++$row_id)
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserOrGroupLink($row['username']), '</td>', '</tr>';
?>
			</tbody>
		</table>
		<p class="text-center">命令格式：命令一行一个，+mike 表示把 mike 加入参赛者，-mike 表示把 mike 从参赛者中移除</p>
		<?php $registrants_form->printHTML(); ?>
	</div>
<?php } ?>

	<div class="tab-pane" id="tab-problems">
		<table class="table table-hover">
			<thead>
				<tr>
					<th>#</th>
					<th>试题名</th>
				</tr>
			</thead>
			<tbody>
<?php
	$result = DB::select("select problem_id from contests_problems where contest_id = {$contest['id']} order by problem_id asc");
	while ($row = DB::fetch($result)) {
		$problem = queryProblemBrief($row['problem_id']);
		$problem_config_str = isset($contest['extra_config']["problem_{$problem['id']}"]) ? $contest['extra_config']["problem_{$problem['id']}"] : 'sample';
		echo '<tr>', '<td>', $problem['id'], '</td>', '<td>', getProblemLink($problem), ' ', "[$problem_config_str]", '</td>', '</tr>';
	}
?>
			</tbody>
		</table>
		<p class="text-center">命令格式：命令一行一个，+233 表示把题号为 233 的试题加入比赛，-233 表示把题号为 233 的试题从比赛中移除</p>
		<p class="text-center">注意：在题目 id 后加入<code>[sample]</code>、<code>[full]</code>或<code>[no-details]</code>分别代表<strong>只测样例</strong>，<strong>测试全部数据且显示详细信息</strong>和<strong>测试全部数据但不显示详细信息</strong>。</p>
		<?php $problems_form->printHTML(); ?>
	</div>
	<div class="tab-pane" id="tab-raw">
		<?php $raw_form->printHTML(); ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
