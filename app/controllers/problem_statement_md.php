<?php
	requirePHPLib('form');
	requirePHPLib('judger');

	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}

	$time_now = DB::query_time_now();

	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!checkGroup(Auth::user(), $problem)) {
		become403Page();
	}

	$problem_content = queryProblemContent($problem['id']);
	$agent = Auth::id();

	$contest = validateUInt($_GET['contest_id']) ? queryContest($_GET['contest_id']) : null;
	if ($contest != null) {
		genMoreContestInfo($contest);
		$rgroup = isset($contest['extra_config']['is_group_contest']);
		if ($problem_rank = queryContestProblemRank($contest, $problem)) {
			$problem_letter = chr(64 + $problem_rank);
		} else {
			become404Page();
		}
	}

	$is_in_contest = $contest != null && $contest['cur_progress'] === CONTEST_IN_PROGRESS;
	$ban_in_contest = false;
	$is_visible = isProblemVisible(Auth::user(), $problem);
	$statement_maintainable = $is_visible && isStatementMaintainer(Auth::user());

	if ($contest != null) {
		if (!hasContestPermission(Auth::user(), $contest)) {
			if ($contest['cur_progress'] === CONTEST_NOT_STARTED) {
				become404Page();
			} elseif ($contest['cur_progress'] === CONTEST_IN_PROGRESS) {
				if ($rgroup) {
					$group = queryRegisteredGroup(Auth::user(), $contest);
					$agent = $group['group_name'];
				} else {
					queryRegisteredUser(Auth::user(), $contest);
				}
				DB::update("update contests_registrants set has_participated = 1 where username = '{$agent}' and contest_id = {$contest['id']}");
			} elseif (!isProblemVisible(Auth::user(), $problem, $contest)) {
				become404Page();
			} else {
				$ban_in_contest = !$is_visible;
			}
		} else {
			$is_in_contest = false;
		}
	} elseif (!$is_visible) {
		become404Page();
	}

	$problem_extra_config = getProblemExtraConfig($problem);
	if (isset($problem_extra_config['dont_show_statement_md']) && !$statement_maintainable) {
		become403Page();
	}

	header('Content-Type: text/plain');
?><?= $problem_content['statement_md'] ?>
