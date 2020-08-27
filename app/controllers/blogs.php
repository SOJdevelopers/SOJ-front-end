<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		redirectToLogin();
	}

	function echoBlogCell($blog) {
		echo '<tr>';
		echo '<td>', getBlogLink($blog['id']), '</td>';
		echo '<td>', getUserLink($blog['poster']), '</td>';
		echo '<td>', $blog['post_time'], '</td>';
		echo '</tr>';
	}
	$header = '<tr>';
	$header .= '<th width="60%">' . UOJLocale::get('title') . '</th>';
	$header .= '<th width="20%">' . UOJLocale::get('publisher') .  '</th>';
	$header .= '<th width="20%">' . UOJLocale::get('publish time') . '</th>';
	$header .= '</tr>';
	$config = array();
	$config['table_classes'] = array('table', 'table-hover');
?>
<?php echoUOJPageHeader(UOJLocale::get('blogs')) ?>
<div class="pull-right btn-group">
	<a href="<?= HTML::blog_url(Auth::id(), '/') ?>" class="btn btn-default btn-sm"><?= UOJLocale::get('my blog homepage') ?></a>
	<a href="<?= HTML::blog_url(Auth::id(), '/blog/new/write')?>" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-edit"></span> <?= UOJLocale::get('write new blog') ?></a>
</div>
<h3><?= UOJLocale::get('blog overview') ?></h3>
<?php echoLongTable(array('id', 'poster', 'title', 'post_time', 'zan'), 'blogs', 'is_hidden = 0', 'order by post_time desc', $header, 'echoBlogCell', $config); ?>
<?php echoUOJPageFooter() ?>
