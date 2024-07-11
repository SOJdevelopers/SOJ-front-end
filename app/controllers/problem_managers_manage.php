<?php
	requirePHPLib('form');
	requirePHPLib('data');

	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}

	if (!hasProblemPermission(Auth::user(), $problem)) {
		become403Page();
	}

	$managers_form = newAddDelCmdForm('managers',
		function($username) {
			if (!queryUser($username)) {
				return "不存在名为 {$username} 的用户";
			}
			return '';
		},
		function($type, $username) {
			global $problem;
			if ($type === '+') {
				addProblemPermission($problem['id'], $username);
			} else if ($type === '-') {
				deleteProblemPermission($problem['id'], $username);
			}
		}
	);

	$managers_form->runAtServer();

	$visibility_form = newAddDelCmdForm('visibility',
		function($groupname) {
			if (!queryGroup($groupname)) {
				return "不存在名为 {$groupname} 的组";
			}
			return '';
		},
		function($type, $groupname) {
			global $problem;
			if ($type === '+') {
				addProblemViewPermission($problem['id'], $groupname);
			} else if ($type === '-') {
				deleteProblemViewPermission($problem['id'], $groupname);
			}
		}
	);

	$visibility_form->runAtServer();
?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 管理者 - 题目管理') ?>
<h1 align="center">#<?=$problem['id']?> : <?=$problem['title']?> 管理</h1>
<ul class="nav nav-tabs" role="tablist">
	<li><a href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">编辑</a></li>
	<li class="active"><a href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">管理者</a></li>
	<li><a href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">数据</a></li>
	<li><a href="/problem/<?=$problem['id']?>" role="tab">返回</a></li>
</ul>

<h3>管理者</h3>
<table class="table table-hover">
	<thead>
		<tr>
			<th>#</th>
			<th>用户名</th>
		</tr>
	</thead>
	<tbody>
<?php
	$result = DB::select("select username from problems_permissions where problem_id = {$problem['id']}");
	for ($row_id = 1; $row = DB::fetch($result); ++$row_id)
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getUserLink($row['username']), '</td>', '</tr>';
?>
	</tbody>
</table>
<p class="text-center">命令格式：命令一行一个，+mike 表示把 mike 加入管理者，-mike 表示把 mike 从管理者中移除</p>
<?php $managers_form->printHTML(); ?>

<h3>可见组</h3>
<table class="table table-hover">
	<thead>
		<tr>
			<th>#</th>
			<th>组名</rh>
		</tr>
	</thead>
	<tbody>
<?php
	$result = DB::select("select group_name from problems_visibility where problem_id = {$problem['id']}");
	for ($row_id = 1; $row = DB::fetch($result); ++$row_id)
		echo '<tr>', '<td>', $row_id, '</td>', '<td>', getGroupLink($row['group_name']), '</td>', '</tr>';
?>
	</tbody>
</table>
<p class="text-center">命令格式：命令一行一个，+zhjc 表示把 zhjc 加入可见组，-zhjc 表示把 zhjc 从可见组中移除</p>
<?php $visibility_form->printHTML(); ?>

<?php echoUOJPageFooter() ?>
