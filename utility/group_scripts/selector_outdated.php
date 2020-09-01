<?php
$ret = DB::selectAll("username from user_info where unix_timestamp(latest_login) < unix_timestamp(DATE_SUB(now(), INTERVAL 180 DAY))");
print_r($ret);
$result = json_encode($ret);
?>
