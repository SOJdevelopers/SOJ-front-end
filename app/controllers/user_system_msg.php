<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	$header_row = '<tr>';
	$header_row .= '<th>' . UOJLocale::get('message') . '</th>';
	$header_row .= '<th style="width: 15em">' . UOJLocale::get('time') . '</th>';
	$header_row .= '</tr>';

	function echoSysMsg($msg) {
		echo $msg['read_time'] == null ? '<tr class="warning">' : '<tr>';
		echo '<td>';
		echo '<h4>', $msg['title'], '</h4>';
		echo $msg['content'];
		echo '</td>';
		echo '<td>', $msg['send_time'], '</td>';
		echo '</tr>';
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('system message')) ?>
<h2><?= UOJLocale::get('system message') ?></h2>
<?php echoLongTable(array('*'), 'user_system_msg', 'receiver=\'' . Auth::id() . '\'', 'order by id desc', $header_row, 'echoSysMsg', array('table_classes' => array('table'))) ?>
<?php DB::update('update user_system_msg set read_time = now() where receiver = \'' . Auth::id() . '\'') ?>
<?php echoUOJPageFooter() ?>
