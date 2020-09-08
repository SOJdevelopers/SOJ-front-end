<?php

// score[username][problem_pos] = [score, penalty, lastID, estimate]
// standings[rank] => [score, penalty, [name, user_rating], rank, estimate]

$score = array();
$n_people = count($contest_data['people']);
$n_problems = count($contest_data['problems']);
const challenge_pos = 0;

foreach ($contest_data['people'] as $person) {
	$score[$person[0]] = array_fill(0, $n_problems, NULL);
}

$challenge_max = 1;
$challenge_list = array();

foreach ($contest_data['data'] as $submission) {
	$penalty = (new DateTime($submission[1]))->getTimestamp() - $contest['start_time']->getTimestamp();
	if ($submission[4] === 0) {
		$penalty = null;
	}
	if ($submission[3] === challenge_pos) {
		if ($submission[4] === 100) {
			$challenge_max = max($challenge_max, $now = 50000 - $submission[6]);
			if (!isset($challenge_list[$submission[2]]) or $now > $challenge_list[$submission[2]]) {
				$challenge_list[$submission[2]] = $now;
				$score[$submission[2]][$submission[3]] = array($submission[4], $penalty, $submission[0], $submission[5]);
			}
		} else if (!isset($challenge_list[$submission[2]])) {
			$score[$submission[2]][$submission[3]] = array($submission[4], $penalty, $submission[0], $submission[5]);
		}
	} else {
		$score[$submission[2]][$submission[3]] = array($submission[4], $penalty, $submission[0], $submission[5]);
	}
}

$standings = array();
foreach ($contest_data['people'] as $person) {
	$cur = array(0, 0, $person, 0, null);
	for ($i = 0; $i < $n_problems; $i++) {
		if (isset($score[$person[0]][$i])) {
			$cur_row = $score[$person[0]][$i];
			$cur[1] += $cur_row[1];
			if (isset($cur_row[3])) {
				$cur_estimate = $cur_row[3];
				$cur[4] = (isset($cur[4]) ? $cur[4] : 0) + $cur_row[3];
			} else {
				$cur_estimate = 'null';
			}
			if ($update_contests_submissions) {
				if ($i === challenge_pos) {
					DB::insert("replace into contests_submissions (contest_id, submitter, problem_id, submission_id, score, penalty, estimate, used_time) values ({$contest['id']}, '{$person[0]}', {$contest_data['problems'][$i]}, {$cur_row[2]}, {$cur_row[0]}, " . (is_null($cur_row[1]) ? 0 : $cur_row[1]) . ", {$cur_estimate}, " . (50000 - $challenge_list[$person[0]]) . ')');
				} else {
					DB::insert("replace into contests_submissions (contest_id, submitter, problem_id, submission_id, score, penalty, estimate) values ({$contest['id']}, '{$person[0]}', {$contest_data['problems'][$i]}, {$cur_row[2]}, {$cur_row[0]}, " . (is_null($cur_row[1]) ? 0 : $cur_row[1]) . ", {$cur_estimate})");
				}
			}
			if ($i === challenge_pos) {
				$cur[0] += $score[$person[0]][$i][0] = round($challenge_list[$person[0]] * 1000 / $challenge_max);
			} else {
				$cur[0] += $cur_row[0];
			}
		}
	}
	$standings[] = $cur;
}

usort($standings, function($lhs, $rhs) {
	if ($lhs[0] != $rhs[0]) {
		return $rhs[0] - $lhs[0];
	} else if ($lhs[1] != $rhs[1]) {
		return $lhs[1] - $rhs[1];
	} else {
		return strcmp($lhs[2][0], $rhs[2][0]);
	}
});

$is_same_rank = function($lhs, $rhs) {
	return $lhs[0] == $rhs[0] && $lhs[1] == $rhs[1];
};

for ($i = 0; $i < $n_people; $i++) {
	if ($i == 0 || !$is_same_rank($standings[$i - 1], $standings[$i])) {
		$standings[$i][3] = $i + 1;
	} else {
		$standings[$i][3] = $standings[$i - 1][3];
	}
}
