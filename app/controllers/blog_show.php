<?php
	if (!validateUInt($_GET['id']) || !($blog = queryBlog($_GET['id']))) {
		become404Page();
	}
	if (!checkBlogGroup(Auth::user(), $blog)) {
		become403Page();
	}
	redirectTo(HTML::blog_url($blog['poster'], UOJContext::requestURI()));
?>
