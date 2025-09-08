<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');
	requirePHPLib('problem');

	if (!Auth::check()) {
		redirectToLogin();
	}

	$owner = $_GET['username'];

	if(!isBlogAllowedUser(Auth::user())) {
		become403Page();
	}

	if(!isProblemCreator(Auth::user()) && $owner != Auth::id()) {
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

	$cond = "owner = '$owner'";

	$header = '<tr>';
	$header .= '<th class="text-center" style="width: 5em">ID</th>';
	$header .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
	$header .= '<th>' . UOJLocale::get('note') . '</th>';
	$header .= '</tr>';

	$pag_config = array('page_len' => 10000);
	$pag_config['col_names'] = array('*');
	$username = Auth::id();
	$pag_config['table_name'] = "collection_user";
	$pag_config['cond'] = $cond;
	$pag_config['tail'] = 'order by id asc';
	$pag_config['max_extend'] = 5;
	$pag_config['timeout'] = 1000;
	$pag = new Paginator($pag_config);

	$div_classes = array('table-responsive');
	$table_classes = array('table', 'table-bordered', 'table-hover', 'table-striped');

	if($owner == Auth::id()) {
		$append_form = new UOJForm('append');

		$append_form->addInput('id', 'text', 'id', '',
			function($result) {
				if(!preg_match('/^-?[a-z0-9]{1,12}$/', $result)) return '题号长度不能超过 12，且只能包含小写字母和数字';
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
			if ($id[0] == '-') {
				$id = substr($id, 1);
				DB::delete("delete from collection_user where id='$id' and owner = '$owner'");
				DB::delete("delete from collection_user_tags where collection_id='$id' and owner = '$owner'");
				return;
			}
			$title = DB::escape($_POST['title']);
			$url = DB::escape($_POST['url']);
			$tag = DB::escape(strtolower($_POST['tag']));
			$note = DB::escape($_POST['note']);

			$upd_cmd = '';
			if($title != '') $upd_cmd .= ", title='$title'";
			if($url != '') $upd_cmd .= ", url='$url'";
			if($note != '') {
				if($note == '-') $note = '';
				$upd_cmd .= ", note='$note'";
			}
			if($upd_cmd != '') {
				DB::insert("insert into collection_user (owner, id, title, url, note) values ('$owner', '$id', '$title', '$url', '$note') on duplicate key update" . substr($upd_cmd, 1));
			}
			if($tag == '-') {
				DB::delete("delete from collection_user_tags where collection_id='$id' and owner = '$owner'");
			}
			else if($tag != '' && DB::selectCount("select count(*) from collection_user_tags where id = '$id' and owner = '$owner'")) {
				DB::insert("insert ignore into collection_user_tags (owner, collection_id, tag) values ('$owner', '$id', '$tag')");
			}
		};
		$append_form->runAtServer();
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('problem collection')) ?>
<?php if($owner == Auth::id()) $append_form->printHTML(); ?>
<h3>约定</h3>
选择一个题目所属的 OJ 时，按照以下顺序选择：
<ol>
	<li>原出处，且有中文题面或英文题面</li>
	<li>有中文题面</li>
	<li>有英文题面</li>
	<li>原出处，但无中文题面或英文题面</li>
	<li>其他</li>
</ol>
有多个同一类的 OJ，按照以下优先级选择。
<h4>各 OJ 题号格式（按照优先级排序）</h4>
<table class="table table-bordered table-hover table-striped">
	<tr><th>OJ</th><th>题号格式</th></tr>
	<tr><td>AtCoder</td><td>agc001a</td></tr>
	<tr><td>Codeforces Problemset</td><td>cf1a</td></tr>
	<tr><td>UOJ</td><td>uoj1</td></tr>
	<tr><td>QOJ</td><td>qoj1</td></tr>
	<tr><td>LOJ</td><td>loj1</td></tr>
	<tr><td>SOJ</td><td>soj1</td></tr>
	<tr><td>Luogu</td><td>lg1001</td></tr>
	<tr><td>Codeforces Gym</td><td>cf123456a</td></tr>
</table>
<ul>
	<li>
		对于 Codeforces 上的题目，使用<strong>题库风格 url</strong>。(<code>https://codeforces.com/problemset/problem/1/A</code>)
	</li>
	<li>
		尽可能<strong>去除</strong> url 中的<strong>比赛编号</strong>。(<code>https://qoj.ac<del>/contest/259</del>/problem/2</code>)
	</li>
</ul>
<h3>列表</h3>
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
