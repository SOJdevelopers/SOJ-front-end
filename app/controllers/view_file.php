<?php
	requirePHPLib('judger');

	if (!isset($_GET['file']) || !isset($_GET['dir'])) {
		become404Page();
	}
	$dir = $_GET['dir'];
	if ($dir !== 'uploads' && $dir !== 'utility' && $dir !== 'pictures') {
		become404Page();
	}
	$file = $_GET['file'];
	foreach (explode('/',$file) as $directory) {
		if (strStartWith($directory, '.')) {
			become404Page();
		}
	}
	$dirfile = $dir . '/' . urldecode($file);
	if (!Auth::check()) {
		$need_login = true;
		foreach (UOJConfig::$data['public-files'] as $pat) {
			if (preg_match('/^'.$pat.'$/', $dirfile)) {
				$need_login = false;
				break;
			}
		}
		if ($need_login) redirectToLogin();
	}
	$file_name = UOJContext::documentRoot() . '/' . $dirfile;
	$finfo = finfo_open(FILEINFO_MIME);
	$mimetype = finfo_file($finfo, $file_name);
	if ($mimetype === false) {
		becomeMsgPage($dirfile . ' not found');
	}
	finfo_close($finfo);
	var_dump($mimetype);

	header("X-Sendfile: {$file_name}");
	header("Content-type: {$mimetype}");
	header('Content-Disposition: inline; filename=' . urldecode(basename($file)));
	header('Content-Length: ' . filesize($file_name));
