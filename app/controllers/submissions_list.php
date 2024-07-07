<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	requirePHPLib('form');


	$search_form = new SOJForm();
	$common = 'class="form-control input-sm" ';
	$q_problem_id = $search_form->addText('problem_id', UOJLocale::get('problems::problem id').':', $common . 'maxlength="4" style="width:4em"', 'validateUInt');
	$q_submitter = $search_form->addText('submitter', UOJLocale::get('username').':', $common . 'maxlength="20" style="width:10em"', 'validateUsername');
	$q_min_score = $search_form->addText('min_score', UOJLocale::get('score range').':', $common . 'maxlength="5" style="width:4em" placeholder="-∞"', 'validateInt');
	$q_max_score = $search_form->addText('max_score', '~', $common . 'maxlength="5" style="width:4em" placeholder="+∞"', 'validateInt');
	$q_language = $search_form->addText('language', UOJLocale::get('problems::language').':', $common . 'maxlength="10" style="width:8em"', validateLength(10));
	$search_form->addSubmit(UOJLocale::get('search'));
	
	$conds = array();
	if($q_problem_id != null) {
		$conds[] = "problem_id = {$q_problem_id}";
	}
	if($q_submitter != null) {
		$conds[] = "submitter = '{$q_submitter}'";
	}
	if ($q_min_score != null) {
		$conds[] = "score >= {$q_min_score}";
	}
	if ($q_max_score != null) {
		$conds[] = "score <= {$q_max_score}";
	}
	if ($q_language != null) {
		$conds[] = 'language = \'' . DB::escape($q_language) . '\'';
	}

	if ($conds) {
		$cond = join($conds, ' and ');
	} else {
		$cond = '1';
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('submissions')) ?>
<div class="hidden-xs">
	<div class="pull-right">
		<a href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('submitter' => Auth::id(), 'page' => null))) ?>" class="btn btn-primary btn-sm"><?= UOJLocale::get('problems::my submissions') ?></a>
	</div>
<?php $search_form->printHTML('submissions') ?>
	<div class="top-buffer-sm"></div>
</div>
<?php
	echoSubmissionsList($cond, 'order by id desc', array('judge_time_hidden' => ''), Auth::user());
?>
<?php echoUOJPageFooter() ?>
