<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	requirePHPLib('form');

	$search_form = new SOJForm();
	$common = 'class="form-control input-sm" ';
	$q_submission_id = $search_form->addText('submission_id', UOJLocale::get('problems::submission id').':', $common.'maxlength="6" style="width:5em"', 'validateUInt');
	$q_problem_id = $search_form->addText('problem_id', UOJLocale::get('problems::problem id').':', $common.'maxlength="4" style="width:4em"', 'validateUInt');
	$q_hacker = $search_form->addText('hacker', UOJLocale::get('problems::hacker').':', $common.'maxlength="20" style="width:8em"', 'validateUsername');
	$q_owner = $search_form->addText('owner', UOJLocale::get('problems::owner').':', $common.'maxlength="20" style="width:8em"', 'validateUsername');
	$q_status = $search_form->addSelect('status', UOJLocale::get('problems::result').':', $common.'style="width:7em"', [''=>'All', 1=>'Success!', 2=>'Failed.']);
	$q_cut_off_time = $search_form->addText('cut_off_time', UOJLocale::get('cut off time').':', $common . 'maxlength="11" style="width:8em; font-family: monospace" placeholder="YYYY-MM-DD"', "validateTime");
	$search_form->addSubmit(UOJLocale::get('search'));

	$conds = array();
	if($q_problem_id != null) {
		$conds[] = "problem_id = {$q_problem_id}";
	}
	if($q_submission_id != null) {
		$conds[] = "submission_id = {$q_submission_id}";
	}
	if($q_hacker != null) {
		$conds[] = "hacker = '{$q_hacker}'";
	}
	if($q_owner != null) {
		$conds[] = "owner = '{$q_owner}'";
	}
	if($q_status == 1) {
		$conds[] = 'success = 1';
	}
	if($q_status == 2) {
		$conds[] = 'success = 0';
	}
	if ($q_cut_off_time != null) {
		if (strlen($q_cut_off_time) == 10) {
			$q_cut_off_time = $q_cut_off_time . ' 23:59:59';
		}
		$conds[] = 'submit_time <=\'' . DB::escape($q_cut_off_time) . '\'';
	}
	
	if ($conds) {
		$cond = join($conds, ' and ');
	} else {
		$cond = '1';
	}
?>
<?php echoUOJPageHeader(UOJLocale::get('hacks')) ?>
<div class="hidden-xs">
	<div class="pull-right">
		<a href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('hacker' => Auth::id(), 'owner' => null, 'page' => null))) ?>" class="btn btn-success btn-sm"><?= UOJLocale::get('problems::hacks by me') ?></a>
		<a href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('hacker' => null, 'owner' => Auth::id(), 'page' => null))) ?>" class="btn btn-danger btn-sm"><?= UOJLocale::get('problems::hacks to me') ?></a>
	</div>
<?php $search_form->printHTML('hacks') ?>
	<div class="top-buffer-sm"></div>
</div>
<?php echoHacksList($cond, 'order by id desc', array('judge_time_hidden' => ''), Auth::user()) ?>
<?php echoUOJPageFooter() ?>
