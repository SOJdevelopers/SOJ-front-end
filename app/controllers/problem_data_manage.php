<?php
	requirePHPLib('form');
	requirePHPLib('judger');
	requirePHPLib('data');
	requirePHPLib('problem');

	if (!validateUInt($_GET['id']) || !($problem = queryProblemBrief($_GET['id']))) {
		become404Page();
	}

	if (!hasProblemPermission(Auth::user(), $problem)) {
		become403Page();
	}

	$oj_name = UOJConfig::$data['profile']['oj-name'];
	$problem_extra_config = getProblemExtraConfig($problem);
	$data_dir = "/var/uoj_data/{$problem['id']}";

	function echoFileNotFound($file_name) {
		echo '<h4>', htmlspecialchars($file_name), '<sub class="text-danger"> ', 'file not found', '</sub></h4>';
	}
	function echoFilePre($file_name, $output_limit = 1000) {
		global $data_dir;
		$file_full_name = $data_dir . '/' . $file_name;

		$finfo = finfo_open(FILEINFO_MIME);
		$mimetype = finfo_file($finfo, $file_full_name);
		if ($mimetype === false) {
			echoFileNotFound($file_name);
			return;
		}
		finfo_close($finfo);

		echo '<h4>', HTML::escape($file_name), '<sub> ', $mimetype, '</sub></h4>';
		if ($file_name === 'problem.conf') {
			echo '<pre class="uoj-problem-conf">
';
		} else {
			echo '<pre>
';
		}

		if (strStartWith($mimetype, 'text/')) {
			echo HTML::escape(uojFilePreview($file_full_name, $output_limit));
		} else {
			echo HTML::escape(strOmit(shell_exec('xxd -g 4 -l 5000 ' . escapeshellarg($file_full_name) . ' | head -c ' . ($output_limit + 4)), $output_limit));
		}
		echo '
</pre>';
	}

	if ($_POST['problem_data_file_submit'] === 'submit') {
		if ($_FILES['problem_data_file']['error'] > 0) {
  			$errmsg = 'Error: ' . $_FILES['problem_data_file']['error'];
			becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
  		} else {
				$up_filename = '/tmp/data' . uojRandString(20);
				move_uploaded_file($_FILES['problem_data_file']['tmp_name'], $up_filename . '.zip');
				$zip = new ZipArchive();
				if ($zip->open($up_filename . '.zip') === true) {
					insertAuditLog('problems','data uploading',$problem['id'],'','');
					$data_upload_dir = "/var/uoj_data/upload/{$problem['id']}";
					is_dir($data_upload_dir) or mkdir($data_upload_dir, 0777, true);
					$zip->extractTo($up_filename);
					$zip->close();
					$all_files = array_values(array_filter(scandir($up_filename), function ($x) {return $x !== '.' && $x !== '..';}));
					if (is_dir($up_filename . '/' . $all_files[0]) && !isset($all_files[1])) {
						exec("mv -bt $data_upload_dir $up_filename/{$all_files[0]}/* $up_filename/{$all_files[0]}/.[!.]*");
						rmdir("$up_filename/{$all_files[0]}");
					} else {
						exec("mv -bt $data_upload_dir $up_filename/* $up_filename/.[!.]*");
					}
					exec("rm $data_upload_dir/*~ -r");
					rmdir($up_filename);
					unlink($up_filename . '.zip');
					echo '<script>alert("上传成功！")</script>';
				} else {
					$errmsg = '解压失败！';
					unlink($up_filename . '.zip');
					becomeMsgPage('<div>' . $errmsg . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
				}
  		}
	}

	$info_form = new UOJForm('info');
	$download_url = HTML::url("/download.php?type=problem&id={$problem['id']}");
	$data_url = HTML::url("/download.php?type=data&id={$problem['id']}");

	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">problem_{$problem['id']}.zip</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a href="$download_url">$download_url</a>
		</div>
	</div>
</div>
EOD
	);
	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">data_{$problem['id']}.zip</label>
	<div class="col-sm-9">
		<div class="form-control-static">
			<a href="$data_url">$data_url</a>
		</div>
	</div>
