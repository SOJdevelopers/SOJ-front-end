<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		redirectToLogin();
	}

	$group_form = new UOJForm('group');
	$group_form->addInput(
		'name', 'text', UOJLocale::get('groupname'), '',
		function($gn) {
			if (!validateUsername($gn) or $gn == 'new') {
				return '无效组名';
			}
			if (queryGroup($gn)) {
				return "组 $gn 已经存在";
			}
			if (queryUser($gn)) {
				return '为防止抢注，组名不可和他人用户名相同';
			}
			return '';
		},
		null
	);
	$group_form->addTextArea(
		'description', UOJLocale::get('description'), '',
		function($gd) {
			if (!validateMotto($gd)) {
				return '无效组描述';
			}
			return '';
		},
		null
	);
	$group_form->addInput(
		'avatar', 'text', UOJLocale::get('group avatar'), '/pictures/SOJ.png',
		function($ga) {
			if (!validateMotto($ga)) {
				return '无效组图像';
			}
			return '';
		},
		null
	);
	$group_form->addSelect(
		'joinable', array('A' => UOJLocale::get('join A'), 'C' => UOJLocale::get('join C'), 'N' => UOJLocale::get('join N')), UOJLocale::get('joinable'), 'C'
	);
	$group_form->handle = function() {
		$dp = DB::escape($_POST['description']);
		$av = DB::escape($_POST['avatar']);
	
		$username = Auth::id();
		DB::insert("insert into group_info (group_name, description, avatar, joinable, group_type) values ('{$_POST['name']}', '$dp', '$av', '{$_POST['joinable']}', 'N')");
		DB::insert("insert into group_members (group_name, username, member_state) values ('{$_POST['name']}', '{$username}', 'A')");

		header("Location: /group/{$_POST['name']}");
	};
	$group_form->succ_href = 'none';
	$group_form->runAtServer();
?>
<?php echoUOJPageHeader(UOJLocale::get('new group')) ?>

<h2 class="page-header"><?= UOJLocale::get('new group') ?></h2>

<?php $group_form->printHTML(); ?>

<?php echoUOJPageFooter() ?>
