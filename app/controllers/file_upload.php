<?php
	requirePHPLib('form');

	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!isCommonUser(Auth::user())) {
		become403Page();
	}

	$allow_rootdir = array(
		'uploads' => '',
		'utility' => '',
		'pictures' => ''
	);
	$root_dir = 'uploads';
	$user_dir = Auth::id();

	function authFile($path) {
		global $allow_rootdir, $root_dir, $user_dir;
		if (!is_string($path)) return 'type error';
		$tokens = explode('/', $path);
		if (!isset($allow_rootdir[$tokens[0]])) return 'invalid path';

		if (!is_file(UOJContext::documentRoot() . '/' . $path)) return 'file not exists';
		if (count($tokens) === 2) array_splice($tokens, 1, 0, '');
		if (count($tokens) !== 3) return 'path format error';

		if (isSuperUser(Auth::user())) {
			$ok = ($tokens[1] === '' or validateUsername($tokens[1]));
		} else {
			$ok = ($tokens[0] === $root_dir and $tokens[1] === $user_dir);
		}

		return $ok && !strStartWith($tokens[2], '.') && preg_match('/^[a-zA-Z0-9_\-\.\x{4e00}-\x{9fa5}]+$/u', $tokens[2])
			?  ''
			:  'no permission';
	}

	function authDir($path) {
		global $allow_rootdir, $root_dir, $user_dir;
		if (!is_string($path)) return 'type error';
		$tokens = explode('/', rtrim($path, '/'));
		if (!isset($allow_rootdir[$tokens[0]])) return 'invalid path';

		if (!is_dir(UOJContext::documentRoot() . '/' . $path)) return 'directory not exists';
		if (count($tokens) === 1) $tokens[1] = '';
		if (count($tokens) !== 2) return 'path format error';

		if (isSuperUser(Auth::user())) {
			$ok = ($tokens[1] === '' or validateUsername($tokens[1]));
		} else {
			$ok = ($tokens[0] === $root_dir and $tokens[1] === $user_dir);
		}

		return $ok ? '' : 'no permission';
	}

	// ensure authDir($path) === '';
	function Ls($path) {
		$path = rtrim($path, '/');
		$dir = UOJContext::documentRoot() . '/' . $path;
		$files = array_values(array_filter(
			scandir($dir), function ($x) use ($dir) {return $x !== '.' && $x !== '..' && $x !== '.htaccess' && is_file($dir . '/' . $x);}
		));
		natsort($files);
		$ret = array();
		foreach ($files as $file) {
			$ret[] = array($file, filesize($dir . '/' . $file));
		}
		return $ret;
	}

	if (isset($_POST['ls'])) {
		$r = authDir($_POST['ls']);
		if ($r === '') {
			die(json_encode(Ls($_POST['ls'])));
		} else {
			die($r);
		}
	}

	if (isset($_POST['delete'])) {
		$r = authFile($_POST['delete']);
		if ($r === '') {
			unlink(UOJContext::documentRoot() . '/' . $_POST['delete']);
			$r = 'ok';
		}
		die($r);
	}

	$upload_form = new UOJForm('upload');

	$text = UOJLocale::get('select a file');
	$browse_text = UOJLocale::get('browse');
	$html = <<<EOD
<div id="div-upload">
	<label for="input-upload">$text</label>
	<input type="file" id="input-upload" name="upload" style="display: none;" onchange="$('#input-upload_path').val($('#input-upload').val());" />
	<div class="input-group bot-buffer-md">
		<input id="input-upload_path" class="form-control" type="text" readonly="readonly" />
		<span class="input-group-btn">
			<button type="button" class="btn btn-primary" style="width: 100px; !important" onclick="$('#input-upload').click();"><span class="glyphicon glyphicon-folder-open"></span> $browse_text</button>
		</span>
	</div>
	<span class="help-block" id="help-upload"></span>
</div>
EOD;
	$upload_form->addNoVal('upload', $html);

	$text = UOJLocale::get('dest path');
	$url = HTML::url('/');
	$readonly_html = (isSuperUser(Auth::user()) ? '' : 'readonly="readonly" ');

	$html = <<<EOD
<div id="div-filepath" class="form-inline form-group">
	<label class="col-sm-2 control-label">$text: </label>
	<div class="bot-buffer-md">
		<label>$url/</label>
		<select class="form-control" name="rootdir" id="input-rootdir" $readonly_html/>
			<option value="uploads">uploads</option>

EOD;
	if (isSuperUser(Auth::user())) {
		$html .= <<<EOD
			<option value="utility">utility</option>
			<option value="pictures">pictures</option>

EOD;
	}
	$html .= <<<EOD
		</select>
		<label>/</label>
		<input type="text" class="form-control" name="userdir" id="input-userdir" value="$user_dir" $readonly_html/>
		<label>/</label>
		<input type="text" class="form-control" name="fname" id="input-fname" value="" />
		<span class="help-block" id="help-filepath"></span>
	</div>
</div>
EOD;

	$upload_form->addNoVal('filepath', $html);

	$html = <<<EOD
<script>
	$('#input-upload').change(function () {
		var path = $(this).val(), posL = path.lastIndexOf('/'), posR = path.lastIndexOf('\\\\');
		$('#input-fname').val(path.substr(Math.max(posL, posR) + 1));
	});
