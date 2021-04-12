<?php
foreach ($args as $frg) {
	for ($i = 1; $i < count($frg); ++$i) {
		DB::insert("update best_ac_submissions set submitter='{$frg[1]}' where submitter='{$frg[0]}'");
		DB::insert("update blogs set poster='{$frg[1]}' where poster='{$frg[0]}'");
		DB::insert("update blogs_comments set poster='{$frg[1]}' where poster='{$frg[0]}'");
		DB::insert("update click_zans set username='{$frg[1]}' where username='{$frg[0]}'");
		DB::insert("update contests_asks set username='{$frg[1]}' where username='{$frg[0]}'");
		DB::insert("update contests_permissions set username='{$frg[1]}' where username='{$frg[0]}'");
		DB::insert("update contests_registrants set username='{$frg[1]}' where username='{$frg[0]}'");
		DB::insert("update contests_submissions set submitter='{$frg[1]}' where submitter='{$frg[0]}'");
		DB::insert("update custom_test_submissions set submitter='{$frg[1]}' where submitter='{$frg[0]}'");
		DB::insert("update group_members set username='{$frg[1]}' where username='{$frg[0]}'");
		DB::insert("update hacks set hacker='{$frg[1]}' where hacker='{$frg[0]}'");
		DB::insert("update hacks set owner='{$frg[1]}' where owner='{$frg[0]}'");
		DB::insert("update problems_permissions set username='{$frg[1]}' where username='{$frg[0]}'");
		DB::insert("update submissions set submitter='{$frg[1]}' where submitter='{$frg[0]}'");
		DB::insert("update user_info set username='{$frg[1]}' where username='{$frg[0]}'");
		DB::insert("update user_msg set sender='{$frg[1]}' where sender='{$frg[0]}'");
		DB::insert("update user_msg set receiver='{$frg[1]}' where receiver='{$frg[0]}'");
		DB::insert("update user_system_msg set receiver='{$frg[1]}' where receiver='{$frg[0]}'");
		$username = $frg[1];
		$password = '123456';
		$password = hash_hmac('md5', $password, getPasswordClientSalt());
		$password = getPasswordToStore($password, $username);
		DB::insert("update user_info set password='$password' where username='$username'");
	}
}
$result = 'CHANGE NAME SUCCESSFULLY';
