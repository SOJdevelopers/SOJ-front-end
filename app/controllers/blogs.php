<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		redirectToLogin();
	}

	function echoBlogCell($blog) {
		echo '<tr>';
		echo '<td>', getBlogLink($blog['id']), '</td>';
		echo '<td><div>', $blog['post_time'], '</div><div>', getUserLink($blog['poster']), '</div></td>';
		if ($blog['latest_commenter']) {
			echo '<td><div>', $blog['latest_comment'], '</div><div>', getUserLink($blog['latest_commenter']), '</div></td>';
		} else {
			echo '<td><div class="text-muted">', UOJLocale::get('no comment'), '</div></td>';
		}
		echo '<td>', getClickZanBlock('B', $blog['id'], $blog['zan']), '</td>';
		echo '</tr>';
	}
	$header = '<tr>';
	$header .= '<th>' . UOJLocale::get('title') . '</th>';
	$header .= '<th style="width: 140px">' . '<button id="input-sortby-post" class="btn btn-' . (isset($_COOKIE['blogs_sortby']) ? 'default' : 'primary') . ' btn-xs"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></button> ' . UOJLocale::get('publish time') . '</th>';
	$header .= '<th style="width: 140px">' . '<button id="input-sortby-active" class="btn btn-' . (isset($_COOKIE['blogs_sortby']) ? 'primary' : 'default') . ' btn-xs"><span class="glyphicon glyphicon-sort-by-attributes-alt"></span></button> ' . UOJLocale::get('latest comment') . '</th>';
	$header .= '<th style="width: 180px">' . UOJLocale::get('appraisal') . '</th>';
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
<?php echoLongTable(array('id', 'poster', 'title', 'post_time', 'zan', 'latest_comment', 'latest_commenter'), 'blogs', 'is_hidden = 0', isset($_COOKIE['blogs_sortby']) ? 'order by latest_comment desc' : 'order by post_time desc', $header, 'echoBlogCell', $config); ?>
<script type="text/javascript">
	$('#input-sortby-post').click(function() {
		$.removeCookie('blogs_sortby', {path: '/blogs'});
		location.reload();
	});
	$('#input-sortby-active').click(function() {
		$.cookie('blogs_sortby', 'active', {path: '/blogs'});
		location.reload();
	});
</script>
<?php echoUOJPageFooter() ?>
