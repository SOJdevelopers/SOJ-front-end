<?php
	function validateZan() {
		if (!validateUInt($_POST['id']))
			return false;
		if (!validateInt($_POST['delta']))
			return false;
		if ($_POST['delta'] != 1 && $_POST['delta'] != -1)
			return false;
		if ($_POST['type'] != 'B' && $_POST['type'] != 'BC' && $_POST['type'] != 'P' && $_POST['type'] != 'C')
			return false;
		return true;
	}
	if (!validateZan()) {
		become404Page();
	}
	if (!Auth::check()) {
		die('<div class="text-danger">please <a href="' . HTML::url('/login') . '">login</a></div>');
	}
	if (!isBlogAllowedUser(Auth::user())) {
		die('<div class="text-danger">permission denied</div>');
	}

	$id = $_POST['id'];
	$delta = $_POST['delta'];
	$type = $_POST['type'];

	switch ($type) {
		case 'B':
			$table_name = 'blogs';
			break;
		case 'BC':
			$table_name = 'blogs_comments';
			break;
		case 'P':
			$table_name = 'problems';
			break;
		case 'C':
			$table_name = 'contests';
			break;
	}
	
	$cur = queryZanVal($id, $type, Auth::user());
	
	if ($cur != $delta) {
		$row = DB::selectFirst("select zan from $table_name where id = $id");
		if ($row == null) {
			die('<div class="text-danger">Wow! hacker! T_T....</div>');
		}
		$cur += $delta;
		$username = Auth::id();
		if ($cur == 0) {
			DB::delete("delete from click_zans where username = '{$username}' and type = '$type' and target_id = $id");
		} else if ($cur != $delta) {
			DB::update("update click_zans set val = '$cur' where username = '{$username}' and type = '$type' and target_id = $id");
		} else {
			DB::insert("insert into click_zans (username, type, target_id, val) values ('{$username}', '$type', $id, $cur)");
		}
		$cnt = $row['zan'] + $delta;
		DB::update("update $table_name set zan = $cnt where id = $id");
	} else {
		$row = DB::selectFirst("select zan from $table_name where id = $id");
		if ($row == null) {
			die('<div class="text-danger">Wow! hacker! T_T....</div>');
		}
		$cnt = $row['zan'];
	}
?>
<?= getClickZanBlock($type, $id, $cnt, $cur) ?>
