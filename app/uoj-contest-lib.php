<?php
define('CONTEST_NOT_STARTED', 0);
define('CONTEST_IN_PROGRESS', 1);
define('CONTEST_PENDING_FINAL_TEST', 2);
define('CONTEST_TESTING', 10);
define('CONTEST_FINISHED', 20);	

function calcRating($standings, $K = 400) {
	$DELTA = 500;

	$n = count($standings);

	$rating = array();
	for ($i = 0; $i < $n; ++$i) {
		$rating[$i] = $standings[$i][2][1];
	}

	$rank = array();
	$foot = array();
	for ($i = 0; $i < $n; ) {
		$j = $i;
		while ($j + 1 < $n && $standings[$j + 1][3] == $standings[$j][3]) {
			++$j;
		}
		$our_rk = 0.5 * (($i + 1) + ($j + 1));
		while ($i <= $j) {
			$rank[$i] = $our_rk;
			$foot[$i] = $n - $rank[$i];
			$i++;
		}
	}

	$weight = array();
	for ($i = 0; $i < $n; ++$i) {
		$weight[$i] = pow(7, $rating[$i] / $DELTA);
	}
	$exp = array_fill(0, $n, 0);
	for ($i = 0; $i < $n; ++$i)
		for ($j = 0; $j < $n; ++$j)
			if ($j != $i) {
				$exp[$i] += $weight[$i] / ($weight[$i] + $weight[$j]);
			}

	$new_rating = array();
	for ($i = 0; $i < $n; $i++) {
		$new_rating[$i] = $rating[$i];
		$new_rating[$i] += ceil($K * ($foot[$i] - $exp[$i]) / ($n - 1));
	}

	for ($i = $n - 1; $i >= 0; $i--) {
		if ($i + 1 < $n && $standings[$i][3] != $standings[$i + 1][3]) {
			break;
		}
		if ($new_rating[$i] > $rating[$i]) {
			$new_rating[$i] = $rating[$i];
		}
	}

	for ($i = 0; $i < $n; $i++) {
		if ($new_rating[$i] < 0) {
			$new_rating[$i] = 0;
		}
	}

	return $new_rating;
}

function calcRatingSelfTest() {
	$tests = [
		[[1500, 1], [1500, 1]],
		[[1500, 1], [1600, 1]],
		[[1500, 1], [1600, 2], [1600, 2]],
		[[1500, 1], [200, 2], [100, 2]],
		[[1500, 1], [100, 2], [200, 2]],
		[[1500, 1], [100, 2], [200, 3]],
		[[1500, 1], [200, 2], [100, 3]],
		[[1500, 1], [3000, 2], [1500, 3]],
		[[1500, 1], [3000, 2], [1500, 3], [1500, 3]],
		[[1500, 1], [1500, 2], [1500, 3], [3000, 4]],
		[[1500, 1], [1500, 2], [10, 3], [1, 4]]
	];
	foreach ($tests as $test_num => $test) {
		print "test #{$test_num}\n";

		$standings = array();
		$n = count($test);
		for ($i = 0; $i < $n; $i++) {
			$standings[] = [0, 0, [(string)$i, $test[$i][0]], $test[$i][1]];
		}
		$new_rating = calcRating($standings);

		for ($i = 0; $i < $n; $i++) {
			printf("%3d: %4d -> %4d delta: %+4d\n", $test[$i][1], $test[$i][0], $new_rating[$i], $new_rating[$i] - $test[$i][0]);
		}
		print "\n";
	}
}

function genMoreContestInfo(&$contest) {
	$contest['start_time_str'] = $contest['start_time'];
	$contest['start_time'] = new DateTime($contest['start_time']);
	$contest['end_time'] = clone $contest['start_time'];
	$contest['end_time']->add(new DateInterval("PT{$contest['last_min']}M"));

	if ($contest['status'] == 'unfinished') {
		if (UOJTime::$time_now < $contest['start_time']) {
			$contest['cur_progress'] = CONTEST_NOT_STARTED;
		} elseif (UOJTime::$time_now < $contest['end_time']) {
			$contest['cur_progress'] = CONTEST_IN_PROGRESS;
		} else {
			$contest['cur_progress'] = CONTEST_PENDING_FINAL_TEST;
		}
	} elseif ($contest['status'] == 'testing') {
		$contest['cur_progress'] = CONTEST_TESTING;
	} elseif ($contest['status'] == 'finished') {
		$contest['cur_progress'] = CONTEST_FINISHED;
	}

	$contest['extra_config_str'] = $contest['extra_config'];
	$contest['extra_config'] = json_decode($contest['extra_config'], true);

	if (!isset($contest['extra_config']['standings_version'])) {
		$contest['extra_config']['standings_version'] = 2;
	}
}

