<?php

function validateUsername($username) {
	return is_string($username) && preg_match('/^[a-zA-Z0-9_]{1,20}$/', $username);
}

function validatePassword($password) {
	return is_string($password) && preg_match('/^[a-z0-9]{32}$/', $password);
}

function validateEmail($email) {
	return is_string($email) && strlen($email) <= 50 && preg_match('/^(.+)@(.+)$/', $email);
}

function validateQQ($QQ) {
	return is_string($QQ) && strlen($QQ) <= 15 && preg_match('/^[0-9]{5,15}$/', $QQ);
}

function validateMotto($motto) {
	return is_string($motto) && ($len = mb_strlen($motto, 'UTF-8')) !== false && $len <= 100;
}

function validateRealname($realname) {
	return is_string($realname) && preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{0,50}$/u', $realname);
}

function validateUInt($x) { // [0, 1000000000)
	return is_string($x) and ($x === '0' or preg_match('/^[1-9][0-9]{0,8}$/', $x));
}

function validateInt($x) {
	return is_string($x) and validateUInt($x[0] === '-' ? substr($x, 1) : $x);
}

function validateUReal($x) {
    return is_string($x) and preg_match('/^(0|[1-9][0-9]{0,8})(\.[0-9]{0,6})?$/', $x);
}

function validateReal($x) {
	return is_string($x) and validateUReal($x[0] === '-' ? substr($x, 1) : $x);
}

function validateUploadedFile($name) {
	return isset($_FILES[$name]) && is_uploaded_file($_FILES[$name]['tmp_name']);
}

function validateIP($ip) {
	return filter_var($ip, FILTER_VALIDATE_IP) !== false;
}
