<?php
	requirePHPLib('form');

	if (!isset($_GET['id']) || !validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id'])) || !UOJContext::isHis($blog)) {
		become404Page();
	}

	if (!Auth::check()) {
		redirectToLogin();
	}

	if ($blog['is_hidden'] && !UOJContext::hasBlogPermission()) {
		become403Page();
	}

	if (isset($_POST['delete'])) {
		if (!isset($_POST['id']) || !validateUInt($_POST['id'])) {
			die('');
		}

		$comment = queryBlogComment($_POST['id']);
		if (!$comment || $comment['blog_id'] !== $blog['id']) {
			die('');
		}

		if (isSuperUser(Auth::user()) || Auth::id() === $blog['poster'] || Auth::id() === $comment['poster']) {
			deleteBlogComment($_POST['id'], $blog['id']);
			die('ok');
		} else if ($comment['reply_id'] != 0) {
			$parent = queryBlogComment($comment['reply_id']);
			if (Auth::id() === $parent['poster']) {
				deleteBlogComment($_POST['id'], $blog['id']);
				die('ok');
			}
		}

		die('no-permission');
	}

	$comment_form = new UOJForm('comment');
	$comment_form->addVTextArea('comment', UOJLocale::get('content'), '',
		function($comment) {
			if (!$comment) {
				return '评论不能为空';
			}
			if (strlen($comment) > 4095) {
				return '不能超过 4095 个字节';
			}
			return '';
		},
		null
	);
	$comment_form->handle = function() {
		global $myUser, $blog, $comment_form;
		$comment = HTML::escape($_POST['comment']);
		
		list($comment, $referrers) = uojHandleSign($comment);
		
		$esc_comment = DB::escape($comment);
		DB::insert("insert into blogs_comments (poster, blog_id, content, reply_id, post_time, zan) values ('{$myUser['username']}', {$blog['id']}, '{$esc_comment}', 0, now(), 0)");
		DB::update("update blogs set latest_comment = now() and latest_commenter = '{$myUser['username']}'");
		$comment_id = DB::insert_id();

		$rank = DB::selectCount("select count(*) from blogs_comments where blog_id = {$blog['id']} and reply_id = 0 and id < {$comment_id}");
		$page = floor($rank / 20) + 1;
		
		$uri = getLongTablePageUri($page) . '#' . "comment-{$comment_id}";
		
		foreach ($referrers as $referrer) {
			$content = '有人在博客 ' . $blog['title'] . ' 的评论里提到你：<a href="' . $uri . '">点击此处查看</a>';
			sendSystemMsg($referrer, '有人提到你', $content);
		}
		
		if ($blog['poster'] !== $myUser['username']) {
			$content = '有人回复了您的博客 ' . $blog['title'] . ' ：<a href="' . $uri . '">点击此处查看</a>';
			sendSystemMsg($blog['poster'], '博客新回复通知', $content);
		}
		
		$comment_form->succ_href = getLongTablePageRawUri($page);
	};
	$comment_form->ctrl_enter_submit = true;
	
	$comment_form->runAtServer();
	
	$reply_form = new UOJForm('reply');
	$reply_form->addHidden('reply_id', '0',
		function($reply_id, &$vdata) {
			global $blog;
			if (!validateUInt($reply_id) || $reply_id == 0) {
				return '您要回复的对象不存在';
			}
			$comment = queryBlogComment($reply_id);
			if (!$comment || $comment['blog_id'] != $blog['id']) {
				return '您要回复的对象不存在';
			}
			$vdata['parent'] = $comment;
			return '';
		},
		null
	);
	$reply_form->addVTextArea('reply_comment', UOJLocale::get('content'), '',
		function($comment) {
			if (!$comment) {
				return '评论不能为空';
			}
			if (strlen($comment) > 1000) {
				return '不能超过 1000 个字节';
			}
			return '';
		},
		null
	);
	$reply_form->handle = function(&$vdata) {
		global $myUser, $blog, $reply_form;
		$comment = HTML::escape($_POST['reply_comment']);
		
		list($comment, $referrers) = uojHandleSign($comment);
		
		$reply_id = $_POST['reply_id'];
		
		$esc_comment = DB::escape($comment);
		DB::insert("insert into blogs_comments (poster, blog_id, content, reply_id, post_time, zan) values ('{$myUser['username']}', {$blog['id']}, '{$esc_comment}', {$reply_id}, now(), 0)");
		DB::update("update blogs set latest_comment = now() and latest_commenter = '{$myUser['username']}' where id = {$blog['id']}");
		$comment_id = DB::insert_id();
		
		$rank = DB::selectCount("select count(*) from blogs_comments where blog_id = {$blog['id']} and reply_id = 0 and id < {$reply_id}");
		$page = floor($rank / 20) + 1;
		
		$uri = getLongTablePageUri($page) . '#' . "comment-{$reply_id}";
		
		foreach ($referrers as $referrer) {
			$content = '有人在博客 ' . $blog['title'] . ' 的评论里提到你：<a href="' . $uri . '">点击此处查看</a>';
			sendSystemMsg($referrer, '有人提到你', $content);
		}
		
		$parent = $vdata['parent'];
		$notified = array();
		if ($parent['poster'] !== $myUser['username']) {
			$notified[] = $parent['poster'];
			$content = '有人回复了您在博客 ' . $blog['title'] . ' 下的评论 ：<a href="' . $uri . '">点击此处查看</a>';
			sendSystemMsg($parent['poster'], '评论新回复通知', $content);
		}
		if ($blog['poster'] !== $myUser['username'] && !in_array($blog['poster'], $notified)) {
			$notified[] = $blog['poster'];
			$content = '有人回复了您的博客 ' . $blog['title'] . ' ：<a href="' . $uri . '">点击此处查看</a>';
			sendSystemMsg($blog['poster'], '博客新回复通知', $content);
		}
		
		$reply_form->succ_href = getLongTablePageRawUri($page);
	};
	$reply_form->ctrl_enter_submit = true;
	
	$reply_form->runAtServer();
	
	$comments_pag = new Paginator(array(
		'col_names' => array('*'),
		'table_name' => 'blogs_comments',
		'cond' => "blog_id = {$blog['id']} and reply_id = 0",
		'tail' => 'order by id asc',
		'page_len' => 20
	));
