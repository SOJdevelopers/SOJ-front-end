<?php
	requirePHPLib('form');
	
	if (!(Auth::check() and isSuperUser(Auth::user()))) {
		become403Page();
	}

	$time_form = new UOJForm('time');
	$time_form->addInput(
		'name', 'text', '比赛标题', 'New Contest',
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
		'start_time', 'text', '开始时间', date("Y-m-d H:i:s"),
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
		'last_min', 'text', '时长（单位：分钟）', 180,
		function($str) {
			return !validateUInt($str) ? '必须为一个整数' : '';
		},
		null
	);
	$time_form->handle = function(&$vdata) {
		$start_time_str = $vdata['start_time']->format('Y-m-d H:i:s');
		$esc_name = DB::escape($vdata['name']);

		DB::insert("insert into contests (name, start_time, last_min, status) values ('$esc_name', '$start_time_str', {$_POST['last_min']}, 'unfinished')");
		$id = DB::insert_id();
		DB::insert("insert into contests_visibility (contest_id, group_name) values ({$id}, '" . UOJConfig::$data['profile']['common-group'] . "')");
	};
	$time_form->succ_href = '/contests';
	$time_form->runAtServer();
?>
<?php echoUOJPageHeader('添加比赛') ?>

<h2 class="page-header">添加比赛</h2>

<?php $time_form->printHTML(); ?>

<?php echoUOJPageFooter() ?>
