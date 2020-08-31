<?php

function isGroupAssociated($user, $group) {
	return DB::selectFirst("select * from group_members where group_name = '{$group['group_name']}' and username = '{$user['username']}'");
}

function isGroupMember($user, $group) {
	$r = isGroupAssociated($user, $group)['member_state'];
	return $r === 'A' or $r === 'U';
}

function isGroupManager($user, $group) {
	return isGroupAssociated($user, $group)['member_state'] === 'A';
}

function isSuperUser($user) {
	return isGroupMember($user, array('group_name' => 'site_manager'));
}

function isProblemManager($user) {
	return isSuperUser($user) or isGroupMember($user, array('group_name' => 'problem_manager'));
}

function isStatementMaintainer($user) {
	return isGroupMember($user, array('group_name' => 'statement_maintainer'));
}

function isBannedUser($user) {
	return isGroupAssociated($user, array('group_name' => 'banned'))['member_state'] === 'U';
}
