<?php

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

function validateUser() {
    if (!isset($_GET['user'])) {
		fail('user: Parameter \'user\' missing; onlyOnline: You have to be authenticated to use \'API\' methods');
	}

	if (!($curUser = queryUser($_GET['user']))) {
		fail("user: Incorrect user. User with id '{$_GET['user']}' not found; onlyOnline: You have to be authenticated to use 'API' methods");
    }
    return $curUser;
}

function validateTime() {
    if (!isset($_GET['time'])) {
		fail('time: Parameter \'time\' missing; onlyOnline: You have to be authenticated to use \'API\' methods');
	}

	if (!is_numeric($_GET['time'])) {
		fail("time: Invalid format for parameter 'time', '{$_GET['time']}' is not a valid number; onlyOnline: You have to be authenticated to use 'API' methods");
	}

	$getTime = (int)$_GET['time'];
	$serverTime = UOJTime::$time_now->getTimestamp();

	if (!(abs($getTime - $serverTime) <= 1800)) {
		fail("time: Specified time {$getTime} is not within 1800 seconds before or after current server time {$serverTime}; onlyOnline: You have to be authenticated to use 'API' methods");
    }
    return $getTime;
}

function validateSign($curUser, $getTime) {
    if (!isset($_GET['sign'])) {
		fail('signature: Parameter \'sign\' missing; onlyOnline: You have to be authenticated to use \'API\' methods');
	}

	$query_string = strtolower($curUser['username']) . '#' . $getTime . '#' . $curUser['svn_password'];

	if (!is_string($_GET['sign']) or strcasecmp($_GET['sign'], md5($query_string))) {
		fail('signature: Incorrect signature; onlyOnline: You have to be authenticated to use \'API\' methods');
	}
}

function validateAll() {
    $curUser = validateUser();
    $getTime = validateTime();
    validateSign($curUser, $getTime);
    return array("user" => $curUser, "time" => $getTime);
}