<?php
if (!$args || !isset($args))
	$args = 'root';
$result = DB::selectFirst("select * from user_info where username='{$args}'")["rating"];
?>
