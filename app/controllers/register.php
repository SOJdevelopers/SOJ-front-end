<?php
	function handleRegisterPost() {
		if (!crsf_check()) {
			return '页面已过期';
		}
		if (!isset($_POST['username'])) {
			return '无效表单';
		}
		if (!isset($_POST['password'])) {
			return '无效表单';
		}

		$username = $_POST['username'];
		$password = $_POST['password'];
		if (!validateUsername($username)) {
			return '失败：无效用户名。';
		}
		if (queryUser($username)) {
			return '失败：用户名已存在。';
		}
		if (queryGroup($username)) {
			return '失败：该用户名已被用作组名。';
		}
		if (!validatePassword($password)) {
			return '失败：无效密码。';
		}

		$conf = array();
		foreach (UOJConfig::$user as $seg => $data) if ($data['atregister'] === true) {
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
		foreach (UOJConfig::$user as $seg => $data)
			if (isset($data['default']) && empty($conf[$seg]))
				$conf[$seg] = $data['default'];


		$conf_s = json_encode($conf);
		
		$password = getPasswordToStore($password, $username);
		
		$api_pw = uojRandString(10);
		DB::insert("insert into user_info (username, password, api_password, register_time, latest_login, remote_addr, http_x_forwarded_for) values ('$username', '$password', '$api_pw', now(), now(), '" . DB::escape($_SERVER['REMOTE_ADDR']) . "', '" . DB::escape($_SERVER['HTTP_X_FORWARDED_FOR']) . "')");
		
		DB::insert("insert into group_members (group_name, username, member_state) values ('" . UOJConfig::$data['profile']['default-group'] . "', '{$username}', 'U')");

		DB::update("update user_info set extra_config='" . DB::escape($conf_s) . "' where username = '{$username}'");
		
		return "欢迎你！{$username}，你已成功注册。";
	}
	
	if (isset($_POST['register'])) {
		echo handleRegisterPost();
		die();
	} elseif (isset($_POST['check_username'])) {
		$username = $_POST['username'];
		if (validateUsername($username) && !queryUser($username)) {
			echo '{"ok" : true}';
		} else {
			echo '{"ok" : false}';
		}
		die();
	}
?>
<?php
	$REQUIRE_LIB['md5'] = '';
	$REQUIRE_LIB['dialog'] = '';
?>
<?php echoUOJPageHeader(UOJLocale::get('register')) ?>
<h2><?= UOJLocale::get('register') ?></h2>
<form id="form-register" class="form-horizontal">
	<div id="div-username" class="form-group">
		<label for="input-username" class="col-sm-2 control-label"><?= UOJLocale::get('username') ?></label>
		<div class="col-sm-3">
			<input type="text" class="form-control" id="input-username" name="username" placeholder="<?= UOJLocale::get('enter your username') ?>" maxlength="20" />
			<span class="help-block" id="help-username"></span>
		</div>
	</div>
	<div id="div-password" class="form-group">
		<label for="input-password" class="col-sm-2 control-label"><?= UOJLocale::get('password') ?></label>
		<div class="col-sm-3">
			<input type="password" class="form-control" id="input-password" name="password" placeholder="<?= UOJLocale::get('enter your password') ?>" maxlength="20" />
			<input type="password" class="form-control top-buffer-sm" id="input-confirm_password" placeholder="<?= UOJLocale::get('re-enter your password') ?>" maxlength="20" />
			<span class="help-block" id="help-password"></span>
		</div>
	</div>
<?php
	$locale = UOJLocale::locale();
	foreach (UOJConfig::$user as $seg => $data) if ($data['atregister'] === true) {
?>
	<div id="div-<?= $seg ?>" class="form-group">
		<label for="input-<?= $seg ?>" class="col-sm-2 control-label"><?= $data['locale'][$locale] ?></label>
		<div class="col-sm-3">
			<?php
			if (isset($data['form'])) {
				echo $data['form'](null);
			} else {
				?>
				<input type="text" class="form-control" name="<?= $seg ?>" id="input-<?= $seg ?>" placeholder="<?= (isset($data['placeholder']) ? $data['placeholder'][$locale] : $data['locale'][$locale]) ?>" maxlength="<?= $data['max_length']?>" />
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
function checkUsernameNotInUse() {
	var ok = false;
	$.ajax({
		url : '/register',
		type : 'POST',
		dataType : 'json',
		async : false,
		data : {
			check_username : '',
			username : $('#input-username').val()
		},
		success : function(data) {
			ok = data.ok;
		},
		error :	function(XMLHttpRequest, textStatus, errorThrown) {
			alert(XMLHttpRequest.responseText);
			ok = false;
		}
	});
	return ok;
}
function validateRegisterPost() {
	var ok = true;
	ok &= getFormErrorAndShowHelp('username', function(str) {
		var err = validateUsername(str);
		if (err)
			return err;
		if (!checkUsernameNotInUse())
			return '该用户名已被人使用了。';
		return '';
	})
	ok &= getFormErrorAndShowHelp('password', validateSettingPassword);
	<?php
		foreach (UOJConfig::$user as $seg => $data) {
			if ($data['atregister'] === true && isset($data['validator_js'])) {
				if ($data['allow_empty'] !== false)
					echo "if ($('#input-" . $seg . "').val().length > 0)\n";
				echo "ok &= getFormErrorAndShowHelp('" . $seg . "', " . $data['validator_js'] . ");\n";
			}
		}
	?>
	return ok;
}

function submitRegisterPost() {
	if (!validateRegisterPost()) {
		return;
	}

	$.post('/register', {
		_token : "<?= crsf_token() ?>",
		register : '',
		username : $('#input-username').val(),
		password : md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>"),
		<?php
			foreach (UOJConfig::$user as $seg => $data)
				if ($data['atregister'] === true) {
					echo $seg . " : $('#input-" . $seg . "').val(),\n";
				}
		?>
	}, function(msg) {
		if (/^欢迎你！/.test(msg)) {
			BootstrapDialog.show({
				title	 : '注册成功',
				message : msg,
				type		: BootstrapDialog.TYPE_SUCCESS,
				buttons: [{
					label: '好的',
					action: function(dialog) {
						dialog.close();
					}
				}],
				onhidden : function(dialog) {
					var prevUrl = document.referrer;
					if (!prevUrl) {
						prevUrl = '/';
					};
					window.location.href = prevUrl;
				}
			});
		} else {
			BootstrapDialog.show({
				title	 : '注册失败',
				message : msg,
				type		: BootstrapDialog.TYPE_DANGER,
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
$(document).ready(function() {
	$('#form-register').submit(function(e) {
		submitRegisterPost();
		return false;
	});
});
</script>
<?php echoUOJPageFooter() ?>
