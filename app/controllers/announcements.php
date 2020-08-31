<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		redirectToLogin();
	}

	function echoBlogCell($blog) {
		$level = $blog['level'];

		if ($level == 0) {
			$level_str = '';
		} else {
			$level_str = '<span style="color: red">[' . UOJLocale::get('sticky ' . (4 - $level)) . ']</span> ';
		}

		echo '<tr>';
		echo '<td>', $level_str . getBlogLink($blog['id']), '</td>';
		echo '<td>', getUserLink($blog['poster']), '</td>';
		echo '<td>', $blog['post_time'], '</td>';
		echo '</tr>';
	}
	$header = '<tr>';
	$header .= '<th width="60%">' . UOJLocale::get('title') . '</th>';
	$header .= '<th width="20%">' . UOJLocale::get('publisher') .  '</th>';
	$header .= '<th width="20%">' . UOJLocale::get('publish time') . '</th>';
	$header .= '</tr>';
	$config = [
		'table_classes' => ['table', 'table-hover'],
		'page_len' => 100
	];
?>
<?php echoUOJPageHeader(UOJLocale::get('announcements')) ?>
<h3><?= UOJLocale::get('announcements') ?></h3>
<?php echoLongTable(array('blogs.id', 'poster', 'title', 'post_time', 'zan', 'level'), 'important_blogs, blogs', 'is_hidden = 0 and important_blogs.blog_id = blogs.id', 'order by level desc, blogs.post_time desc', $header, 'echoBlogCell', $config); ?>
<?php echoUOJPageFooter() ?>
