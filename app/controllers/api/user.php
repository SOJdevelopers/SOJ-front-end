<?php
    requirePHPLib('api');

    $curUser = validateAll()['user'];
    
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
            'rating' => (int)$hisUser['rating'],
            'ac_num' => (int)$hisUser['ac_num'],
            'avatar' => HTML::avatar_addr($hisUser, 256)
        );
        $isAdmin = isSuperUser($curUser);
        $isSelf = $hisUser['username'] == $curUser['username'];
        foreach (UOJConfig::$user as $seg => $data) {
            if ($data['publish'] === true || $isSelf || $isAdmin) {
                $cur[$seg] = $hisUser['extra_config'][$seg];
            }
        }
        if ($isAdmin) {
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