<?php
    requirePHPLib("api");

    validateAll();
    
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