</script>
EOD;

	$upload_form->appendHTML($html);

	$upload_form->is_big = true;
	$upload_form->has_file = true;

	$upload_form->handle = function() {
		global $url, $root_dir, $user_dir, $allow_rootdir;

		if (!isset($_FILES['upload'])) {
			becomeMsgPage('你在干啥……怎么什么都没交过来……？');
		} elseif (!is_uploaded_file($_FILES['upload']['tmp_name'])) {
			becomeMsgPage('上传出错，貌似你什么都没交过来……？');
		}

		$r_dir = $_POST['rootdir'];

		if (!is_string($r_dir) || $r_dir == '') becomeMsgPage('路径不能为空');

		if (isSuperUser(Auth::user())) {
			if (!isset($allow_rootdir[$r_dir])) becomeMsgPage('路径名无效');
		} else {
			if ($r_dir !== $root_dir) becomeMsgPage('非管理员只能选择 \'uploads\' 目录');
		}

		$u_dir = $_POST['userdir'];

		if (!is_string($u_dir)) becomeMsgPage('路径字段缺失');

		if (isSuperUser(Auth::user())) {
			if ($u_dir !== '') {
				if (!validateUsername($u_dir)) becomeMsgPage('路径包含非法字符');
			}
		} else {
			if ($u_dir !== $user_dir) becomeMsgPage('非管理员只能选择该用户目录');
		}

		$fname = $_POST['fname'];

		if (!is_string($fname) || $fname == '') becomeMsgPage('文件名不能为空');
		if (strlen($fname) > 200) becomeMsgPage('文件名不能超过 200 个字节');
		if (strStartWith($fname, '.')) becomeMsgPage('文件名不能以 \'.\' 开头');
		if (!preg_match('/^[a-zA-Z0-9_\-\.\x{4e00}-\x{9fa5}]+$/u', $fname)) becomeMsgPage('文件名包含非法字符');

		$path = $r_dir . ($u_dir === '' ? '/' : ('/' . $u_dir . '/')) . $fname;

		// try to create directory
		if ($u_dir != '') {
			$dest_dir = $r_dir . '/' . $u_dir;
			is_dir($dest_dir) or mkdir($dest_dir, 0777, true);
		}

		if (move_uploaded_file($_FILES['upload']['tmp_name'], UOJContext::documentRoot() . '/' . $path)) {
			becomeMsgPage("<div>上传成功！最终路径：<a href='/{$path}'>/{$path}</a></div>" . '<a href="javascript:history.go(-1)">返回</a>');
		} else {
			becomeMsgPage('上传失败！');
		}
	};

	$upload_form->succ_href = 'none';
	$upload_form->runAtServer();
?>
<?php echoUOJPageHeader(UOJLocale::get('file upload')); ?>

<h2><?= UOJLocale::get('file upload') ?></h2>

<?php $upload_form->printHTML(); ?>

<h3><?= UOJLocale::get('uploaded files list') ?></h3>
<div id="file-list"><p><a href="#" class="click-to-show"><?= UOJLocale::get('view all') ?></a></p><p class="text-muted">如需批量删除请使用 Web 控制台。</p></div>
<script>
	function soj_file_delete(name, interactive) {
		$.post('/file-upload', {
			delete : name
		}, function (msg) {
			if (interactive) {
				if (msg === 'ok')
					location.reload();
				else
					alert('删除失败！');
			} else {
				if (msg === 'ok')
					console.log('文件 ' + name + ' 删除成功！(请手动刷新页面)');
				else
					console.log('文件 ' + name + ' 删除失败！(错误信息：' + msg + ')');
			}
		});
	}

	$(document).ready(function () {
		$('.click-to-show').click(function (e) {
			var entries = [], list = [
<?php
		if (isSuperUser(Auth::user())) {
			foreach ($allow_rootdir as $r_dir => $_) {
				echo "\t\t\t\t'{$r_dir}',\n\t\t\t\t'{$r_dir}/{$user_dir}',\n";
			}
			echo "\t\t\t\t'utility/contest_scripts',";
			echo "\n\t\t\t\t'utility/group_scripts',";
			echo "\n\t\t\t\t'pictures/emoticon'\n";
		} else {
			echo "\t\t\t\t'{$root_dir}/{$user_dir}'\n";
		}
?>
			];

			e.preventDefault();

			for (var dir of list)
				$.ajax('/file-upload', {
					type : 'POST',
					dataType : 'json',
					async : false,
					data : {
						ls : dir
					},
					success : function (data) {
						for (var f of data) {
							entries.push([f[0], dir + '/' + f[0], '/' + dir + '/' + f[0], f[1]]);
						}
					}
				});

			$('#file-list').long_table(
				entries,
				1,
				'<tr>' + 
					'<th>ID</th>' +
					'<th>' + uojLocale('file name') + '</th>' +
					'<th>' + uojLocale('file complete path') + '</th>' +
					'<th>' + uojLocale('file size') + '</th>' +
					'<th>' + uojLocale('operation') + '</th>' +
				'</tr>',
				function (row, idx) { 
					return $('<tr />').append(
						$('<td>' + (idx + 1) + '</td>')
					).append(
						$('<td>' + row[0] + '</td>')
					).append(
						$('<td />').append(
							$('<a href="' + row[2] + '" target="_blank">' + row[2] + '</a></td>')
						)
					).append(
						$('<td>' + getFileSizeStr(row[3]) + '</td>')
					).append(
						$('<td />').append(
							$('<a href="#"><span class="glyphicon glyphicon-remove"></span> Delete</a>').click(function(e) {
								e.preventDefault();
								if (!confirm("您真的要删除文件 '" + row[1] + "' 吗？")) return;
								soj_file_delete(row[1], true);
							})
						)
					);
				}, {
					get_row_index : true,
					page_len : 100,
					print_after_table : function() {
						return '<div class="text-right text-muted">' + uojLocale("files::n files", entries.length) + '</div>';
					}
				}
			);
		});

		console.log("请使用 soj_file_delete('path/to/file') 来批量删除文件。");
	});
</script>

<?php echoUOJPageFooter() ?>
