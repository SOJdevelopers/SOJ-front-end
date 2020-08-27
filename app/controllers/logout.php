<?php
	crsf_defend();
	Auth::logout();
?>

<script type="text/javascript">
	var prevUrl = document.referrer;
	if (prevUrl == '' || /.*\/login.*/.test(prevUrl) || /.*\/register.*/.test(prevUrl) || /.*\/reset-password.*/.test(prevUrl) || /.*\/logout.*/.test(prevUrl)) {
		prevUrl = '/';
	};
	window.location.href = prevUrl;
</script>
