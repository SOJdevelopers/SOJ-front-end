<?php
	requirePHPLib('judger');

	/*	return
		{"status" : "ok", "result" : xxx}
		{"status" : "failed", "comment" : xxx}
	*/

	function die_json($ret) {
		header('Content-type: application/json');
		if (isset($_GET['callback']) && is_string($_GET['callback'])) {
			$ret = $_GET['callback'] . '(' . $ret . ');';
		}
		die($ret);
	}

	function fail($comment) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 400 Bad Request', true, 400);
		die_json(json_encode(array(
			'status' => 'failed',
			'comment' => $comment
		)));
	}

	function check_parameter_on($key) {
		return isset($_GET[$key]) and !strcasecmp($_GET[$key], 'true');
	}
 
	$cur_tab = $_GET['tab'];

	$tabs_info = array('user' => '', 'problem' => '', 'contest' => '', 'group' => '', 'hidden' => '');

	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}

	if ($cur_tab === 'hidden') {
		$magic_text = '1978年12月，安徽省凤阳县小岗村农民自发实行包产到户。';
		if (!isset($_GET['lhs'])) {
			become404Page();
		}
		if ($_GET['lhs'] !== '181') {
			fail("What are you doing ???!!!");
		} else {
			die_json(json_encode(array(
				'status' => 'ok',
				'result' => array(
					'encoding' => 'UTF-8',
					'hashing' => 'MD5',
					'text' => $magic_text,
					'then submit' => 'Gain the first blood!'
				)
			), JSON_UNESCAPED_UNICODE));
		}
	}

	// validate `user`

	if (!isset($_GET['user'])) {
		fail('user: Parameter \'user\' missing; onlyOnline: You have to be authenticated to use \'API\' methods');
	}

	if (!($curUser = queryUser($_GET['user']))) {
		fail("user: Incorrect user. User with id '{$_GET['user']}' not found; onlyOnline: You have to be authenticated to use 'API' methods");
	}

	// validate `time`

	if (!isset($_GET['time'])) {
		fail('time: Parameter \'time\' missing; onlyOnline: You have to be authenticated to use \'API\' methods');
	}

	if (!is_numeric($_GET['time'])) {
		fail("time: Invalid format for parameter 'time', '{$_GET['time']}' is not a valid number; onlyOnline: You have to be authenticated to use 'API' methods");
	}

	$get_time = (int)$_GET['time'];
	$server_time = UOJTime::$time_now->getTimestamp();

	if (!(abs($get_time - $server_time) <= 1800)) {
		fail("time: Specified time {$get_time} is not within 1800 seconds before or after current server time {$server_time}; onlyOnline: You have to be authenticated to use 'API' methods");
	}

	// validate `sign`

	if (!isset($_GET['sign'])) {
		fail('signature: Parameter \'sign\' missing; onlyOnline: You have to be authenticated to use \'API\' methods');
	}

	$query_string = strtolower($curUser['username']) . '#' . $get_time . '#' . $curUser['svn_password'];

	if (!is_string($_GET['sign']) or strcasecmp($_GET['sign'], md5($query_string))) {
		fail('signature: Incorrect signature; onlyOnline: You have to be authenticated to use \'API\' methods');
	}

	// authenticated !

	if ($cur_tab === 'user') {
		if (!isset($_GET['id'])) fail('id: Field should not be empty');
		if (is_array($_GET['id'])) {
			foreach ($_GET['id'] as $i => $id)
				if (!queryUser($id)) fail("id: User with id '{$id}' not found (at {$i})");
		} else {
			if (!queryUser($_GET['id'])) fail("id: User with id '{$_GET['id']}' not found");
		}
		$ids = (is_array($_GET['id']) ? $_GET['id'] : array($_GET['id']));
		$ret = array();
		foreach ($ids as $id) {
			$hisUser = queryUser($id);
			$cur = array(
				'username' => $hisUser['username'],
				'email' => $hisUser['email'],
				'rating' => (int)$hisUser['rating'],
				'sex' => $hisUser['sex'],
				'ac_num' => (int)$hisUser['ac_num'],
				'motto' => $hisUser['motto'],
				'avatar' => HTML::avatar_addr($hisUser, 256)
			);
			if ($hisUser['qq'] != 0) $cur['qq'] = (int)$hisUser['qq'];
			if (isSuperUser($curUser)) {
				$cur['real_name'] = $hisUser['real_name'];
				$cur['register_time'] = $hisUser['register_time'];
				$cur['remote_addr'] = $hisUser['remote_addr'];
				$cur['http_x_forwarded_for'] = $hisUser['http_x_forwarded_for'];
				$cur['latest_active'] = $hisUser['latest_login'];
			}
			if (check_parameter_on('group')) {
				$cur['group'] = array();
				$gs = DB::select("select group_name from group_members where username = '{$hisUser['username']}' and member_state != 'W'");
				while ($g = DB::fetch($gs)) {
					$cur['group'][] = $g['group_name'];
				}
			}
			$ret[] = $cur;
		}
		if (!is_array($_GET['id'])) {
			$ret = $ret[0];
		}
		die_json(json_encode(array(
			'status' => 'ok',
			'result' => $ret
		)));
	} elseif ($cur_tab === 'problem') {
		if (!isset($_GET['id'])) fail('id: Field should not be empty');
		if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) fail("id: Problem with id '{$_GET['id']}' not found");
		if (!isProblemVisibleToUser($problem, $curUser)) fail("id: You have no permission to view problem #{$_GET['id']}");
		$problem_content = queryProblemContent($problem['id']);
		$ret = array(
			'id' => (int)$problem['id'],
			'title' => $problem['title'],
			'appraisal' => (int)$problem['zan'],
			'ac_num' => (int)$problem['ac_num'],
			'submit_num' => (int)$problem['submit_num'],
			'content' => $problem_content['statement'],
			'content_md' => $problem_content['statement_md']
		);
		if (hasProblemPermission($curUser, $problem)) {
			$ret['is_hidden'] = (bool)$problem['is_hidden'];
			$ret['hackable'] = (bool)$problem['hackable'];
			$ret['submission_requirement'] = getProblemSubmissionRequirement($problem);
			$ret['extra_config'] = getProblemExtraConfig($problem);
			if (check_parameter_on('config')) {
				$problem_conf = getUOJConf("/var/uoj_data/{$problem['id']}/problem.conf");
				if ($problem_conf !== -1 && $problem_conf !== -2) $ret['problem_conf'] = $problem_conf;
			}
		}
		die_json(json_encode(array(
			'status' => 'ok',
			'result' => $ret
		)));
	} elseif ($cur_tab === 'contest') {
		if (!isset($_GET['id'])) fail('id: Field should not be empty');
		if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) fail("id: Contest with id '{$_GET['id']}' not found");
		genMoreContestInfo($contest);

		$conf = array('show_estimate_result' => true);
		if (check_parameter_on('all')) {
			$conf['verbose'] = true;
		}

		$contest_data = queryContestData($contest, $conf);
		calcStandings($contest, $contest_data, $score, $standings);
		$ret = array(
			'standings' => $standings,
			'score' => $score,
			'problems' => $contest_data['problems'],
			'full_scores' => $contest_data['full_scores']
		);

		if (isset($conf['verbose'])) {
			$ret['submissions'] = $contest_data['data'];
		}

		die_json(json_encode(array(
			'status' => 'ok',
			'result' => $ret
		)));
	} elseif ($cur_tab === 'group') {
		if (!isset($_GET['name'])) fail('name: Field should not be empty');
		if (is_array($_GET['name'])) {
			foreach ($_GET['name'] as $i => $name)
				if (!queryGroup($name)) fail("name: Group with name '{$name}' not found (at {$i})");
		} else {
			if (!queryGroup($_GET['name'])) fail("name: Group with name '{$_GET['name']}' not found");
		}
		$names = (is_array($_GET['name']) ? $_GET['name'] : array($_GET['name']));
		$ret = array();
		foreach ($names as $name) {
			$g = queryGroup($name);
			$cur = array(
				'name' => $g['group_name'],
				'description' => $g['description'],
				'avatar' => $g['avatar'],
				'rating' => (int)$g['rating'],
				'joinable' => $g['joinable']
			);
			if (check_parameter_on('member')) {
				$cur['member'] = array();
				$us = DB::select("select username from group_members where group_name = '{$g['group_name']}' and member_state != 'W'");
				while ($u = DB::fetch($us)) {
					$cur['member'][] = $u['username'];
				}
			}
			$ret[] = $cur;
		}
		if (!is_array($_GET['name'])) {
			$ret = $ret[0];
		}
		die_json(json_encode(array(
			'status' => 'ok',
			'result' => $ret
		)));
	}
