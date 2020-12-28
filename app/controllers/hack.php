<?php
	requirePHPLib('form');

	if (!validateUInt($_GET['id']) || !($hack = queryHack($_GET['id']))) {
		become404Page();
	}

	if (!Auth::check()) {
		redirectToLogin();
	}

	$submission = querySubmission($hack['submission_id']);	
	$problem = queryProblemBrief($submission['problem_id']);
	$problem_extra_config = getProblemExtraConfig($problem);
	$has_permission = hasProblemPermission(Auth::user(), $problem);

	if (!checkGroup(Auth::user(), $problem)) {
		become403Page();
	}

	if ($submission['contest_id']) {
		if (!checkContestGroup(Auth::user(), array('id' => $submission['contest_id']))) {
			become403Page();
		}
	}

	if (querySubmissionDetailPermission(Auth::user(), $submission)) {
		become403Page();
	}

	if ($has_permission) {
		$delete_form = new UOJForm('delete');
		$delete_form->handle = function() {
			global $hack;
			DB::delete("delete from hacks where id = {$hack['id']}");
		};
		$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
		$delete_form->submit_button_config['text'] = '删除此Hack';
		$delete_form->submit_button_config['align'] = 'right';
		$delete_form->submit_button_config['smart_confirm'] = '';
		$delete_form->succ_href = '/hacks';
		$delete_form->runAtServer();
	}
	
	$should_show_content = hasViewPermission($problem_extra_config['view_content_type'], Auth::user(), $problem, $submission);
	$should_show_all_details = hasViewPermission($problem_extra_config['view_all_details_type'], Auth::user(), $problem, $submission);
	$should_show_details = hasViewPermission($problem_extra_config['view_details_type'], Auth::user(), $problem, $submission);

	if ($has_permission) {
		$should_show_content = $should_show_all_details = $should_show_details_to_me = true;
	}

	if ($should_show_all_details) {
		$styler = new HackDetailsStyler();
		if (!$should_show_details) {
			$styler->fade_all_details = true;
			$styler->show_small_tip = false;
		}
	}
?>
<?php
	$REQUIRE_LIB['shjs'] = '';
?>
<?php echoUOJPageHeader(UOJLocale::get('problems::hack').' #'.$hack['id']) ?>

<?php echoHackListOnlyOne($hack, array(), $myUser) ?>
<?php if ($should_show_all_details) { ?>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h4 class="panel-title"><?= UOJLocale::get('details') ?></h4>
		</div>
		<div class="panel-body">
			<?php echoJudgementDetails($hack['details'], $styler, 'details') ?>
			<?php if ($should_show_details_to_me) { ?>
				<?php if (!$should_show_details) { ?>
					<hr />
					<?php echoHackDetails($hack['details'], 'final_details') ?>
				<?php } ?>
			<?php } ?>
		</div>
	</div>
<?php } ?>
<?php
	echoSubmissionsListOnlyOne($submission, array(), $myUser);

	if ($should_show_content) {
		echoSubmissionContent($submission, getProblemSubmissionRequirement($problem));
	}

	if (isset($delete_form)) {
		$delete_form->printHTML();
	}
?>
<?php echoUOJPageFooter() ?>
