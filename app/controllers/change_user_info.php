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

		$email = $_POST['email'];
		if (!validateEmail($email))
		{
			return '失败：无效电子邮箱。';
		}
		$esc_email = DB::escape($email);
		DB::update("update user_info set email = '$esc_email' where username = '{$username}'");

		if ($_POST['Qtag'])
		{
			$qq = $_POST['qq'];
			if (!validateQQ($qq)) return "失败：无效 QQ。";
			DB::update("update user_info set qq = '$qq' where username = '{$username}'");
		}
		else
			DB::update("update user_info set QQ = NULL where username = '{$username}'");
		if ($_POST['sex'] == 'U' || $_POST['sex'] == 'M' || $_POST['sex'] == 'F')
		{
			$sex = $_POST['sex'];
			DB::update("update user_info set sex = '$sex' where username = '{$username}'");
		}

		if (validateMotto($_POST['motto'])) {
			$esc_motto = DB::escape($_POST['motto']);
			DB::update("update user_info set motto = '$esc_motto' where username = '{$username}'");
		}

		if (validateUInt($_POST['about_me'])) {
			$about_me = $_POST['about_me'];
			$blog_id = (int)$about_me;
			if ($blog_id) {
				if (!DB::selectFirst("select poster from blogs where id = '$blog_id' and poster = '{$username}'")) return "失败：非本人博客";
			}
			DB::update("update user_info set about_me = '$blog_id' where username = '{$username}'");
		}
		if (validateRealname($_POST['real_name'])) {
			$esc_name = DB::escape($_POST['real_name']);
			DB::update("update user_info set real_name = '$esc_name' where username = '{$username}'");
		}
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
	<div id="div-email" class="form-group">
		<label for="input-email" class="col-sm-2 control-label"><?= UOJLocale::get('email') ?></label>
		<div class="col-sm-3">
			<input type="email" class="form-control" name="email" id="input-email" value="<?= Auth::user()['email'] ?>" placeholder="<?= UOJLocale::get('enter your email') ?>" maxlength="50" />
			<span class="help-block" id="help-email"></span>
		</div>
	</div>
	<div id="div-qq" class="form-group">
		<label for="input-qq" class="col-sm-2 control-label"><?= UOJLocale::get('QQ') ?></label>
		<div class="col-sm-3">
			<input type="text" class="form-control" name="qq" id="input-qq" value="<?= Auth::user()['qq'] != 0 ? Auth::user()['qq'] : '' ?>" placeholder="<?= UOJLocale::get('enter your QQ') ?>" maxlength="50" />
			<span class="help-block" id="help-qq"></span>
		</div>
	</div>
	<div id="div-sex" class="form-group">
		<label for="input-sex" class="col-sm-2 control-label"><?= UOJLocale::get('sex') ?></label>
		<div class="col-sm-3">
			<select class="form-control" id="input-sex" name="sex">
				<option value="U"<?= Auth::user()['sex'] == 'U' ? ' selected="selected"' : ''?>><?= UOJLocale::get('refuse to answer') ?></option>
				<option value="M"<?= Auth::user()['sex'] == 'M' ? ' selected="selected"' : ''?>><?= UOJLocale::get('male') ?></option>
				<option value="F"<?= Auth::user()['sex'] == 'F' ? ' selected="selected"' : ''?>><?= UOJLocale::get('female') ?></option>
			</select>
		</div>
	</div>
	<div id="div-motto" class="form-group">
		<label for="input-motto" class="col-sm-2 control-label"><?= UOJLocale::get('motto') ?></label>
		<div class="col-sm-3">
			<textarea class="form-control" id="input-motto" name="motto"><?= HTML::escape(Auth::user()['motto']) ?></textarea>
			<span class="help-block" id="help-motto"></span>
		</div>
	</div>
	<div id="div-about-me" class="form-group">
		<label for="input-about-me" class="col-sm-2 control-label"><?= UOJLocale::get('about me blog') ?></label>
		<div class="col-sm-3">
			<input type="text" class="form-control" name="about-me" id="input-about-me" value="<?= Auth::user()['about_me'] ?>" placeholder="<?= UOJLocale::get('enter about me') ?>" maxlength="50" />
			<span class="help-block" id="help-about-me"><?= UOJLocale::get('fill 0 if you dont want to show') ?></span>
		</div>
	</div>
	<div id="div-real-name" class="form-group">
		<label for="input-real-name" class="col-sm-2 control-label"><?= UOJLocale::get('realname') ?></label>
		<div class="col-sm-3">
			<input type="text" class="form-control" name="real-name" id="input-real-name" value="<?= Auth::user()['real_name'] ?>" placeholder="<?= UOJLocale::get('enter your realname') ?>" maxlength="50" />
			<span class="help-block" id="help-real-name"></span>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-3">
			<p class="form-control-static"><strong><?= UOJLocale::get('change avatar help') ?></strong></p>
		</div>
	</div>
	<div class="form-group">
		<div class="col-sm-offset-2 col-sm-3">
			<button type="submit" id="button-submit" class="btn btn-default"><?= UOJLocale::get('submit') ?></button>
		</div>
	</div>
</form>

<script type="text/javascript">
	function validateUpdatePost() {
		var ok = true;
		ok &= getFormErrorAndShowHelp('email', validateEmail);
		ok &= getFormErrorAndShowHelp('old_password', validatePassword);
		if ($('#input-password').val().length > 0)
			ok &= getFormErrorAndShowHelp('password', validateSettingPassword);
		if ($('#input-qq').val().length > 0)
			ok &= getFormErrorAndShowHelp('qq', validateQQ);
		ok &= getFormErrorAndShowHelp('motto', validateMotto);
		ok &= getFormErrorAndShowHelp('about-me', validateAboutMe);
		ok &= getFormErrorAndShowHelp('real-name', validateRealname);
		return ok;
	}
	function submitUpdatePost() {
		if (!validateUpdatePost())
			return;
		$.post('/user/modify-profile', {
			change       : '',
			etag         : $('#input-email').val().length,
			ptag         : $('#input-password').val().length,
			Qtag         : $('#input-qq').val().length,
			email        : $('#input-email').val(),
			password     : md5($('#input-password').val(), "<?= getPasswordClientSalt() ?>"),
			old_password : md5($('#input-old_password').val(), "<?= getPasswordClientSalt() ?>"),
			qq           : $('#input-qq').val(),
			sex          : $('#input-sex').val(),
			motto        : $('#input-motto').val(),
			about_me     : $('#input-about-me').val(),
			real_name    : $('#input-real-name').val()
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

