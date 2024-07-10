<?php
	if (!isset($_GET['p'])) {
		become404Page();
	}
	function resetPassword() {
		list($username, $check_code) = explode('.', base64url_decode($_GET['p']));
		if (!isset($_POST['newPW']) || !validatePassword($_POST['newPW'])) {
			return '操作失败，无效密码';
		}
		if (!isset($username) || !validateUsername($username)) {
			return '不明错误';
		}
		if (!isset($check_code)) {
			return '不明错误';
		}
		
		$newPW = $_POST['newPW'];
		$user = queryUser($username);
		if ($user == null) {
			return '不明错误';
		}
		if ($check_code !== md5($user['username'] . '+' . $user['password'])) {
			return '不明错误';
		}
		$newPW = getPasswordToStore($newPW, $user['username']);
		DB::update("update user_info set password = '$newPW' where username = '{$user['username']}'");
		return 'ok';
	}
	if (isset($_POST['reset'])) {
		die(resetPassword());
	}
	list($username, $check_code) = explode('.', base64url_decode($_GET['p']));
	if (!isset($username) || !validateUsername($username)) {
		become404Page();
	}
	if (!isset($check_code)) {
		become404Page();
	}
	$user = queryUser($username);
	if ($user == null) {
		become404Page();
	}
	if ($check_code !== md5($user['username'] . '+' . $user['password'])) {
		become404Page();
	}
?>
<?php
	$REQUIRE_LIB['dialog'] = '';
	$REQUIRE_LIB['md5'] = '';
?>
<?php echoUOJPageHeader(UOJLocale::get('reset password')) ?>
<h2><?= UOJLocale::get('reset password') ?></h2>
<form id="form-reset" class="form-horizontal">
	<div id="div-password" class="form-group">
		<label for="input-password" class="col-sm-2 control-label"><?= UOJLocale::get('new password') ?></label>
		<div class="col-sm-3">
			<input type="password" class="form-control" id="input-password" name="password" placeholder="<?= UOJLocale::get('enter your new password') ?>" maxlength="20" />
			<input type="password" class="form-control top-buffer-sm" id="input-confirm_password" placeholder="<?= UOJLocale::get('re-enter your new password') ?>" maxlength="20" />
			<span class="help-block" id="help-password"></span>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-3">
			<button type="submit" id="button-submit" class="btn btn-default"><?= UOJLocale::get('submit') ?></button>
		</div>
	</div>
</form>

<script type="text/javascript">
function validateResetPwPost() {
	var ok = true;
	ok &= getFormErrorAndShowHelp('password', validateSettingPassword);
	return ok;
}
$(document).ready(function() {
	$('#form-reset').submit(function(e) {
		if (!validateResetPwPost()) {
			return false;
		}
		$.post('<?= UOJContext::requestURI() ?>', {
			reset : '',
			newPW : md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>")
		}, function(res) {
			if (res == 'ok') {
				BootstrapDialog.show({
					title   : '提示',
					message : '密码更改成功',
					type    : BootstrapDialog.TYPE_SUCCESS,
					buttons: [{
						label: '好的',
						action: function(dialog) {
							dialog.close();
						}
					}],
					onhidden : function(dialog) {
						window.location.href = '/login';
					}
				});
			} else {
				BootstrapDialog.show({
					title   : '提示',
					message : res,
					type	: BootstrapDialog.TYPE_DANGER,
					buttons: [{
							label: '好的',
							action: function(dialog) {
								dialog.close();
							}
					}]
				});
			}
		});
		return false;
	});
});
</script>
<?php echoUOJPageFooter() ?>
