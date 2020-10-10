<?php
$ret = DB::selectAll('select username from user_info where not exists (select 1 from group_members where user_info.username = group_members.username)');
$result = json_encode($ret);