?>
<?php
	$REQUIRE_LIB['mathjax'] = '';
	$REQUIRE_LIB['shjs'] = '';
?>
<?php echoUOJPageHeader(HTML::stripTags($blog['title']) . ' - ' . UOJLocale::get('blogs')) ?>
<?php echoBlog($blog, array('show_title_only' => isset($_GET['page']) && $_GET['page'] != 1)) ?>
<h2><?= UOJLocale::get('comments') ?> <span class="glyphicon glyphicon-comment"></span></h2>
<div class="list-group">
<?php
	if ($comments_pag->isEmpty()) {
		echo '<div class="list-group-item text-muted">' . UOJLocale::get('no comment yet') . '</div>';
	} else {
		foreach ($comments_pag->get() as $comment) {
			$poster = queryUser($comment['poster']);
			$esc_email = HTML::escape($poster['email']);
			$asrc = HTML::avatar_addr($poster, 80);
		
			$replies = DB::selectAll("select id, poster, content, post_time from blogs_comments where reply_id = {$comment['id']} order by id");
			foreach ($replies as $idx => $reply) {
				$replies[$idx]['poster_rating'] = queryUser($reply['poster'])['rating'];
				$replies[$idx]['removable'] = (isSuperUser(Auth::user()) || Auth::id() === $blog['poster'] || Auth::id() === $comment['poster'] || Auth::id() === $reply['poster']);
			}
			$replies_json = json_encode($replies);
?>
	<div id="comment-<?= $comment['id'] ?>" class="list-group-item">
		<div class="media<?= $comment['zan'] < -10 ? ' comttoobad' : ($comment['zan'] < -5 ? ' comtbad' : '') ?>">
			<div class="media-left comtposterbox">
				<a href="<?= HTML::url('/user/profile/'.$poster['username']) ?>" class="hidden-xs">
					<img class="media-object img-rounded" src="<?= $asrc ?>" alt="avatar" />
				</a>
			</div>
			<div id="comment-body-<?= $comment['id'] ?>" class="media-body comtbox">
				<div class="row">
					<div class="col-sm-6"><?= getUserLink($poster['username']) ?></div>
					<div class="col-sm-6 text-right"><?= getClickZanBlock('BC', $comment['id'], $comment['zan']) ?></div>
				</div>
				<div class="comtbox1 uoj-readmore"><?= $comment['content'] ?></div>
				<ul class="text-right list-inline bot-buffer-no">
					<li><small class="text-muted"><?= $comment['post_time'] ?></small></li>
					<li><a id="reply-to-<?= $comment['id'] ?>" href="#"><?= UOJLocale::get('reply') ?></a></li>
<?php if (isSuperUser(Auth::user()) || Auth::id() === $blog['poster'] || Auth::id() === $comment['poster']) { ?>
					<li><a id="delete-<?= $comment['id'] ?>" href="#"><?= UOJLocale::get('delete') ?></a></li>
<?php } ?>
				</ul>
<?php if ($replies) { ?>
				<div id="replies-<?= $comment['id'] ?>" class="comtbox5"></div>
<?php } ?>
				<script type="text/javascript">showCommentReplies('<?= $comment['id'] ?>', <?= $replies_json ?>);</script>
			</div>
		</div>
	</div>
<?php
		}
	}
?>
</div>
<?= $comments_pag->pagination() ?>

<h3><?= UOJLocale::get('post comment') ?></h3>
<p><?= UOJLocale::get('at tip') ?></p>
<p><?= UOJLocale::get('emoticon tip') ?></p>
<?php $comment_form->printHTML() ?>

<div id="div-form-reply" style="display: none">
<?php $reply_form->printHTML() ?>
</div>

<?php echoUOJPageFooter() ?>
