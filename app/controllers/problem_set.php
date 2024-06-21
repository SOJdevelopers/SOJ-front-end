<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');

	if (!Auth::check()) {
		redirectToLogin();
	}

	if (isProblemManager(Auth::user())) {
		$new_problem_form = new UOJForm('new_problem');
		$new_problem_form->handle = function() {
			DB::insert("insert into problems (title, is_hidden, submission_requirement) values ('New Problem', 1, '{}')");
			$id = DB::insert_id();
			DB::insert("insert into problems_contents (id, statement, statement_md) values ({$id}, '', '')");
			DB::insert("insert into problems_visibility (problem_id, group_name) values ({$id}, '" . UOJConfig::$data['profile']['common-group'] . "')");
			dataNewProblem($id);
		};
		$new_problem_form->submit_button_config['align'] = 'right';
		$new_problem_form->submit_button_config['class_str'] = 'btn btn-primary';
		$new_problem_form->submit_button_config['text'] = UOJLocale::get('problems::add new');
		$new_problem_form->submit_button_config['smart_confirm'] = '';
		
		$new_problem_form->runAtServer();
	}
	
	function echoProblem($problem) {
		if (isProblemVisible(Auth::user(), $problem)) {
			echo '<tr class="text-center">';
			if ($problem['submission_id']) {
				echo '<td class="success">';
			} else {
				echo '<td>';
			}
			echo '#', $problem['id'], '</td>';
			echo '<td class="text-left">';
			if ($problem['is_hidden']) {
				echo '<span class="text-danger">[', UOJLocale::get('hidden'), ']</span> ';
			}
			echo '<a href="/problem/', $problem['id'], '">', $problem['title'], '</a>';
			if (isset($_COOKIE['show_tags_mode'])) {
				foreach (queryProblemTags($problem['id']) as $tag) {
					echo '<a class="uoj-problem-tag">', '<span class="badge">', HTML::escape($tag), '</span>', '</a>';
				}
			}
			echo '</td>';
			if (isset($_COOKIE['show_submit_mode'])) {
				$perc = $problem['submit_num'] > 0 ? round(100 * $problem['ac_num'] / $problem['submit_num']) : 0;
				echo <<<EOD
				<td><a href="/submissions?problem_id={$problem['id']}&min_score=100&max_score=100">&times;{$problem['ac_num']}</a></td>
				<td><a href="/submissions?problem_id={$problem['id']}">&times;{$problem['submit_num']}</a></td>
				<td>
					<div class="progress bot-buffer-no">
						<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="$perc" aria-valuemin="0" aria-valuemax="100" style="width: {$perc}%; min-width: 20px;">{$perc}%</div>
					</div>
				</td>
EOD;
			}
			echo '<td class="text-left">', getClickZanBlock('P', $problem['id'], $problem['zan']), '</td>';
			echo '</tr>';
		}
	}
	
	$cond = array();
	
	$search_tag = null;
	
	$cur_tab = isset($_GET['tab']) ? $_GET['tab'] : 'all';
	if (isset($_GET['tag'])) {
		$search_tag = DB::escape($_GET['tag']);
	}
	
	if ($search_tag) {
		$tags_raw = explode(',', str_replace('，', ',', $search_tag));
		foreach ($tags_raw as &$tag_ref) {
			$tag_ref = trim($tag_ref);
		}
	}
	else
		$tags_raw = array();

	$validate_input = true;
	if (count($tags_raw) > 10)
		$validate_input = false;
	foreach ($tags_raw as $tag) {
		if (strlen($tag) > 30) {
			$validate_input = false;
			break;
		}
	}

	if ($validate_input) {
		if ($cur_tab == 'template')
			$tags_raw[] = '模板题';
	
		if ($tags_raw) {
			$tags = array();
			foreach ($tags_raw as $tag) {
				if (strlen($tag) == 0) {
					continue;
				}
				if (in_array($tag, $tags, true)) {
					continue;
				}
				$tags[] = $tag;
				$cond[] = "exists (select 1 from problems_tags where problems_tags.problem_id = problems.id and problems_tags.tag = '{$tag}')";
			}
		}
		
		if ($cur_tab == 'contested') {
			$cond[] = 'exists (select 1 from contests_problems where contests_problems.problem_id = problems.id)';
		}
	
		if (isset($_GET['search'])) {
			$esc_search = mb_substr(DB::escape($_GET['search']),0,128);
			$cmp = (isset($_GET['regexp']) && $_GET['regexp']=='true') ? "regexp '{$esc_search}'" : "like '%{$esc_search}%'";
			$cond_or = array();
			if (isset($_GET['regexp']) && $_GET['regexp']=='true')
				$cond_or[] = "id regexp '{$esc_search}'";
			else
				if(validateUInt($esc_search))
					$cond_or[] = "id = '{$esc_search}'";
			$cond_or[] = 'title ' . $cmp;
			$cond_or[] = 'exists (select 1 from problems_tags where problems_tags.problem_id = problems.id and problems_tags.tag '.$cmp.')';
			if (isset($_GET['content']) && $_GET['content']=='true')
			    $cond_or[] = 'exists (select 1 from problems_contents where problems_contents.id = problems.id and problems_contents.statement_md ' . $cmp . ')';
			if ($cond_or)
				$cond[] = '(' . join(' or ', $cond_or) . ')';
		}
	
		if ($cond) {
			$cond = join($cond, ' and ');
		} else {
			$cond = '1';
		}
	}
	else
		$cond = '0';

	$header = '<tr>';
	$header .= '<th class="text-center" style="width: 5em">ID</th>';
	$header .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
	if (isset($_COOKIE['show_submit_mode'])) {
		$header .= '<th class="text-center" style="width: 5em">' . UOJLocale::get('problems::ac') . '</th>';
		$header .= '<th class="text-center" style="width: 5em">' . UOJLocale::get('problems::submit') . '</th>';
		$header .= '<th class="text-center" style="width: 150px">' . UOJLocale::get('problems::ac ratio') . '</th>';
	}
	$header .= '<th class="text-center" style="width: 180px">' . UOJLocale::get('appraisal') . '</th>';
	$header .= '</tr>';
	
	$tabs_info = array(
		'all' => array(
			'name' => UOJLocale::get('problems::all problems'),
			'url' => '/problems'
		),
		'template' => array(
			'name' => UOJLocale::get('problems::template problems'),
			'url' => '/problems/template'
		),
		'contested' => array(
			'name' => UOJLocale::get('problems::contested problems'),
			'url' => '/problems/contested'
		)
	);

	$pag_config = array('page_len' => 100);
	$pag_config['col_names'] = array('*');
	$username = Auth::id();
	$pag_config['table_name'] = "problems left join best_ac_submissions on best_ac_submissions.submitter = '{$username}' and problems.id = best_ac_submissions.problem_id";
	$pag_config['cond'] = $cond;
	$pag_config['tail'] = 'order by id asc';
	$pag_config['max_extend'] = 5;
	$pag_config['timeout'] = 1000;
	$pag = new Paginator($pag_config);

	$div_classes = array('table-responsive');
	$table_classes = array('table', 'table-bordered', 'table-hover', 'table-striped');
