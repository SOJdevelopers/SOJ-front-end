<?php
	requirePHPLib('judger');
	
	if (!authenticateJudger()) {
		become404Page();
	}
	$id = $_GET['id'];
    if (!validateUInt($id) || !($problem = queryProblemBrief($id))) {
        become404Page();
    }
	if (queryJudgerDataNeedUpdate($id)) {
        $esc_judger_name = DB::escape($_POST['judger_name']);
        DB::insert("insert into judger_data_sync (judger_name, problem_id) values ('$esc_judger_name', $id)");
    }
    die('success');
?>
