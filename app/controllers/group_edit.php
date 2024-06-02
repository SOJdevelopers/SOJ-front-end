<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!validateUsername($_GET['username']) || !($group = queryGroup($_GET['username']))) {
		become404Page();
	}

	if (!(isGroupMember(Auth::user(), $group) || isSuperUser(Auth::user()))) {
		become403Page();
	}
/*
	Joinable tags:
	'A' : Join Directly
	'C' : Need verify by managers
	'N' : Forbidden
	------
	Member states:
	'A' : Manager
	'U' : Common user
	'W' : Pending verifying
*/
	if (DB::selectFirst("select * from contests_registrants where username = '{$group['group_name']}' and exists (select 1 from contests where contests.id = contests_registrants.contest_id and contests.status != 'finished')")) {
		becomeMsgPage('<h2>此组已经报名比赛，暂时无法修改信息</h2><p>组 ' . $group['group_name'] . ' 已经报名了未结束的比赛，因此无法修改信息。如果比赛尚未开始，请先退出报名，等信息确认后重新报名。</p>');
	}

	$is_operatable = isGroupManager(Auth::user(), $group) || isSuperUser(Auth::user());

	function handlePost() {
		global $group, $is_operatable;
		if (!$is_operatable) {
			return 'Wow! hacker! T_T....';
		}

		if (validateMotto($_POST['description'])) {
			$dp = DB::escape($_POST['description']);
			DB::update("update group_info set description = '$dp' where group_name = '{$group['group_name']}'");
		}

		if (validateMotto($_POST['avatar'])) {
			$av = DB::escape($_POST['avatar']);
			DB::update("update group_info set avatar = '$av' where group_name = '{$group['group_name']}'");
		}

		if ($_POST['joinable'] === 'A' or $_POST['joinable'] === 'C' or $_POST['joinable'] === 'N') {
			if ($group['joinable'] === 'C') {
				if ($_POST['joinable'] === 'A') {
					DB::update("update group_members set member_state = 'U' where group_name = '{$group['group_name']}' and member_state = 'W'");
				} elseif ($_POST['joinable'] === 'N') {
					DB::update("delete from group_members where group_name = '{$group['group_name']}' and member_state = 'W'");
				}
			}
			DB::update("update group_info set joinable = '{$_POST['joinable']}' where group_name = '{$group['group_name']}'");
		}

		return 'ok';
	}

	if (isset($_POST['change'])) {
		die(handlePost());
	}

	if ($is_operatable) {
		$members_form = newAddDelCmdForm('members',
			function($username, $type) {
				global $group;
				if ($type === '+') {
					if ($username[0] === '+') $username = trim(substr($username, 1));
					if (!strcasecmp($username, Auth::id())) return '为安全起见，不可以修改自己的属性';
					if (!($u = queryUser($username))) return "不存在名为 {$username} 的用户";
					return isGroupAssociated($u, $group) || isSuperUser(Auth::user()) ? '' : '非全站管理员不允许拉入未申请的组外用户';
				} elseif ($type === '-') {
					if (!strcasecmp($username, Auth::id())) return '为安全起见，不可以修改自己的属性';
					if (!($u = queryUser($username))) return "不存在名为 {$username} 的用户";
					return '';
				}
				return 'Wow! hacker! T_T....';
			},
			function($type, $username) {
				global $group;
				if ($type === '+') {
					if ($username['0'] === '+') {
						$member_state = 'A';
						$username = substr($username, 1);
					} else {
						$member_state = 'U';
					}
					$username = queryUser($username)['username'];
					DB::insert("insert into group_members (group_name, username, member_state) values ('{$group['group_name']}', '$username', '$member_state') on duplicate key update member_state = '$member_state'");
				} elseif ($type === '-') {
					$username = queryUser($username)['username'];
					DB::delete("delete from group_members where group_name = '{$group['group_name']}' and username = '$username'");
				}
			}
		);

		$members_form->runAtServer();
	}

	$REQUIRE_LIB['dialog'] = '';
	$REQUIRE_LIB['md5'] = '';
?>
<?php echoUOJPageHeader(UOJLocale::get('edit group')) ?>

<h2 class="page-header"><?= UOJLocale::get('edit group') ?></h2>

<h3><?= UOJLocale::get('basic profile') ?></h3>

