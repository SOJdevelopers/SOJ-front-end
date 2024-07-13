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
				while (list($contest_id) = DB::fetch($result, MYSQLI_NUM)) {
					$contest = queryContest($contest_id);
					genMoreContestInfo($contest);
					if ($contest['cur_progress'] != CONTEST_NOT_STARTED) {
						if (isset($contest['extra_config']['is_group_contest'])) {
							if (queryRegisteredGroup(Auth::user(), $contest, true)) {
								$visible = true;
							}
						} else {
							if (hasRegistered(Auth::user(), $contest)) {
								$visible = true;
							}
						}
					}
				}
			}
			if (!$visible) {
				become403Page();
			}

			$id = $_GET['id'];
			insertAuditLog('problems','download attachments',$id,isset($_GET['reason'])?mb_substr($_GET['reason'], 0, 100):'','');
			
			$file_name = "/var/uoj_data/{$id}/download.zip";
			$download_name = "problem_{$id}.zip";
			break;
		case 'testlib.h':
			$file_name = $uojMainJudgerWorkPath . '/include/testlib.h';
			$download_name = 'testlib.h';
			break;
		case 'ex_testlib.h':
			$file_name = $uojMainJudgerWorkPath . '/include/ex_testlib.h';
			$download_name = 'ex_testlib.h';
			break;
		case 'uoj_judger.h':
			$file_name = $uojMainJudgerWorkPath . '/include/uoj_judger.h';
			$download_name = 'uoj_judger.h';
			break;
		case 'judger.cpp':
			$file_name = $uojMainJudgerWorkPath . '/builtin/judger/judger.cpp';
			$download_name = 'judger.cpp';
			break;
		case 'data':
			if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
				become404Page();
			}

			$permission = hasProblemPermission(Auth::user(), $problem);
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
			insertAuditLog('problems','download data',$id,isset($_GET['reason'])?mb_substr($_GET['reason'], 0, 100):'','');
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

	header("X-Sendfile: {$file_name}");
	header("Content-type: {$mimetype}");
	header("Content-Disposition: attachment; filename={$download_name}");
