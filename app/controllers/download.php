<?php
	requirePHPLib('judger');

	if (!Auth::check()) {
		redirectToLogin();
	}

	switch ($_GET['type']) {
		case 'problem':
			if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
				become404Page();
			}

			$visible = isProblemVisible(Auth::user(), $problem);
			if (!$visible) {
				$result = DB::select("select contest_id from contests_problems where problem_id = {$_GET['id']}");
				while (list($contest_id) = DB::fetch($result, MYSQL_NUM)) {
					$contest = queryContest($contest_id);
					genMoreContestInfo($contest);
					if (isset($contest['extra_config']['is_group_contest'])) {
						$gs = DB::select("select * from contests_registrants where contest_id = {$contest['id']} and exists (select 1 from group_members where group_members.group_name = contests_registrants.username and group_members.username = '{$myUser['username']}' and group_members.member_state != 'W')");
						$group = DB::fetch($gs);
						if ($group and !DB::fetch($gs)) {
							$visible = true;
						}
					} else {
						if ($contest['cur_progress'] != CONTEST_NOT_STARTED && hasRegistered(Auth::user(), $contest)) {
							$visible = true;
						}
					}
				}
			}
			if (!$visible) {
				become404Page();
			}

			$id = $_GET['id'];
			
			$file_name = "/var/uoj_data/{$id}/download.zip";
			$download_name = "problem_{$id}.zip";
			break;
		case 'testlib.h':
			$file_name = '/home/local_main_judger/judge_client/uoj_judger/include/testlib.h';
			$download_name = 'testlib.h';
			break;
		case 'ex_testlib.h':
			$file_name = '/home/local_main_judger/judge_client/uoj_judger/include/ex_testlib.h';
			$download_name = 'ex_testlib.h';
			break;
		case 'uoj_judger.h':
			$file_name = '/home/local_main_judger/judge_client/uoj_judger/include/uoj_judger.h';
			$download_name = 'uoj_judger.h';
			break;
		case 'judger.cpp':
			$file_name = '/home/local_main_judger/judge_client/uoj_judger/builtin/judger/judger.cpp';
			$download_name = 'judger.cpp';
			break;
		case 'data':
			if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
				become404Page();
			}

			$permission = hasProblemPermission($myUser, $problem);
			if (!$permission) {
				$config = getProblemExtraConfig($problem);
				if ($config['data_download'] && isProblemVisible(Auth::user(), $problem)) {
					$permission = true;
				}
			}
			if (!$permission) {
				become403Page();
			}

			$id = $_GET['id'];
			$file_name = "/var/uoj_data/{$id}.zip";
			$download_name = "data_{$id}.zip";
			break;
		default:
			become404Page();
	}
	$finfo = finfo_open(FILEINFO_MIME);
	$mimetype = finfo_file($finfo, $file_name);
	if ($mimetype === false) {
		become404Page();
	}
	finfo_close($finfo);
	var_dump($mimetype);

	header("X-Sendfile: {$file_name}");
	header("Content-type: {$mimetype}");
	header("Content-Disposition: attachment; filename={$download_name}");