</div>
EOD
	);
	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">headers</label>
	<div class="col-sm-3">
		<div class="form-control-static">
			<a href="/download.php?type=testlib.h">testlib.h</a>
		</div>
	</div>
	<div class="col-sm-3">
		<div class="form-control-static">
			<a href="/download.php?type=ex_testlib.h">ex_testlib.h</a>
		</div>
	</div>
	<div class="col-sm-3">
		<div class="form-control-static">
			<a href="/download.php?type=uoj_judger.h">uoj_judger.h</a>
		</div>
	</div>
</div>
EOD
	);

	$esc_submission_requirement = HTML::escape(json_encode(json_decode($problem['submission_requirement']), JSON_PRETTY_PRINT));
	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">提交文件配置</label>
	<div class="col-sm-9">
		<div class="form-control-static"><pre>
$esc_submission_requirement
</pre>
		</div>
	</div>
</div>
EOD
	);
	$esc_extra_config = HTML::escape(json_encode(json_decode($problem['extra_config']), JSON_PRETTY_PRINT));
	$info_form->appendHTML(<<<EOD
<div class="form-group">
	<label class="col-sm-3 control-label">其它配置</label>
	<div class="col-sm-9">
		<div class="form-control-static"><pre>
$esc_extra_config
</pre>
		</div>
	</div>
</div>
EOD
	);
	if ($problem['data_locked']) {
		$info_form->no_submit = true;
	} else {
		$info_form->addVInput('submission_requirement', 'text', '提交文件配置', $problem['submission_requirement'],
			function ($submission_requirement, &$vdata) {
				$submission_requirement = json_decode($submission_requirement, true);
				if ($submission_requirement === null) {
					return '不是合法的 JSON';
				}
				$vdata['submission_requirement'] = json_encode($submission_requirement);
			},
			null);
		$info_form->addVInput('extra_config', 'text', '其它配置', $problem['extra_config'],
			function ($extra_config, &$vdata) {
				$extra_config = json_decode($extra_config, true);
				if ($extra_config === null) {
					return '不是合法的 JSON';
				}
				$vdata['extra_config'] = json_encode($extra_config);
			},
			null);
		
			
		$info_form->handle = function(&$vdata) {
			global $problem;
			if ($problem['data_locked']) {
				becomeMsgPage('<div>Problem data locked.</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
			}
			dataUpdateSubmissionRequirement($problem['id'], $vdata['submission_requirement']);
			updateProblemExtraConfig($problem['id'], $vdata['extra_config']);
		};
	}
	class DataDisplayer {
		public $problem_conf = array();
		public $data_files = array();
		public $displayers = array();

		public function __construct($problem_conf = null, $data_files = null) {
			global $data_dir;

			if (isset($problem_conf)) {
				foreach ($problem_conf as $key => $val) {
					$this->problem_conf[$key] = array('val' => $val);
				}
			}

			if (!isset($data_files)) {
				$this->data_files = array_filter(scandir($data_dir), function($x) {return $x !== '.' && $x !== '..' && $x !== 'problem.conf';});
				natsort($this->data_files);
				array_unshift($this->data_files, 'problem.conf');
			} else {
				$this->data_files = $data_files;
			}

			$this->setDisplayer('problem.conf', function($self) {
				global $info_form;
				$info_form->printHTML();
				echo '<div class="top-buffer-md"></div>';

				/*
				echo '<table class="table table-bordered table-hover table-striped table-text-center">';
				echo '<thead>';
				echo '<tr>';
				echo '<th>key</th>';
				echo '<th>value</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				foreach ($self->problem_conf as $key => $info) {
					if (!isset($info['status'])) {
						echo '<tr>';
						echo '<td>', htmlspecialchars($key), '</td>';
						echo '<td>', htmlspecialchars($info['val']), '</td>';
						echo '</tr>';
					} elseif ($info['status'] == 'danger') {
						echo '<tr class="text-danger">';
						echo '<td>', htmlspecialchars($key), '</td>';
						echo '<td>', htmlspecialchars($info['val']), ' <span class="glyphicon glyphicon-remove"></span>', '</td>';
						echo '</tr>';
					}
				}
				echo '</tbody>';
				echo '</table>';
				*/

				echo '<table class="table table-bordered table-hover table-striped table-text-center">';
				echo '<thead>';
				echo '<tr>';
				echo '<th>评测机</th>';
				echo '<th>ip</th>';
				echo '<th>同步状态</th>';
				echo '</tr>';
				echo '</thead>';
				echo '<tbody>';
				foreach (DB::selectAll("select * from judger_info") as $judger) {
					echo '<tr>';
					echo '<td>', htmlspecialchars($judger["judger_name"]), '</td>';
					echo '<td>', htmlspecialchars($judger["ip"]), '</td>';
					$sync = queryJudgerDataNeedUpdate($_GET['id'], $judger["judger_name"]);
					echo '<td>', ($sync ? '<span class="glyphicon glyphicon-remove"></span>未同步' : '<span class="glyphicon glyphicon-ok"></span>已同步'), '</td>';
					echo '</tr>';
				}
				echo '</tbody>';
				echo '</table>';

				echoFilePre('problem.conf', 1048576);
			});
		}

		public function setProblemConfRowStatus($key, $status) {
			$this->problem_conf[$key]['status'] = $status;
			return $this;
		}

		public function setDisplayer($file_name, $fun) {
			$this->displayers[$file_name] = $fun;
			return $this;
		}
		public function addDisplayer($file_name, $fun) {
			$this->data_files[] = $file_name;
			$this->displayers[$file_name] = $fun;
			return $this;
		}
		public function echoDataFilesList($active_file) {
			foreach ($this->data_files as $file_name) {
				if ($file_name !== $active_file) {
					echo '<li>';
				} else {
					echo '<li class="active">';
				}
				echo '<a href="#">', htmlspecialchars($file_name), '</a></li>';
			}
		}
		public function displayFile($file_name) {
			global $data_dir;

			if (isset($this->displayers[$file_name])) {
				$fun = $this->displayers[$file_name];
				$fun($this);
			} elseif (in_array($file_name, $this->data_files)) {
				echoFilePre($file_name);
			} else {
				echoFileNotFound($file_name);
			}
		}
	}

	function getDataDisplayer() {
		global $data_dir;
		global $problem;

		$allow_files = array_flip(array_filter(scandir($data_dir), function($x) {return $x !== '.' && $x !== '..';}));

		$getDisplaySrcFunc = function($name) use($allow_files) {
			return function() use($name, $allow_files) {
				$src_name = $name . '.cpp';
				if (isset($allow_files[$src_name])) {
					echoFilePre($src_name, 100 * 1024);
				} else {
					echoFileNotFound($src_name);
				}
				if (isset($allow_files[$name])) {
					echoFilePre($name, 100 * 1024);
				} else {
					echoFileNotFound($name);
				}
			};
		};

		$problem_conf = getUOJConf("$data_dir/problem.conf");
		if ($problem_conf === -1) {
			return (new DataDisplayer())->setDisplayer('problem.conf', function() {
				global $info_form;
				$info_form->printHTML();
				echoFileNotFound('problem.conf');
			});
		}
		if ($problem_conf === -2) {
			return (new DataDisplayer())->setDisplayer('problem.conf', function() {
				global $info_form;
				$info_form->printHTML();
				echo '<h4 class="text-danger">problem.conf 格式有误</h4>';
				echoFilePre('problem.conf');
			});
		}

		$judger_name = getUOJConfVal($problem_conf, 'use_builtin_judger', null);
		if (!isset($problem_conf['use_builtin_judger'])) {
			return new DataDisplayer($problem_conf);
		}
		if ($problem_conf['use_builtin_judger'] == 'on') {
			$n_tests = getUOJConfVal($problem_conf, 'n_tests', '10');
			if (!validateUInt($n_tests)) {
				return (new DataDisplayer($problem_conf))->setProblemConfRowStatus('n_tests', 'danger');
			}

			$has_extra_tests = !(isset($problem_conf['submit_answer']) && $problem_conf['submit_answer'] == 'on');

			$data_disp = new DataDisplayer($problem_conf, array('problem.conf'));
			$data_disp->addDisplayer('tests',
				function($self) use($problem_conf, $allow_files, $n_tests, $n_ex_tests) {
					for ($num = 1; $num <= $n_tests; $num++) {
						$input_file_name = getUOJProblemInputFileName($problem_conf, $num);
						$output_file_name = getUOJProblemOutputFileName($problem_conf, $num);
						echo '<div class="row">';
						echo '<div class="col-md-6">';
						if (isset($allow_files[$input_file_name])) {
							echoFilePre($input_file_name);
						} else {
							echoFileNotFound($input_file_name);
						}
						echo '</div>';
						echo '<div class="col-md-6">';
						if (isset($allow_files[$output_file_name])) {
							echoFilePre($output_file_name);
						} else {
							echoFileNotFound($output_file_name);
						}
						echo '</div>';
						echo '</div>';
					}
				}
			);
			if ($has_extra_tests) {
				$n_ex_tests = getUOJConfVal($problem_conf, 'n_ex_tests', '0');
				if (!validateUInt($n_ex_tests)) {
					return (new DataDisplayer($problem_conf))->setProblemConfRowStatus('n_ex_tests', 'danger');
				}

				$data_disp->addDisplayer('extra tests',
					function($self) use($problem_conf, $allow_files, $n_tests, $n_ex_tests) {
						for ($num = 1; $num <= $n_ex_tests; $num++) {
							$input_file_name = getUOJProblemExtraInputFileName($problem_conf, $num);
							$output_file_name = getUOJProblemExtraOutputFileName($problem_conf, $num);
							echo '<div class="row">';
							echo '<div class="col-md-6">';
							if (isset($allow_files[$input_file_name])) {
								echoFilePre($input_file_name);
							} else {
								echoFileNotFound($input_file_name);
							}
							echo '</div>';
							echo '<div class="col-md-6">';
							if (isset($allow_files[$output_file_name])) {
								echoFilePre($output_file_name);
							} else {
								echoFileNotFound($output_file_name);
							}
							echo '</div>';
							echo '</div>';
						}
					}
				);
			}
			
			if (isset($problem_conf['use_builtin_checker'])) {
				$data_disp->addDisplayer('checker', function($self) {
					echo '<h4>use builtin checker : ', $self->problem_conf['use_builtin_checker']['val'], '</h4>';
				});
			} else {
				$data_disp->addDisplayer('checker', $getDisplaySrcFunc('chk'));
			}
			if ($problem['hackable']) {
				$data_disp->addDisplayer('standard', $getDisplaySrcFunc('std'));
				$data_disp->addDisplayer('validator', $getDisplaySrcFunc('val'));
			}
			return $data_disp;
		} else {
			return (new DataDisplayer($problem_conf))->setProblemConfRowStatus('use_builtin_judger', 'danger');
		}
	}

	$data_disp = getDataDisplayer();
	$data_disp->addDisplayer('uploaded file list', function($self) {
			global $problem;
			global $data_dir;
			$upload_dir = "/var/uoj_data/upload/{$problem['id']}";
			$files = scandir($upload_dir);

			$last_sync = 0;
			if(is_dir($data_dir)) {
				$last_sync = stat($data_dir)['mtime'];
				echo sprintf("<h4>last sync: %s</h4>", date("Y-m-d H:i:s", $last_sync));
			}

			if(count($files) <= 2) { // "." and ".."
				echo "<h4>no file uploaded.</h4>";
				return;
			}

			echo "<pre>";
			function formatSize($bytes) {
				$units = ['B', 'KiB', 'MiB', 'GiB', 'TiB'];
				$i = 0;
				while ($bytes >= 1024 && $i < count($units) - 1) {
					$bytes /= 1024;
					$i++;
				}
				return round($bytes, 2) . ' ' . $units[$i];
			}

			natsort($files);

			foreach ($files as $file) {
				if ($file !== '.' && $file !== '..') {
					$filePath = $upload_dir . '/' . $file;
					$fileInfo = stat($filePath);

					if (is_dir($filePath)) {
						$size = 'Directory';
					} else {
						$size = formatSize($fileInfo['size']);
					}

					$mtime = $fileInfo['mtime'];
					if($mtime > $last_sync) {
						$mtime = "<strong style='color:red'>".date("Y-m-d H:i:s", $fileInfo['mtime'])."</strong>";
					} else {
						$mtime = date("Y-m-d H:i:s", $fileInfo['mtime']);
					}
					
					echo sprintf("%-34s %-14s %s\n", $file, $size, $mtime);
				}
			}
			echo "</pre>";
		}
	);

	if (isset($_GET['display_file'])) {
		if (!isset($_GET['file_name'])) {
			echoFileNotFound('');
		} else {
			$data_disp->displayFile($_GET['file_name']);
		}
		die();
	}

	if (!$problem['data_locked']) {
		$hackable_form = new UOJForm('hackable');
		$hackable_form->handle = function() {
			global $problem;
			global $action_reason;
			set_time_limit(600);
			$ret = dataFlipHackableStatus($problem, array('reason' => (isset($action_reason)?$action_reason:null)));
			if ($ret) {
				becomeMsgPage('<div>' . $ret . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
			}
		};
		$hackable_form->submit_button_config['class_str'] = 'btn btn-warning btn-block';
		$hackable_form->submit_button_config['text'] = $problem['hackable'] ? '禁止使用 hack' : '允许使用 hack';
		$hackable_form->submit_button_config['smart_confirm'] = '';
		$hackable_form->submit_button_config['reason'] = '';
	}

	if (!$problem['data_locked']) {
		$data_form = new UOJForm('data');
		$data_form->handle = function() {
			global $problem;
			global $action_reason;
			set_time_limit(600);
			$ret = dataSyncProblemData($problem, (bool)isSuperUser(Auth::user()), array('reason' => (isset($action_reason)?$action_reason:null)));
			if ($ret) {
				becomeMsgPage('<div>' . $ret . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
			}
		};
		$data_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
		$data_form->submit_button_config['text'] = '完全同步数据';
		$data_form->submit_button_config['smart_confirm'] = '';
		$data_form->submit_button_config['reason'] = '';
	}

	if (!$problem['data_locked']) {
		$fast_data_form = new UOJForm('fast_data');
		$fast_data_form->handle = function() {
			global $problem;
			global $action_reason;
			set_time_limit(600);
			$ret = dataFastSyncProblemData($problem, (bool)isSuperUser(Auth::user()), array('reason' => (isset($action_reason)?$action_reason:null)));
			if ($ret) {
				becomeMsgPage('<div>' . $ret . '</div><a href="/problem/' . $problem['id'] . '/manage/data">返回</a>');
			}
		};
		$fast_data_form->submit_button_config['class_str'] = 'btn btn-success btn-block';
		$fast_data_form->submit_button_config['text'] = '快速同步数据';
		$fast_data_form->submit_button_config['confirm_text'] = '你真的要快速同步数据吗？\nWarning: 快速同步只适用于不改变任何代码的情形，否则请使用完全同步数据。';
		$fast_data_form->submit_button_config['reason'] = '';
	}

	if (!$problem['data_locked']) {
		$clear_data_form = new UOJForm('clear_data');
		$clear_data_form->handle = function() {
			global $problem;
			global $action_reason;
			dataClearProblemData($problem, array('reason' => (isset($action_reason)?$action_reason:null)));
		};
		$clear_data_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
		$clear_data_form->submit_button_config['text'] = '清空题目数据';
		$clear_data_form->submit_button_config['smart_confirm'] = '';
		$clear_data_form->submit_button_config['reason'] = '';
	}

	if (!$problem['data_locked']) {
		$rejudge_form = new UOJForm('rejudge');
		$rejudge_form->handle = function() {
			global $problem;
			global $action_reason;
			rejudgeProblem($problem, array('reason' => (isset($action_reason)?$action_reason:null)));
		};
		$rejudge_form->succ_href = "/submissions?problem_id={$problem['id']}";
		$rejudge_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
		$rejudge_form->submit_button_config['text'] = '重测该题';
		$rejudge_form->submit_button_config['smart_confirm'] = '';
		$rejudge_form->submit_button_config['reason'] = '';

		$rejudgege97_form = new UOJForm('rejudgege97');
		$rejudgege97_form->handle = function() {
			global $problem;
			global $action_reason;
			rejudgeProblemGe97($problem, array('reason' => (isset($action_reason)?$action_reason:null)));
		};
		$rejudgege97_form->succ_href = "/submissions?problem_id={$problem['id']}";
		$rejudgege97_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
		$rejudgege97_form->submit_button_config['text'] = '重测不低于 97 分的程序';
		$rejudgege97_form->submit_button_config['smart_confirm'] = '';
		$rejudgege97_form->submit_button_config['reason'] = '';
		
		$rejudgeac_form = new UOJForm('rejudgeac');
		$rejudgeac_form->handle = function() {
			global $problem;
			global $action_reason;
			rejudgeProblemAC($problem, array('reason' => (isset($action_reason)?$action_reason:null)));
		};
		$rejudgeac_form->succ_href = "/submissions?problem_id={$problem['id']}";
		$rejudgeac_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
		$rejudgeac_form->submit_button_config['text'] = '重测通过的程序';
		$rejudgeac_form->submit_button_config['smart_confirm'] = '';
		$rejudgeac_form->submit_button_config['reason'] = '';
	}

	$view_type_form = new UOJForm('view_type');
	$view_type_form->addVSelect('view_content_type',
		array('NONE' => '禁止',
				'SELF' => '仅自己',
				'ALL_AFTER_AC' => 'AC后',
				'ALL' => '所有人'
		),
		'查看提交文件:',
		$problem_extra_config['view_content_type']
	);
	$view_type_form->addVSelect('view_all_details_type',
		array('NONE' => '禁止',
				'SELF' => '仅自己',
				'ALL_AFTER_AC' => 'AC后',
				'ALL' => '所有人'
		),
		'查看全部详细信息:',
		$problem_extra_config['view_all_details_type']
	);
	$view_type_form->addVSelect('view_details_type',
		array('NONE' => '禁止',
				'SELF' => '仅自己',
				'ALL_AFTER_AC' => 'AC后',
				'ALL' => '所有人'
		),
		'查看测试点详细信息:',
		$problem_extra_config['view_details_type']
	);
	$view_type_form->handle = function() {
		global $problem, $problem_extra_config;
		$config = $problem_extra_config;
		$config['view_content_type'] = $_POST['view_content_type'];
		$config['view_all_details_type'] = $_POST['view_all_details_type'];
		$config['view_details_type'] = $_POST['view_details_type'];
		updateProblemExtraConfig($problem['id'], json_encode($config));
	};
	$view_type_form->submit_button_config['class_str'] = 'btn btn-warning btn-block top-buffer-sm';
	
	if (!$problem['data_locked'] && $problem['hackable']) {
		$test_std_form = new UOJForm('test_std');
		$test_std_form->handle = function() {
			global $problem;
			
			$user_std = queryUser('std');
			if (!$user_std) {
				becomeMsgPage('Please create an user named "std"');
			}
			
			$requirement = json_decode($problem['submission_requirement'], true);
			
			$zip_file_name = uojRandAvaiableSubmissionFileName();
			$zip_file = new ZipArchive();
			if ($zip_file->open(UOJContext::storagePath().$zip_file_name, ZipArchive::CREATE) !== true) {
				becomeMsgPage('提交失败');
			}
		
			$content = array();
			$content['file_name'] = $zip_file_name;
			$content['config'] = array();
			foreach ($requirement as $req) {
				if ($req['type'] == 'source code') {
					$std_lang = isset($req['std_language']) ? $req['std_language'] : 'C++14';
					$content['config'][] = array("{$req['name']}_language", $std_lang);
				}
			}
		
			$tot_size = 0;
			foreach ($requirement as $req) {
				$zip_file->addFile("/var/uoj_data/{$problem['id']}/std.cpp", $req['file_name']);
				$tot_size += $zip_file->statName($req['file_name'])['size'];
			}
		
			$zip_file->close();
		
			$content['config'][] = array('validate_input_before_test', 'on');
			$content['config'][] = array('problem_id', $problem['id']);
			$esc_content = DB::escape(json_encode($content));
			$esc_language = DB::escape($std_lang);
		 	
		 	$result = array();
		 	$result['status'] = 'Waiting';
		 	$result_json = json_encode($result);
			
			DB::insert("insert into submissions (problem_id, submit_time, submitter, content, language, tot_size, status, result) values ({$problem['id']}, now(), '{$user_std['username']}', '$esc_content', '$esc_language', $tot_size, '{$result['status']}', '$result_json')");
		};
		$test_std_form->succ_href = "/submissions?problem_id={$problem['id']}";
		$test_std_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
		$test_std_form->submit_button_config['text'] = '检验数据正确性';
		$test_std_form->runAtServer();
	}
	
	$problem_lock_form = new UOJForm('problem_lock');
	$problem_lock_form->handle = function() {
		global $problem;
		global $action_reason;
		dataFlipLockedStatus($problem, array('reason' => (isset($action_reason)?$action_reason:null)));
	};
	$problem_lock_form->submit_button_config['reason'] = '';
	$problem_lock_form->submit_button_config['class_str'] = 
		$problem['data_locked'] ? 'btn btn-danger btn-block' : 'btn btn-success btn-block';
	$problem_lock_form->submit_button_config['text'] = $problem['data_locked'] ? '题目数据已锁定' : '锁定题目数据';
	if (!$problem['data_locked'])
		$problem_lock_form->submit_button_config['smart_confirm'] = '';
	else
		$problem_lock_form->submit_button_config['confirm_text'] = '你真的要解锁题目数据吗？';

	$judger_data_clean_form = new UOJForm('judger_data_clean');
	$judger_data_clean_form->handle = function() {
		global $problem;
		clearJudgerData($problem['id']);
	};
	$judger_data_clean_form->submit_button_config['class_str'] = 'btn btn-danger btn-block';
	$judger_data_clean_form->submit_button_config['text'] = '清空该题评测机数据';
	$judger_data_clean_form->submit_button_config['confirm_text'] = '你真的要清空对应的评测机数据吗？';

	if (!$problem['data_locked']) {
		$hackable_form->runAtServer();
	}
	$view_type_form->runAtServer();
	if (!$problem['data_locked']) {
		$data_form->runAtServer();
		$fast_data_form->runAtServer();
		$clear_data_form->runAtServer();
		$rejudge_form->runAtServer();
		$rejudgege97_form->runAtServer();
		$rejudgeac_form->runAtServer();
	}
	$problem_lock_form->runAtServer();
	$judger_data_clean_form->runAtServer();
	$info_form->runAtServer();

?>
<?php
	$REQUIRE_LIB['dialog'] = '';
?>
<?php echoUOJPageHeader(HTML::stripTags($problem['title']) . ' - 数据 - 题目管理') ?>
<h1 align="center">#<?=$problem['id']?> : <?=$problem['title']?> 管理</h1>
<ul class="nav nav-tabs" role="tablist">
	<li><a href="/problem/<?= $problem['id'] ?>/manage/statement" role="tab">编辑</a></li>
	<li><a href="/problem/<?= $problem['id'] ?>/manage/managers" role="tab">管理者</a></li>
	<li class="active"><a href="/problem/<?= $problem['id'] ?>/manage/data" role="tab">数据</a></li>
	<li><a href="/problem/<?=$problem['id']?>" role="tab">返回</a></li>
</ul>

<div class="row">
	<div class="col-md-10 top-buffer-sm">
		<div class="row">
			<div class="col-md-3 top-buffer-sm" id="div-file_list">
				<ul class="nav nav-pills nav-stacked">
					<?php $data_disp->echoDataFilesList('problem.conf'); ?>
				</ul>
			</div>
			<div class="col-md-9 top-buffer-sm" id="div-file_content">
				<?php $data_disp->displayFile('problem.conf'); ?>
			</div>
			<script type="text/javascript">
				curFileName = '';
				$('#div-file_list a').click(function(e) {
					$('#div-file_content').html('<h3>loading...</h3>');
					$(this).tab('show');

					var fileName = $(this).text();
					curFileName = fileName;
					$.get('/problem/<?= $problem['id'] ?>/manage/data', {
							display_file: '',
							file_name: fileName
						},
						function(data) {
							if (curFileName != fileName) {
								return;
							}
							$('#div-file_content').html(data);
						},
						'html'
					);
					return false;
				});
			</script>
		</div>
	</div>
	<div class="col-md-2 top-buffer-sm">
		<div class="top-buffer-md">
<?php if ($problem['hackable']) { ?>
				<span class="glyphicon glyphicon-ok"></span> hack 功能已启用
<?php } else { ?>
				<span class="glyphicon glyphicon-remove"></span> hack 功能已禁止
<?php }
	if (!$problem['data_locked']) {
		$hackable_form->printHTML();
	}
?>
		</div>
<?php if (!$problem['data_locked'] && $problem['hackable']) { ?>
		<div class="top-buffer-md">
			<?php $test_std_form->printHTML(); ?>
		</div>
<?php } ?>
		<div class="top-buffer-md">
			<button id="button-display_view_type" type="button" class="btn btn-primary btn-block" onclick="$('#div-view_type').toggle('fast');">修改提交记录可视权限</button>
			<div class="top-buffer-sm" id="div-view_type" style="display:none; padding-left:5px; padding-right:5px;">
				<?php $view_type_form->printHTML(); ?>
			</div>
		</div>
<?php if (!$problem['data_locked']) { ?>
		<div class="top-buffer-md">
			<button type="button" class="btn btn-block btn-primary" data-toggle="modal" data-target="#UploadDataModal">上传数据</button>
		</div>
		<div class="top-buffer-md">
			<?php $data_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $fast_data_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $clear_data_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $rejudge_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $rejudgege97_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $rejudgeac_form->printHTML(); ?>
		</div>
<?php } ?>
		<div class="top-buffer-md">
			<?php $problem_lock_form->printHTML(); ?>
		</div>
		<div class="top-buffer-md">
			<?php $judger_data_clean_form->printHTML(); ?>
		</div>
	</div>

<?php if (!$problem['data_locked']) { ?>
	<div class="modal fade" id="UploadDataModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
					<h4 class="modal-title" id="myModalLabel">上传数据</h4>
				</div>
				<div class="modal-body">
					<form action="" method="post" enctype="multipart/form-data" role="form">
					<div class="form-group">
						<label for="exampleInputFile">文件</label>
						<style>
							#problem_data_file {
								position: absolute;
								width: 100%;
								height: 100%;
								opacity: 0;
								left: 0px;
								top: 0px;
							}
							.highbox {
								position: relative;
								line-height: 122.43px;
								border: 2px dashed fuchsia;
							}
							.highbox.hover {
								line-height: 114.43px;
								border: 6px solid fuchsia;
							}
						</style>
						<div class="highbox" id="problem_data_box">
							<input type="file" name="problem_data_file" id="problem_data_file">
							<div class="text-center" id="problem_data_file_name">请上传.zip文件，支持拖放</div>
						</div>
						<script>
							$("#problem_data_file").change(function () {
								var path = $(this).val(), posL = path.lastIndexOf('/'), posR = path.lastIndexOf('\\');
								var str = path.substr(Math.max(posL, posR) + 1);
								if (!str) str = "请上传.zip文件，支持拖放";
								$("#problem_data_file_name").text(str);
							});
							$("#problem_data_box")
								.on("dragenter", function (e) {
									$("#problem_data_box").addClass("hover");
								})
								.on("dragleave", function (e) {
									$("#problem_data_box").removeClass("hover");
								})
								.on("drop", function (e) {
									$("#problem_data_box").removeClass("hover");
								});
						</script>
						<!--<p class="help-block">请上传.zip文件</p>-->
					</div>
					<input type="hidden" name="problem_data_file_submit" value="submit">
					<button type="submit" class="btn btn-success btn-block btn-lg">上传</button>
				</form>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-default" data-dismiss="modal">关闭</button>
				</div>
			</div>
  		</div>
	</div>
<?php } ?>
</div>
<?php echoUOJPageFooter() ?>
