<?php

function isGroupAssociated($user, $group) {
	return DB::selectFirst("select * from group_members where group_name = '{$group['group_name']}' and username = '{$user['username']}'");
}

function isGroupMember($user, $group) {
	$r = isGroupAssociated($user, $group)['member_state'];
	return $r === 'A' or $r === 'U';
}

function isGroupManager($user, $group) {
	return isGroupAssociated($user, $group)['member_state'] === 'A';
}

function isSuperUser($user) {
	return isGroupMember($user, array('group_name' => 'site_manager'));
}

function isProblemManager($user) {
	return isSuperUser($user) or isGroupMember($user, array('group_name' => 'problem_manager'));
}

function isStatementMaintainer($user) {
	return isGroupMember($user, array('group_name' => 'statement_maintainer'));
}

function isBannedUser($user) {
	return isGroupAssociated($user, array('group_name' => 'banned'))['member_state'] === 'U';
}

function selectUsersByScript($selector = 'default', $args) {
	$script = 'selector_' . $selector;
	$file = UOJContext::documentRoot() . '/utility/group_scripts/' . $script . '.php';
	if (!(validateUsername($script) and is_file($file))) {
		$file = UOJContext::documentRoot() . '/utility/group_scripts/selector_default.php';
	}
	$result = NULL;
	include $file;
	return $result;
}

function operateUsersByScript($operator = 'default', $args) {
	$script = 'operator_'.$operator;
	$file = UOJContext::documentRoot() . '/utility/group_scripts/' . $script . '.php';
	if (!(validateUsername($script) and is_file($file))) {
		$file = UOJContext::documentRoot() . '/utility/group_scripts/operator_default.php';
	}
	$result = NULL;
	include $file;
	return $result;
}

function getScriptDocument($json) {
	$json = UOJContext::documentRoot() . '/utility/group_scripts/' . $json;
	if (is_file($json)) {
		$res = json_decode(file_get_contents($json), true);
		if (isset($res['document'])) {
			return $res['document'];
		}
	}
	return array("No such document.");
}

function getGroupScripts($path) {
	$path = rtrim($path, '/');
	$dir = UOJContext::documentRoot() . '/' . $path;
	$files = array_values(array_filter(
		scandir($dir), function ($x) use ($dir) {
			return pathinfo($x, PATHINFO_EXTENSION) === 'php' && is_file($dir . '/' . $x);
		}
	));
	natsort($files);
	$ret = array(
		'selector' => array(),
		'operator' => array()
	);
	$pattern_len = strlen('selector_');
	foreach ($files as $file) {
		$arr = pathinfo($file);
		$type = 0;
		if (strncmp($file, 'selector_' , $pattern_len) === 0)
			$type = 1;
		else if (strncmp($file, 'operator_' , $pattern_len) === 0)
			$type = 2;
		if ($type == 0) continue;
		$realname = substr($arr['filename'], $pattern_len);
		$conf = array(
			'filename' => $realname,
			'description' => $realname,
			'jsonpath' => $arr['filename'] . '.json'
		);
		$path_to_json = $dir . '/' . $arr['filename'] . '.json';
		if (is_file($path_to_json)) {
			$res = json_decode(file_get_contents($path_to_json), true);
			if (isset($res['description'])) {
				$conf['description'] = $res['description'];
			}
		}
		if ($type == 1) {
			$ret['selector'][] = $conf;
		} else {
			$ret['operator'][] = $conf;
		}
	}
	return $ret;
}