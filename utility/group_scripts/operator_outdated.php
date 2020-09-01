<?php
DB::delete("delete from group_members where group_name='outdated'");
$users = json_decode($args, true);
$pre = "insert into group_members (group_name, username, member_state) values ";
$target = "";
$lst = false;
foreach ($users as $user) {
	if ($lst) $target .= ",";
	$lst = true;
	$target .= "('outdated', '" . $user["username"] . "', 'U')";
}
$result = "Mysql returned: " . DB::insert($pre . $target);
?>
