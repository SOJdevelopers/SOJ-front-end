<?php

if (!Auth::check()) {
	become403Page();
}

if (!is_array($_GET['get'])) {
	become404Page();
}

$res = [];

foreach ($_GET['get'] as $id) {
	if (!validateUInt($id)) {
		become404Page();
	}

	$submission = querySubmission($id);
	if (isOurSubmission(Auth::user(), $submission) !== true) {
		become403Page();
	}

	$out_status = explode(', ', $submission['status'])[0];

	$res[] = [
		'judged' => $out_status == 'Judged',
		'html' => getSubmissionStatusDetails($submission)
	];
}

die(json_encode($res));
