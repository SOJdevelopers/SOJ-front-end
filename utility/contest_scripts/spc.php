<?php

// score[username][problem_pos] = [score, penalty, lastID, submit_times]
// standings[rank] => [score, raw_penalty, [name, user_rating], rank, wa_penalty]

$score = array();
$n_people = count($contest_data['people']);
$n_problems = count($contest_data['problems']);
$wa_penalty = isset($contest['wa_penalty']) ? $contest['wa_penalty'] : 1200;

foreach ($contest_data['people'] as $person) {
	$score[$person[0]] = array_fill(0, $n_problems, NULL);
}

foreach ($contest_data['data'] as $submission) {
	$penalty = (new DateTime($submission[1]))->getTimestamp() - $contest['start_time']->getTimestamp();
	if (!isset($score[$submission[2]][$submission[3]])) {
		$score[$submission[2]][$submission[3]] = array($submission[4], $penalty, $submission[0], (int)($submission[4] == 0));
	} else if ($submission[4] > $score[$submission[2]][$submission[3]][0]) {
		$score[$submission[2]][$submission[3]] = array($submission[4], $penalty, $submission[0], $score[$submission[2]][$submission[3]][3]);
	} else {
		++$score[$submission[2]][$submission[3]][3];
	}
	if ($update_contests_submissions) {
		DB::insert("replace into contests_submissions (contest_id, submitter, problem_id, submission_id, score, penalty, estimate) values ({$contest['id']}, '{$submission[2]}', {$contest_data['problems'][$submission[3]]}, {$submission[0]}, {$submission[4]}, {$penalty})");
	}
}

$standings = array();
foreach ($contest_data['people'] as $person) {
	$cur = array(0, 0, $person, 0, 0, 0);
	for ($i = 0; $i < $n_problems; $i++) {
		if (isset($score[$person[0]][$i])) {
			$cur_row = $score[$person[0]][$i];
			$cur[0] += $cur_row[0];
			if ($cur_row[0] > 0) {
				$cur[1] += $cur_row[1];
				$cur[4] += $cur_row[3];
			}
		}
	}
	$cur[5] = $cur[1] + $cur[4] * $wa_penalty;
	$standings[] = $cur;
}

usort($standings, function($lhs, $rhs) {
	if ($lhs[0] != $rhs[0]) {
		return $rhs[0] - $lhs[0];
	} else if ($lhs[5] != $rhs[5]) {
		return $lhs[5] - $rhs[5];
	} else {
		return strcmp($lhs[2][0], $rhs[2][0]);
	}
});

$is_same_rank = function($lhs, $rhs) {
	return $lhs[0] == $rhs[0] && $lhs[5] == $rhs[5];
};

for ($i = 0; $i < $n_people; $i++) {
	if ($i == 0 || !$is_same_rank($standings[$i - 1], $standings[$i])) {
		$standings[$i][3] = $i + 1;
	} else {
		$standings[$i][3] = $standings[$i - 1][3];
	}
}
