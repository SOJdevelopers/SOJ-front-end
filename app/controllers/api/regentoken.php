<?php
    requirePHPLib("api");

    $curUser = validateAll()["user"];

    $svn_pw = uojRandString(10);

    DB::insert("update user_info set svn_password='$svn_pw' where username='{$curUser["username"]}'");
    
    die_json(json_encode(array(
		'status' => 'ok',
        'token' => $svn_pw
	)));
