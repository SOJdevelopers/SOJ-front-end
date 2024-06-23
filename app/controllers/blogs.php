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
	$config['table_classes'] = array('table', 'table-hover', 'table-vertical-middle');
?>
<?php echoUOJPageHeader(UOJLocale::get('blogs')) ?>
<div class="pull-right btn-group">
	<a href="<?= HTML::blog_url(Auth::id(), '/') ?>" class="btn btn-default btn-sm"><?= UOJLocale::get('my blog homepage') ?></a>
	<a href="<?= HTML::blog_url(Auth::id(), '/blog/new/write')?>" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-edit"></span> <?= UOJLocale::get('write new blog') ?></a>
</div>
<h3><?= UOJLocale::get('blog overview') ?></h3>
<?php
	$search_form = new SOJForm();
	$search = $search_form->addTextSubmit('search', UOJLocale::get('keyword').':', 'class="form-control" maxlength="128" placeholder="'.UOJLocale::get('title').'"', validateLength(128));
	$search_form->focusText('search', 191);
	$tag = $search_form->addCheckBox('tag', UOJLocale::get('search blog tags'), 'true');
	$regexp = $search_form->addCheckBox('regexp', UOJLocale::get('regexp'), 'true');
	$search_form->printHTML('blogs');

	$cond = 'is_hidden = 0';
	if (!isSuperUser(Auth::user())) {
		initBlogEnvironment(Auth::user());
		$cond .= " and id in (select id from blog_t)";
	}
	if ($search) {
		$search = DB::escape($search);
		$cmp = $regexp ? "regexp '{$search}'" : "like '%{$search}%'";
		$cond_or = "title {$cmp}";
		if ($tag) {
			$cond_or .= " or exists (select 1 from blogs_tags where blogs_tags.blog_id = blogs.id and blogs_tags.tag {$cmp})";
		}
		$cond .= " and ({$cond_or})";
	}
	echoLongTable(array('id', 'poster', 'title', 'post_time', 'zan', 'latest_comment', 'latest_commenter'), 'blogs', $cond, isset($_COOKIE['blogs_sortby']) ? 'order by latest_comment desc' : 'order by post_time desc', $header, 'echoBlogCell', $config);
?>
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
