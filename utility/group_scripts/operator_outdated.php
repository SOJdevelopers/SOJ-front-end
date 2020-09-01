<?php
$users = json_decode($args, true);
$pre = "insert into group_members (group_name, username, member_state) values ";
$target = "";
$lst = false;
foreach ($users as $user) {
	if ($lst) $target .= ",";
	$lst = true;
	$target .= "('outdated', '" . $user["username"] . "', 'U')";
}
$tail = " on duplicate key update member_state = 'U'";
$result = "Mysql returned: " . DB::insert($pre . $target . $tail);
?>
