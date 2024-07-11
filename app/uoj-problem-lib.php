<?php
	requirePHPLib('data');

	function addProblemPermission($problem_id, $username, $log_config = array()) {
		insertAuditLog('problems','add permission',$problem_id,isset($log_config['reason'])?$log_config['reason']:'', json_encode(array('username' => $username)), $log_config);
		DB::insert("insert into problems_permissions (problem_id, username) values ({$problem_id}, '$username')");
	}

	function deleteProblemPermission($problem_id, $username, $log_config = array()) {
		insertAuditLog('problems','delete permission',$problem_id,isset($log_config['reason'])?$log_config['reason']:'', json_encode(array('username' => $username)), $log_config);
		DB::delete("delete from problems_permissions where problem_id = {$problem_id} and username = '$username'");
	}

	function addProblemViewPermission($problem_id, $groupname, $log_config = array()) {
		insertAuditLog('problems','add view permission',$problem_id,isset($log_config['reason'])?$log_config['reason']:'', json_encode(array('groupname' => $groupname)), $log_config);
		DB::insert("insert into problems_visibility (problem_id, group_name) values ({$problem_id}, '$groupname')");
	}

	function deleteProblemViewPermission($problem_id, $groupname, $log_config = array()) {
		insertAuditLog('problems','delete view permission',$problem_id,isset($log_config['reason'])?$log_config['reason']:'', json_encode(array('groupname' => $groupname)), $log_config);
		DB::delete("delete from problems_visibility where problem_id = {$problem_id} and group_name = '$groupname'");
	}

	function newProblem($log_config = array()) {
		DB::insert("insert into problems (title, is_hidden, submission_requirement) values ('New Problem', 1, '{}')");
		$id = DB::insert_id();
		insertAuditLog('problems','create',$id,isset($log_config['reason'])?$log_config['reason']:'', '', $log_config);
		DB::insert("insert into problems_contents (id, statement, statement_md) values ({$id}, '', '')");
		DB::insert("insert into problems_visibility (problem_id, group_name) values ({$id}, '" . UOJConfig::$data['profile']['common-group'] . "')");
		if (!isProblemManager(Auth::user()))
			addProblemPermission($id, Auth::id(), array('reason' => 'create problem without manage permission', 'auto' => true));
		dataNewProblem($id);
		return $id;
	}
