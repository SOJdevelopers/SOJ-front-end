<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');
	requirePHPLib('problem');

	if (!Auth::check()) {
		redirectToLogin();
	}

	function isValidProblemId($id) {
		return preg_match('/^[a-z0-9]{1,12}$/', $id);
	}

	if (isset($_POST['state'])) {
		$state = (int)$_POST['state'];
		if(!isset($_POST['id'])) die('id not set');
		$id = $_POST['id'];
		if(!isValidProblemId($id)) die('invalid id');
		if(!DB::selectCount("select count(*) from collection where id = '$id'")) die('no such problem');
		if(!($state >= 0 && $state <= 2)) die('invalid state');
		$username = Auth::id();
		DB::insert("insert into collection_state (id, username, state) values ('$id', '$username', $state) on duplicate key update state = $state");
		die('ok');
	}

	function echoProblem($problem) {
		$username = Auth::id();
		if (isBlogAllowedUser(Auth::user())) {
			$state = 0;
			$stateResult = DB::selectFirst("select state from collection_state where id = '{$problem['id']}' and username = '$username'");
			if(isset($stateResult)) $state = $stateResult['state'];
			$color = ['fff','feb','dfb'][$state];
			echo '<tr style="background-color: #'.$color.'" id="problem-'.$problem['id'].'">';
			echo '<td class="text-center">';
			echo $problem['id'], '</td>';
			echo '<td>';
			echo '<div style="display: flex; justify-content: space-between">';
			echo '<span style="text-align: left">';
			echo '<a href="', $problem['url'], '">', $problem['title'], '</a>';
			echo '</span>';
			echo '<span style="text-align: right">';
			echo '<a class="glyphicon glyphicon-remove" onclick=\'changeProblemState(0,"'.$problem['id'].'");\'></a> ';
			echo '<a style="color: #aa0" class="glyphicon glyphicon-question-sign" onclick=\'changeProblemState(1,"'.$problem['id'].'");\'></a> ';
			echo '<a class="glyphicon glyphicon-ok" onclick=\'changeProblemState(2,"'.$problem['id'].'");\'></a>';
			echo '</span>';
			echo '</div>';
			echo '</td>';
			echo '<td class="text-left">';
			echo $problem['note'];
			echo '</td>';
			echo '</tr>';
		}
	}
	
	$cur_tab = isset($_GET['type']) ? $_GET['type'] : 'null';

	$cond = "exists (select 1 from collection_tags where collection_tags.collection_id = collection.id and collection_tags.tag = '{$cur_tab}')";

	$header = '<tr>';
	$header .= '<th class="text-center" style="width: 5em">ID</th>';
	$header .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
	$header .= '<th>' . UOJLocale::get('note') . '</th>';
	$header .= '</tr>';
	
	$tabs_info = array(
		'counting' => array(
			'name' => 'Counting',
			'url' => '/collection?type=counting'
		),
		'constructive' => array(
			'name' => 'Constructive',
			'url' => '/collection?type=constructive'
		),
		'interactive' => array(
			'name' => 'Interactive',
			'url' => '/collection?type=interactive'
		),
	);

	$pag_config = array('page_len' => 1000);
	$pag_config['col_names'] = array('*');
	$username = Auth::id();
	$pag_config['table_name'] = "collection";
	$pag_config['cond'] = $cond;
	$pag_config['tail'] = 'order by id asc';
	$pag_config['max_extend'] = 5;
	$pag_config['timeout'] = 1000;
	$pag = new Paginator($pag_config);

	$div_classes = array('table-responsive');
	$table_classes = array('table', 'table-bordered', 'table-hover', 'table-striped');
?>
<?php echoUOJPageHeader(UOJLocale::get('problem collection')) ?>
<script>
function changeProblemState(state, id) {
	title = document.getElementById('problem-' + id).children[1].children[0].children[0].textContent;
	if(!confirm(id + ' - ' + title + '\n' + ['Unsolved', 'Uncertain', 'Solved'][state] + '?')) return;
	$.post('/collection', {
		'state': state,
		'id': id,
	}, function(msg) {
		if (msg == 'ok') {
			window.location.reload();
		} else {
			alert('修改失败：' + msg);
		}
	});
}
</script>
<div class="row">
	<div class="col-xs-6 col-sm-6">
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills') ?>
	</div>
<?php if(isBlogAllowedUser(Auth::user())):?>
	<div class="col-xs-3 col-sm-3">
		<ul class="nav nav-pills" role="tablist"><li><a href="/collection/propose"><span class="glyphicon glyphicon-send"></span>  <?= UOJLocale::get('propose') ?></a></li></ul>
	</div>
<?php endif?>
<?php if(isProblemCreator(Auth::user())):?>
	<div class="col-xs-3 col-sm-3">
		<ul class="nav nav-pills" role="tablist"><li><a href="/collection/manage"><span class="glyphicon glyphicon-cog"></span>  <?= UOJLocale::get('manage') ?></a></li></ul>
	</div>
<?php endif?>
</div>
<div class="top-buffer-sm"></div>
<div class="top-buffer-sm"></div>
<div class="row">
	<div class="col-xs-12 col-sm-12 input-group">
		<?php echo $pag->pagination(); ?>
	</div>
</div>
<div class="top-buffer-sm"></div>
<?php
	echo '<div class="', join($div_classes, ' '), '">';
	echo '<table class="', join($table_classes, ' '), '">';
	echo '<thead>';
	echo $header;
	echo '</thead>';
	echo '<tbody>';
	
	foreach ($pag->get() as $idx => $row) {
		echoProblem($row);
	}
	
	echo '</tbody>';
	echo '</table>';
	echo '</div>';
	
	echo $pag->pagination();
?>
<?php echoUOJPageFooter() ?>