function updateContestPlayerNum($contest) {
	DB::update("update contests set player_num = (select count(*) from contests_registrants where contest_id = {$contest['id']}) where id = {$contest['id']}");
}

// problems: pos => id
// data    : id, submit_time, submitter, problem_pos, score
// people  : username, user_rating
function queryContestData($contest, $config = array()) {
	mergeConfig($config, array(
		'pre_final' => false,
		'show_estimate_result' => false
	));

	$problems = array();
	$prob_pos = array();
	$full_scores = array();
	$n_problems = 0;
	$result = DB::query("select problem_id from contests_problems where contest_id = {$contest['id']} order by problem_id");
	$need_only_myself = (isset($contest['extra_config']['only_myself']) and $contest['cur_progress'] < CONTEST_PENDING_FINAL_TEST and !hasContestPermission(Auth::user(), $contest));

	while ($row = DB::fetch($result, MYSQL_NUM)) {
		$problems[] = $p = (int)$row[0];
		$prob_pos[$p] = $n_problems++;
		$full_scores[] = isset($contest['extra_config']["full_score_{$p}"]) ? $contest['extra_config']["full_score_{$p}"] : 100;
	}

	$data = array();
	if ($config['pre_final']) {
		$result = DB::query("select id, submit_time, submitter, problem_id, score, estimate, used_time, used_memory, result from submissions where contest_id = {$contest['id']} and score is not null order by id");
		while ($row = DB::fetch($result, MYSQL_NUM)) {
			$r = json_decode($row[8], true);
			$row[0] = (int)$row[0];
			$row[3] = $prob_pos[$row[3]];
			if (isset($r['final_result'])) {
				$row[4] = (int)($r['final_result']['score']);
			} else {
				$row[4] = (int)$row[4];
			}
			if (isset($row[5])) {
				$row[5] = (int)$row[5];
			}
			unset($row[8]);
			$data[] = $row;
		}
	} else {
		$query_estimate = $contest['cur_progress'] >= CONTEST_PENDING_FINAL_TEST && $config['show_estimate_result'];
		$query_only_myself = ($need_only_myself ? 'and submitter = \'' . Auth::id() . '\'' : '');

		if ($contest['cur_progress'] < CONTEST_FINISHED) {
			$result = DB::query("select id, submit_time, submitter, problem_id, score, estimate, used_time, used_memory from submissions where contest_id = {$contest['id']} and score is not null {$query_only_myself} order by id");
		} else {
			$result = DB::query("select submission_id, date_add('{$contest['start_time_str']}', interval penalty second), submitter, problem_id, score, estimate, used_time, used_memory from contests_submissions where contest_id = {$contest['id']} order by submission_id");
		}
		while ($row = DB::fetch($result, MYSQL_NUM)) {
			$row[0] = (int)$row[0];
			$row[3] = $prob_pos[$row[3]];
			$row[4] = (int)$row[4];
			if ($query_estimate) {
				if (isset($row[5])) {
					$row[5] = (int)$row[5];
				}
			} else {
				unset($row[5]);
			}
			$data[] = $row;
		}
		if (isset($config['verbose']) and $contest['cur_progress'] >= CONTEST_FINISHED) {
			$filled = array();
			foreach ($data as $row) {
				$filled[$row[0]] = '';
			}
			$result_d = DB::query("select id, submit_time, submitter, problem_id, score{$query_estimate} from submissions where contest_id = {$contest['id']} and score is not null order by id");
			while ($row = DB::fetch($result_d, MYSQL_NUM)) {
				$row[0] = (int)$row[0];
				$row[3] = $prob_pos[$row[3]];
				$row[4] = (int)$row[4];
				if (isset($row[5])) {
					$row[5] = (int)$row[5];
				}
				if (!isset($filled[$row[0]])) {
					$data[] = $row;
					$filled[$row[0]] = '';
				}
			}
		}
	}

	$query_only_myself = ($need_only_myself ? 'and username = \'' . Auth::id() . '\'' : '');
	$people = array();
	$result = DB::query("select username, user_rating from contests_registrants where contest_id = {$contest['id']} {$query_only_myself} and has_participated = 1");
	while ($row = DB::fetch($result, MYSQL_NUM)) {
		$row[1] = (int)$row[1];
		$people[] = $row;
	}

	return array('problems' => $problems, 'data' => $data, 'people' => $people, 'full_scores' => $full_scores);
}

function calcStandings($contest, $contest_data, &$score, &$standings, $update_contests_submissions = false) {
	$script = isset($contest['extra_config']['standings_script']) ? $contest['extra_config']['standings_script'] : 'builtin';
	$file = UOJContext::documentRoot() . '/utility/contest_scripts/' . $script . '.php';
	if (!(validateUsername($script) and is_file($file))) {
		$file = UOJContext::documentRoot() . '/utility/contest_scripts/builtin.php';
	}
	include $file;
}
