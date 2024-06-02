<?php
	require $_SERVER['DOCUMENT_ROOT'] . '/app/uoj-lib.php';

	if ($_SERVER['REDIRECT_STATUS'] == 403) {
		become403Page();
	} else
		become404Page();