?>
<?php echoUOJPageHeader(UOJLocale::get('problems')) ?>
<div class="row">
	<div class="col-xs-6 col-sm-6">
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills') ?>
	</div>
	<div class="col-xs-6 col-sm-6 checkbox text-right">
		<label class="checkbox-inline" for="input-show_tags_mode"><input type="checkbox" id="input-show_tags_mode" <?= isset($_COOKIE['show_tags_mode']) ? 'checked="checked" ': ''?>/> <?= UOJLocale::get('problems::show tags') ?></label>
		<label class="checkbox-inline" for="input-show_submit_mode"><input type="checkbox" id="input-show_submit_mode" <?= isset($_COOKIE['show_submit_mode']) ? 'checked="checked" ': ''?>/> <?= UOJLocale::get('problems::show statistics') ?></label>
	</div>
</div>
<form id="form-search" class="form-horizontal" role="form" method="get">
	<div class="form-group">
		<label class="col-sm-1 control-label"><?= UOJLocale::get('tags')?></label>
		<div class="col-sm-2">
			<input type="text" class="form-control" name="tag" maxlength="128" placeholder="<?= UOJLocale::get('separated by comma')?>" <?= isset($_GET['tag']) ? 'value="' . HTML::escape($_GET['tag']) . '" ' : '' ?>/>
		</div>
		<div class="col-sm-3 text-right">
			<label class="checkbox-inline"><input type="checkbox" name="content" value="true" <?= isset($_GET['content']) && $_GET['content']=='true' ? 'checked' : '' ?>/><?= UOJLocale::get('search problem contents')?></label>
			<label class="checkbox-inline"><input type="checkbox" name="regexp" value="true" <?= isset($_GET['regexp']) && $_GET['regexp']=='true' ? 'checked' : '' ?>/><?= UOJLocale::get('regexp')?></label>
		</div>
		<label class="col-sm-1 control-label"><?= UOJLocale::get('keyword')?></label>
		<div class="col-sm-4 input-group">
			<input type="text" class="form-control" name="search" id="search" maxlength="128" placeholder="<?= UOJLocale::get('search problem')?>" <?= isset($_GET['search']) ? 'value="' . HTML::escape($_GET['search']) . '" ' : '' ?>/>
			<span class="input-group-btn"><button type="submit" class="btn btn-search btn-primary" id="submit-search"><span class="glyphicon glyphicon-search"></span></button></span>
		</div>
	</div>
</form>
<div class="row">
	<div class="col-xs-12 col-sm-12 input-group">
		<?php echo $pag->pagination(); ?>
	</div>
</div>
<?php
	if (isProblemManager(Auth::user())) {
		$new_problem_form->printHTML();
	}
?>
<div class="top-buffer-sm"></div>
<script type="text/javascript">
	document.addEventListener("keydown",function(event) {
		if(event.keyCode===191&&document.activeElement.type!=="text") {
			var obj=document.getElementById("search");
			var pos=obj.value.length;
			obj.setSelectionRange(pos,pos);
			obj.focus();
			event.preventDefault();
		}
	});
	$('#input-show_tags_mode').click(function() {
		if (this.checked) {
			$.cookie('show_tags_mode', '', {path: '/problems'});
		} else {
			$.removeCookie('show_tags_mode', {path: '/problems'});
		}
		location.reload();
	});
	$('#input-show_submit_mode').click(function() {
		if (this.checked) {
			$.cookie('show_submit_mode', '', {path: '/problems'});
		} else {
			$.removeCookie('show_submit_mode', {path: '/problems'});
		}
		location.reload();
	});
</script>
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
