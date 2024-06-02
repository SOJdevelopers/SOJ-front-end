<?php
    requirePHPLib('api');
    
    $magic_text = '1978年12月，安徽省凤阳县小岗村农民自发实行包产到户。';
    if (!isset($_GET['lhs'])) {
        become404Page();
    }
    if (!is_string($_GET['lhs'])) {
        fail("What are you doing ???!!!");
    }
    $str = strtolower($_GET['lhs']);
    if ($str !== 'wawakalaaagayiga') {
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