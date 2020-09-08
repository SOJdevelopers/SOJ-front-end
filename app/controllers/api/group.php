<?php
	requirePHPLib('judger');

	validateAll();
	
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
