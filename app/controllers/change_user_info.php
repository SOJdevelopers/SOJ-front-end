<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	if (isset($_POST['regenerate_apipassword'])) {
		$username = Auth::id();
		$passwd = uojRandString(10);
		DB::update("update user_info set svn_password='$passwd' where username='{$username}'");
		die('ok');
	}

	function handlePost() {
		$username = Auth::id();
		if (!isset($_POST['old_password']))
		{
			return '无效表单';
		}
		$old_password = $_POST['old_password'];
		if (!validatePassword($old_password) || !checkPassword(Auth::user(), $old_password))
		{
			return '失败：密码错误。';
		}
		if ($_POST['ptag'])
		{
			$password = $_POST['password'];
			if (!validatePassword($password))
			{
				return '失败：无效密码。';
			}
			$password = getPasswordToStore($password, $username);
			DB::update("update user_info set password = '$password' where username = '{$username}'");
		}
		$conf = array();
		foreach (UOJConfig::$user as $seg => $data) {
			if ($data['allow_empty'] === false && !$_POST[$seg]) {
				return '失败：字段 ' . $seg . ' 为空';
			}
			$val = $_POST[$seg];
			if ($val) {
				if (isset($data['validator_php'])) {
					$res = $data['validator_php']($val);
					if ($res === false) {
						return '失败：字段 ' . $seg . ' 不合法';
					} else if ($res !== true) {
						return $res;
					}
				}
				$conf[$seg] = $val;
			}
		}
		$conf_s = json_encode($conf);
		DB::update("update user_info set extra_config='" . DB::escape($conf_s) . "' where username = '{$username}'");
		return 'ok';
	}
	if (isset($_POST['change'])) {
		die(handlePost());
	}

	$REQUIRE_LIB['dialog'] = '';
	$REQUIRE_LIB['md5'] = '';
?>
<?php echoUOJPageHeader(UOJLocale::get('modify my profile')) ?>
<h2 class="page-header"><?= UOJLocale::get('modify my profile') ?></h2>
<form id="form-update" class="form-horizontal">
	<h4><?= UOJLocale::get('please enter your password for authorization') ?></h4>
	<div id="div-old_password" class="form-group">
		<label for="input-old_password" class="col-sm-2 control-label"><?= UOJLocale::get('password') ?></label>
		<div class="col-sm-3">
			<input type="password" class="form-control" name="old_password" id="input-old_password" placeholder="<?= UOJLocale::get('enter your password') ?>" maxlength="20" />
			<span class="help-block" id="help-old_password"></span>
		</div>
	</div>
	<h4><?= UOJLocale::get('please enter your new profile') ?></h4>
	<div id="div-password" class="form-group">
		<label for="input-password" class="col-sm-2 control-label"><?= UOJLocale::get('new password') ?></label>
		<div class="col-sm-3">
			<input type="password" class="form-control" id="input-password" name="password" placeholder="<?= UOJLocale::get('enter your new password') ?>" maxlength="20" />
			<input type="password" class="form-control top-buffer-sm" id="input-confirm_password" placeholder="<?= UOJLocale::get('re-enter your new password') ?>" maxlength="20" />
			<span class="help-block" id="help-password"><?= UOJLocale::get('leave it blank if you do not want to change the password') ?></span>
		</div>
	</div>
<?php
	$conf = Auth::user()['extra_config'];
	$locale = UOJLocale::locale();
	foreach (UOJConfig::$user as $seg => $data) {
?>
	<div id="div-<?= $seg ?>" class="form-group">
		<label for="input-<?= $seg ?>" class="col-sm-2 control-label"><?= $data['locale'][$locale] ?></label>
		<div class="col-sm-3">
			<?php
			if (isset($data['form'])) {
				echo $data['form']($conf[$seg]);
			} else {
				?>
				<input type="text" class="form-control" name="<?= $seg ?>" id="input-<?= $seg ?>" value="<?= $conf[$seg] ?>" placeholder="<?= (isset($data['placeholder']) ? $data['placeholder'][$locale] : $data['locale'][$locale]) ?>" maxlength="<?= $data['max_length']?>" />
			<?php }	?>
			<span class="help-block" id="help-<?= $seg ?>"></span>
		</div>
	</div>
<?php }	?>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-3">
			<button type="submit" id="button-submit" class="btn btn-default"><?= UOJLocale::get('submit') ?></button>
		</div>
	</div>
</form>

<script type="text/javascript">
	function validateUpdatePost() {
		var ok = true;
		ok &= getFormErrorAndShowHelp('old_password', validatePassword);
		if ($('#input-password').val().length > 0)
			ok &= getFormErrorAndShowHelp('password', validateSettingPassword);
	<?php
		foreach (UOJConfig::$user as $seg => $data) {
			if (isset($data['validator_js'])) {
				if ($data['allow_empty'] !== false)
					echo "if ($('#input-" . $seg . "').val().length > 0)\n";
				echo "ok &= getFormErrorAndShowHelp('" . $seg . "', " . $data['validator_js'] . ");\n";
			}
		}
	?>
		return ok;
	}
	function submitUpdatePost() {
		if (!validateUpdatePost())
			return;
		$.post('/user/modify-profile', {
			change       : '',
			password     : md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>"),
			old_password : md5($('#input-old_password').val(), "<?= getPasswordClientSalt() ?>"),
			<?php
				foreach (UOJConfig::$user as $seg => $data) {
					echo $seg . " : $('#input-" . $seg . "').val(),\n";
				}
			?>
		}, function(msg) {
			if (msg == 'ok') {
				BootstrapDialog.show({
					title   : '修改成功',
					message : '用户信息修改成功',
					type    : BootstrapDialog.TYPE_SUCCESS,
					buttons : [{
						label: '好的',
						action: function(dialog) {
							dialog.close();
						}
					}],
					onhidden : function(dialog) {
						window.location.href = '/user/profile/<?= Auth::id() ?>';
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
<?php echoUOJPageFooter() ?>

