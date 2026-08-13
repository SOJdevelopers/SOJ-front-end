<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');
	requirePHPLib('problem');

	if (!Auth::check()) {
		redirectToLogin();
	}

	if(!isBlogAllowedUser(Auth::user())) {
		become403Page();
	}

	function echoProblem($problem) {
		if (isBlogAllowedUser(Auth::user())) {
			echo '<tr class="text-center">';
			echo '<td>';
			echo $problem['id'], '</td>';
			echo '<td class="text-left">';
			echo '<a href="', $problem['url'], '">', $problem['title'], '</a>';
			foreach (queryCollectionTags($problem['id']) as $tag) {
				echo '<a class="uoj-collection-tag">', '<span class="badge">', HTML::escape($tag), '</span>', '</a>';
			}
			echo '</td>';
			echo '<td class="text-left">';
			echo $problem['note'];
			echo '</td>';
			echo '</tr>';
		}
	}

	$cur_tab = isset($_GET['type']) ? $_GET['type'] : 'null';

	$cond = "1";

	$header = '<tr>';
	$header .= '<th class="text-center" style="width: 5em">ID</th>';
	$header .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
	$header .= '<th>' . UOJLocale::get('note') . '</th>';
	$header .= '</tr>';

	$pag_config = array('page_len' => 10000);
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

	$append_form = new UOJForm('append');

	$append_form->addInput('id', 'text', 'id', '',
		function($result) {
			if(!preg_match('/^[a-z0-9]{1,12}$/', $result)) return '题号长度不能超过 12，且只能包含小写字母和数字';
			return '';
		},
		null
	);
	$append_form->addInput('title', 'text', 'title', '',
		function($result) {
			if(strlen($result) > 100) return '长度不能超过 100 byte';
			return '';
		},
		null
	);
	$append_form->addInput('url', 'text', 'url', '',
		function($result) {
			if(strlen($result) > 100) return '长度不能超过 100 byte';
			return '';
		},
		null
	);
	$append_form->addInput('tag', 'text', 'tag', '',
		function($result) {
			if(strlen($result) > 100) return '长度不能超过 100 byte';
			return '';
		},
		null
	);
	$append_form->addInput('note', 'text', 'note', '',
		function($result) {
			if(strlen($result) > 100) return '长度不能超过 100 byte';
			return '';
		},
		null
	);

	$append_form->handle = function() {
		$id = $_POST['id'];
		$title = DB::escape($_POST['title']);
		$url = DB::escape($_POST['url']);
		$tag = DB::escape(strtolower($_POST['tag']));
		$note = DB::escape($_POST['note']);
		DB::insert("insert into collection_proposal (id, title, url, tag, note) values ('$id', '$title', '$url', '$tag', '$note')");
	};
	$append_form->runAtServer();
?>
<?php echoUOJPageHeader(UOJLocale::get('problem collection')) ?>
<?php $append_form->printHTML(); ?>
<?php echoUOJPageFooter() ?>
