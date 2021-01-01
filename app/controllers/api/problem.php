<?php
    requirePHPLib('api');

    $curUser = validateAll()['user'];

    if (!isset($_GET['id'])) fail('id: Field should not be empty');
    if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) fail("id: Problem with id '{$_GET['id']}' not found");
    if (!isProblemVisible($curUser, $problem)) fail("id: You have no permission to view problem #{$_GET['id']}");
    $problem_content = queryProblemContent($problem['id']);
    $ret = array(
        'id' => (int)$problem['id'],
        'title' => $problem['title'],
        'appraisal' => (int)$problem['zan'],
        'ac_num' => (int)$problem['ac_num'],
        'submit_num' => (int)$problem['submit_num'],
        'content' => $problem_content['statement'],
        'content_md' => $problem_content['statement_md']
    );
    if (hasProblemPermission($curUser, $problem)) {
        $ret['is_hidden'] = (bool)$problem['is_hidden'];
        $ret['hackable'] = (bool)$problem['hackable'];
        $ret['submission_requirement'] = getProblemSubmissionRequirement($problem);
        $ret['extra_config'] = getProblemExtraConfig($problem);
        if (check_parameter_on('config')) {
            $problem_conf = getUOJConf("/var/uoj_data/{$problem['id']}/problem.conf");
            if ($problem_conf !== -1 && $problem_conf !== -2) $ret['problem_conf'] = $problem_conf;
        }
    }
    die_json(json_encode(array(
        'status' => 'ok',
        'result' => $ret
    )));
