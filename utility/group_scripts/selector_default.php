<?php
if (!$args || !isset($args))
	$args = 'root';
$result = DB::selectFirst("select rating from user_info where username='{$args}'")["rating"];
?>
