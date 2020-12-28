<?php
	requirePHPLib('form');
	requirePHPLib('judger');

	if (!validateUInt($_GET['id']) || !($submission = querySubmission($_GET['id']))) {
		become404Page();
	}

	if (!Auth::check()) {
		redirectToLogin();
	}

	$submission_result = json_decode($submission['result'], true);
	$problem = queryProblemBrief($submission['problem_id']);
	$problem_extra_config = getProblemExtraConfig($problem);
	$has_permission = hasProblemPermission(Auth::user(), $problem);

	if (!checkGroup(Auth::user(), $problem)) {
		become403Page();
	}

	if ($submission['contest_id']) {
		$contest = queryContest($submission['contest_id']);
		genMoreContestInfo($contest);
	} else {
		$contest = null;
	}

	if (querySubmissionDetailPermission(Auth::user(), $submission) === 0) {
		become403Page();
	}

	$hackable = $submission['score'] == 100 && $problem['hackable'] == 1;
	if ($contest != null && $contest['cur_progress'] < CONTEST_FINISHED) {
		$hackable = false;
	}

	if ($hackable) {
		$hack_form = new UOJForm('hack');	
		
		$hack_form->addTextFileInput('input', UOJLocale::get('input data'));
		$hack_form->addCheckBox('use_formatter', UOJLocale::get('tidy up whitespaces'), true);
		$hack_form->handle = function(&$vdata) {
			global $myUser, $problem, $submission;

			if ($_POST['input_upload_type'] == 'file') {
				$tmp_name = UOJForm::uploadedFileTmpName('input_file');
				if ($tmp_name == null) {
					becomeMsgPage('你在干啥……怎么什么都没交过来……？');
				}
			}

			$fileName = uojRandAvaiableTmpFileName();
			$fileFullName = UOJContext::storagePath().$fileName;
			if ($_POST['input_upload_type'] == 'editor') {
				file_put_contents($fileFullName, $_POST['input_editor']);
			} else {
				move_uploaded_file($_FILES['input_file']['tmp_name'], $fileFullName);
			}
			$input_type = isset($_POST['use_formatter']) ? 'USE_FORMATTER' : 'DONT_USE_FORMATTER';
			DB::insert("insert into hacks (problem_id, contest_id, submission_id, hacker, owner, input, input_type, submit_time, details) values ({$problem['id']}, {$contest['id']}, {$submission['id']}, '{$myUser['username']}', '{$submission['submitter']}', '$fileName', '$input_type', now(), '')");
		};
		$hack_form->succ_href = '/hacks';
		$hack_form->runAtServer();
	}

	if ($submission['status'] === 'Judged' && $has_permission) {
		$rejudge_form = new UOJForm('rejudge');
		$rejudge_form->handle = function() {
			global $submission;
			rejudgeSubmission($submission);
		};
		$rejudge_form->submit_button_config['class_str'] = 'btn btn-primary';
		$rejudge_form->submit_button_config['text'] = '重新测试';
		$rejudge_form->submit_button_config['align'] = 'right';
		$rejudge_form->runAtServer();
	}
	
	if ($has_permission) {
		$delete_form = new UOJForm('delete');
		$delete_form->handle = function() {
			global $submission;
			deleteSubmission($submission);
		};
		$delete_form->submit_button_config['class_str'] = 'btn btn-danger';
		$delete_form->submit_button_config['text'] = '删除此提交记录';
		$delete_form->submit_button_config['align'] = 'right';
		$delete_form->submit_button_config['smart_confirm'] = '';
		$delete_form->succ_href = '/submissions';
		$delete_form->runAtServer();
	}
	
	$should_show_content = hasViewPermission($problem_extra_config['view_content_type'], Auth::user(), $problem, $submission);
	$should_show_all_details = hasViewPermission($problem_extra_config['view_all_details_type'], Auth::user(), $problem, $submission);
	$should_show_details = hasViewPermission($problem_extra_config['view_details_type'], Auth::user(), $problem, $submission);

	if ($contest != null && $contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
		if ($contest['extra_config']["problem_{$submission['problem_id']}"] === 'no-details') {
			$should_show_details = false;
		}
		if (!isOurSubmission(Auth::user(), $submission) || (isset($contest['extra_config']['is_group_contest']) && hasOverRegistered(Auth::user(), $contest))) {
			$should_show_content = $should_show_all_details = false;
		}
	}
	if ($has_permission) {
		$should_show_content = $should_show_all_details = $should_show_details_to_me = true;
	}

	if ($should_show_all_details) {
		$styler = new SubmissionDetailsStyler();
		if (!$should_show_details) {
			$styler->fade_all_details = true;
			$styler->show_small_tip = false;
		}
	}
?>
<?php 
	$REQUIRE_LIB['shjs'] = '';
?>
<?php echoUOJPageHeader(UOJLocale::get('problems::submission').' #'.$submission['id']) ?>
<?php echoSubmissionsListOnlyOne($submission, array(), Auth::user()) ?>

<?php if ($should_show_content) { ?>
	<?php echoSubmissionContent($submission, getProblemSubmissionRequirement($problem)) ?>
	<?php if ($hackable) { ?>
		<p class="text-center">
			<?= UOJLocale::get('hack prompt') ?> <button id="button-display-hack" type="button" class="btn btn-danger btn-xs">Hack!</button>
		</p>
		<div id="div-form-hack" style="display:none" class="bot-buffer-md">
			<?php $hack_form->printHTML() ?>
		</div>
		<script type="text/javascript">
			$(document).ready(function() {
				$('#button-display-hack').click(function() {
					$('#div-form-hack').toggle('fast');
				});
			});
		</script>
	<?php } ?>
<?php } ?>

<?php if ($should_show_all_details) { ?>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h4 class="panel-title"><?= UOJLocale::get('details') ?></h4>
		</div>
		<div class="panel-body">
			<?php echoJudgementDetails($submission_result['details'], $styler, 'details') ?>
			<?php if ($should_show_details_to_me) { ?>
				<?php if (isset($submission_result['final_result'])) { ?>
					<hr />
					<?php echoSubmissionDetails($submission_result['final_result']['details'], 'final_details') ?>
				<?php } elseif (!$should_show_details) { ?>
					<hr />
					<?php echoSubmissionDetails($submission_result['details'], 'final_details') ?>
				<?php } ?>
			<?php } ?>
		</div>
	</div>
<?php } ?>

<?php
	if (isset($rejudge_form)) {
		echo '<div class="top-buffer-sm">';
		$rejudge_form->printHTML();
		echo '</div>';
	}

	if (isset($delete_form)) {
		echo '<div class="top-buffer-sm">';
		$delete_form->printHTML();
		echo '</div>';
	}
?>
<?php echoUOJPageFooter() ?>
