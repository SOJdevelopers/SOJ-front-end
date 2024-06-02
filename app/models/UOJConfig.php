<?php

class UOJConfig {
	public static $data;
	public static $user;

	public static function mergeDataConfig($extra) {
		mergeConfig(self::$data, $extra);
	}

	public static function mergeUserConfig($extra) {
		mergeConfig(self::$user, $extra);
	}
}

if (is_file($_SERVER['DOCUMENT_ROOT'] . '/app/.config.php')) {
	UOJConfig::$data = include $_SERVER['DOCUMENT_ROOT'] . '/app/.config.php';
	UOJConfig::mergeDataConfig(include $_SERVER['DOCUMENT_ROOT'] . '/app/.default-config.php');
} else {
	UOJConfig::$data = include $_SERVER['DOCUMENT_ROOT'] . '/app/.default-config.php';
}

if (is_file($_SERVER['DOCUMENT_ROOT'] . '/app/.userinfo.php')) {
	UOJConfig::$user = include $_SERVER['DOCUMENT_ROOT'] . '/app/.userinfo.php';
	UOJConfig::mergeUserConfig(include $_SERVER['DOCUMENT_ROOT'] . '/app/.default-userinfo.php');
} else {
	UOJConfig::$user = include $_SERVER['DOCUMENT_ROOT'] . '/app/.default-userinfo.php';
}