<form id="form-update" class="form-horizontal">
	<div id="div-description" class="form-group">
		<label for="input-description" class="col-sm-2 control-label"><?= UOJLocale::get('description') ?></label>
		<div class="col-sm-3">
			<textarea class="form-control" id="input-description" name="description"><?= HTML::escape($group['description']) ?></textarea>
			<span class="help-block" id="help-description"></span>
		</div>
	</div>
	<div id="div-avatar" class="form-group">
		<label for="input-avatar" class="col-sm-2 control-label"><?= UOJLocale::get('group avatar') ?></label>
		<div class="col-sm-3">
			<input type="text" class="form-control" name="avatar" id="input-avatar" value="<?= $group['avatar'] ?>" maxlength="100" />
			<span class="help-block" id="help-avatar"></span>
		</div>
	</div>
	<div id="div-joinable" class="form-group">
		<label for="input-joinable" class="col-sm-2 control-label"><?= UOJLocale::get('joinable') ?></label>
		<div class="col-sm-3">
			<select class="form-control" id="input-joinable" name="joinable">
				<option value="A"<?= $group['joinable'] === 'A' ? ' selected="selected"' : ''?>><?= UOJLocale::get('join A') ?></option>
				<option value="C"<?= $group['joinable'] === 'C' ? ' selected="selected"' : ''?>><?= UOJLocale::get('join C') ?></option>
				<option value="N"<?= $group['joinable'] === 'N' ? ' selected="selected"' : ''?>><?= UOJLocale::get('join N') ?></option>
			</select>
		</div>
	</div>
<?php if ($is_operatable) { ?>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-3">
			<button type="submit" id="button-submit" class="btn btn-default"><?= UOJLocale::get('submit') ?></button>
		</div>
	</div>
<?php } ?>
</form>

<?php if ($is_operatable) { ?>
<script type="text/javascript">
	function submitUpdatePost() {
		$.post('/group/<?= $group['group_name'] ?>/edit', {
			change       : '',
			description  : $('#input-description').val(),
			avatar       : $('#input-avatar').val(),
			joinable     : $('#input-joinable').val(),
		}, function(msg) {
			if (msg == 'ok') {
				BootstrapDialog.show({
					title   : '修改成功',
					message : '组信息修改成功',
					type    : BootstrapDialog.TYPE_SUCCESS,
					buttons : [{
						label: '好的',
						action: function(dialog) {
							dialog.close();
						}
					}],
					onhidden : function(dialog) {
						window.location.href = '/group/<?= $group['group_name'] ?>';
					}
				});
			} else {
				BootstrapDialog.show({
					title   : '修改失败',
					message : msg,
					type    : BootstrapDialog.TYPE_DANGER,
					buttons: [{
						label: '好的',
						action: function(dialog) {
							dialog.close();
						}
					}],
				});
			}
		});
	}
	$(document).ready(function(){
		$('#form-update').submit(function(e) {submitUpdatePost(), e.preventDefault();});
	});
</script>
<?php } ?>

<h3><?= UOJLocale::get('group members') ?></h3>

<?php
	$gm_header = '<tr>';
	$gm_header .= '<th>' . UOJLocale::get('username') . '</th>';
	$gm_header .= '<th>' . UOJLocale::get('member type') . '</th>';
	$gm_header .= '</tr>';
	$cond = "group_name = '{$group['group_name']}'";
	if (!$is_operatable) {
		$cond .= ' and member_state != \'W\'';
	}

	echoLongTable(array('username', 'member_state'), 'group_members', $cond, 'order by member_state asc, username asc', $gm_header, function($row) {
			if ($row['member_state'] === 'A') {
				echo '<tr class="info">';
			} elseif ($row['member_state'] === 'W') {
				echo '<tr class="warning">';
			} else {
				echo '<tr>';
			}
			echo '<td>', getUserLink($row['username']), '</td>';
			echo '<td>', UOJLocale::get('ms ' . $row['member_state']), '</td>';
			echo '</tr>';
		}, array('page_len' => 50));

?>

<?php if ($is_operatable) { ?>
<p class="text-center">命令格式：命令一行一个，+root 表示把 root 加入该组，-root 表示把 root 移出改组，++root 表示将 root 设为组管理员</p>
<?php
		$members_form->printHTML();
	}
?>

<?php echoUOJPageFooter() ?>
