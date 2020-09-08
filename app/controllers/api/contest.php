<?php
    requirePHPLib("api");

    validateAll();
    
    if (!isset($_GET['id'])) fail('id: Field should not be empty');
    if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) fail("id: Contest with id '{$_GET['id']}' not found");
    genMoreContestInfo($contest);

    $conf = array('show_estimate_result' => true);
    if (check_parameter_on('all')) {
        $conf['verbose'] = true;
    }

    $contest_data = queryContestData($contest, $conf);
    calcStandings($contest, $contest_data, $score, $standings);
    $ret = array(
        'standings' => $standings,
        'score' => $score,
        'problems' => $contest_data['problems'],
        'full_scores' => $contest_data['full_scores']
    );

    if (isset($conf['verbose'])) {
        $ret['submissions'] = $contest_data['data'];
    }

    die_json(json_encode(array(
        'status' => 'ok',
        'result' => $ret
    )));