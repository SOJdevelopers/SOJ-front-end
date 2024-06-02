<?php
	$tabs_info = array(
		'rating' => '',
		'ac' => '',
		'group' => '',
		'mygroup' => ''
	);

	if (isset($_GET['type']) and isset($tabs_info[$_GET['type']])) {
		$config = array('page_len' => 100, 'more_details' => '');
	} else {
		become404Page();
	}

	if (!Auth::check()) {
		redirectToLogin();
	}

	if ($_GET['all'] === 'true') {
		$config['show_all'] = '';
	}

	if ($_GET['type'] === 'rating') {
		echoUOJPageHeader(UOJLocale::get('top rated'));
		echoRanklist($config);
	} elseif ($_GET['type'] === 'ac') {
		echoUOJPageHeader(UOJLocale::get('top cutted'));
		echoACRanklist($config);
	} elseif ($_GET['type'] === 'group') {
		echoUOJPageHeader(UOJLocale::get('top rated groups'));
		echoGrouplist($config);
	} elseif ($_GET['type'] === 'mygroup') {
		echoUOJPageHeader(UOJLocale::get('my groups'));
		echoGrouplist($config, true);
?>
		<div class="text-right">
		<a href="/group/new" class="btn btn-primary"><?= UOJLocale::get('new group') ?></a>
		</div>
<?php
	}
	echoUOJPageFooter()
?>
