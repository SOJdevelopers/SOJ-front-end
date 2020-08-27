<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		redirectToLogin();
	}
?>
<?php
        $REQUIRE_LIB['mathjax'] = '';
        $REQUIRE_LIB['shjs'] = '';
?>
<?php echoUOJPageHeader(UOJLocale::get('about me')) ?>

<?php
	$hisUser = UOJContext::user();

	$blog_id = $hisUser['about_me'];

	if ($blog_id && ($blog = queryBlog($blog_id))) {
		echoBlog($blog, array('content_only' => true));
	} else {
		echo '<h3>博主是个 AK 了 IOI 的超级大神犇！</h3>';
		echo '<p>(目前已经滋磁定制此页，请去 <a href="/user/modify-profile" target="_blank">更改个人信息</a> 区设定)</p>';
	}
?>

<?php echoUOJPageFooter() ?>
