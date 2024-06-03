<?php
	// Actually, these things should be done by main_judger so that the code would be much simpler.
	// However, this lib exists due to some history issues.
	
	function dataNewProblem($id) {
		mkdir("/var/uoj_data/upload/$id");
		mkdir("/var/uoj_data/$id");

		exec("cd /var/uoj_data; rm $id.zip; zip $id.zip $id -r -q");
	}

	class UOJProblemConfException extends Exception {
		public function __construct($message) {
			parent::__construct("<strong>problem.conf</strong> : $message");
		}
	}
	class UOJFileNotFoundException extends Exception {
		public function __construct($file_name) {
			parent::__construct("file <strong>" . htmlspecialchars($file_name) . '</strong> not found');
		}
	}

	function dataClearProblemData($problem) {
		$id = $problem['id'];
		clearJudgerData($id);
		if (!validateUInt($id)) {
			error_log("dataClearProblemData: hacker detected");
			return "invalid problem id";
		}

		exec("rm /var/uoj_data/upload/$id -r");
		exec("rm /var/uoj_data/$id -r");
		dataNewProblem($id);
	}
	
	class SyncProblemDataHandler {
		private $problem, $permission_level, $sync_config;
		private $data_upload_dir, $data_dir, $prepare_dir;
		private $requirement, $problem_extra_config;
		private $problem_conf, $final_problem_conf;
		private $allow_files;

		public function __construct($problem, $permission_level, $config = array()) {
			$this->problem = $problem;
			$this->permission_level = $permission_level;
			$this->sync_config = $config;
		}

		private function check_conf_on($name) {
			return isset($this->problem_conf[$name]) && $this->problem_conf[$name] === 'on';
		}

		private function copy_to_prepare($file_name) {
			global $uojMainJudgerWorkPath;
			if (!isset($this->allow_files[$file_name])) {
				throw new UOJFileNotFoundException($file_name);
			}
			$src = escapeshellarg("{$this->data_upload_dir}/$file_name");
			$dest = escapeshellarg("{$this->prepare_dir}/$file_name");
			if (isset($this->problem_extra_config['dont_use_formatter']) || !is_file("{$this->data_upload_dir}/$file_name")) {
				exec("cp $src $dest -r", $output, $ret);
			} else {
				exec("$uojMainJudgerWorkPath/run/formatter <$src >$dest", $output, $ret);
			}
			if ($ret) {
				throw new UOJFileNotFoundException($file_name);
			}
		}
		private function copy_file_to_prepare($file_name) {
			global $uojMainJudgerWorkPath;
			if (!isset($this->allow_files[$file_name]) || !is_file("{$this->data_upload_dir}/$file_name")) {
				throw new UOJFileNotFoundException($file_name);
			}
			$this->copy_to_prepare($file_name);
		}
		private function compile_at_prepare($name, $config = array()) {
			global $uojMainJudgerWorkPath;

			if (isset($this->sync_config['no_compile'])) {
				is_file("{$this->data_dir}/$name") and rename("{$this->data_dir}/$name", "{$this->prepare_dir}/$name");
				return;
			}

			$include_path = "$uojMainJudgerWorkPath/include";
			
			if (!isset($config['src'])) {
				$config['src'] = "$name.cpp";
			}
			
			if (isset($config['path'])) {
				exec("mv {$this->prepare_dir}/$name.cpp {$this->prepare_dir}/{$config['path']}/$name.cpp");
				$work_path = "{$this->prepare_dir}/{$config['path']}";
			} else {
				$work_path = $this->prepare_dir;
			}

			$cmd_prefix = "$uojMainJudgerWorkPath/run/run_program >{$this->prepare_dir}/run_compiler_result.txt --in=/dev/null --out=stderr --err={$this->prepare_dir}/compiler_result.txt --tl=20000 --ml=512 --ol=64 --type=compiler --work-path={$work_path}";
			if (isset($config['need_include_header']) && $config['need_include_header']) {
				exec("$cmd_prefix --add-readable-raw=$include_path/ /usr/bin/g++ -o $name {$config['src']} -I$include_path -lm -O2 -DONLINE_JUDGE");
			} else {
				exec("$cmd_prefix /usr/bin/g++ -o $name {$config['src']} -lm -O2 -DONLINE_JUDGE");
			}
			
			$fp = fopen("{$this->prepare_dir}/run_compiler_result.txt", "r");
			if (fscanf($fp, '%d %d %d %d', $rs, $used_time, $used_memory, $exit_code) != 4) {
				$rs = 7;
			}
			fclose($fp);
			
			unlink("{$this->prepare_dir}/run_compiler_result.txt");
			
			if ($rs != 0 || $exit_code != 0) {
				if ($rs == 0) {
					throw new Exception("<strong>$name</strong> : compile error<pre>\n" . uojFilePreview("{$this->prepare_dir}/compiler_result.txt", 10240) . "\n</pre>");
				} elseif ($rs == 7) {
					throw new Exception("<strong>$name</strong> : compile error. No comment");
				} else {
					throw new Exception("<strong>$name</strong> : compile error. Compiler " . judgerCodeStr($rs));
				}
			}
			
			unlink("{$this->prepare_dir}/compiler_result.txt");
			
			if (isset($config['path'])) {
				exec("mv {$this->prepare_dir}/{$config['path']}/$name.cpp {$this->prepare_dir}/$name.cpp");
				exec("mv {$this->prepare_dir}/{$config['path']}/$name {$this->prepare_dir}/$name");
			}
		}
		private function makefile_at_prepare() {
			global $uojMainJudgerWorkPath;

			$include_path = "$uojMainJudgerWorkPath/include";
			$cmd_prefix = "$uojMainJudgerWorkPath/run/run_program >{$this->prepare_dir}/run_makefile_result.txt --in=/dev/null --out=stderr --err={$this->prepare_dir}/makefile_result.txt --tl=60000 --ml=512 --ol=64 --type=compiler --unsafe --work-path={$this->prepare_dir}";
			exec("$cmd_prefix --add-readable-raw=$include_path/ /usr/bin/make INCLUDE_PATH=$include_path");
			
			$fp = fopen("{$this->prepare_dir}/run_makefile_result.txt", "r");
			if (fscanf($fp, '%d %d %d %d', $rs, $used_time, $used_memory, $exit_code) != 4) {
				$rs = 7;
			}
			fclose($fp);
			
			unlink("{$this->prepare_dir}/run_makefile_result.txt");
			
			if ($rs != 0 || $exit_code != 0) {
				if ($rs == 0) {
					throw new Exception("<strong>Makefile</strong> : compile error<pre>\n" . uojFilePreview("{$this->prepare_dir}/makefile_result.txt", 10240) . "\n</pre>");
				} elseif ($rs == 7) {
					throw new Exception("<strong>Makefile</strong> : compile error. No comment");
				} else {
					throw new Exception("<strong>Makefile</strong> : compile error. Compiler " . judgerCodeStr($rs));
				}
			}
			
			unlink("{$this->prepare_dir}/makefile_result.txt");
		}
		
		public function handle() {
			$id = $this->problem['id'];
			if (!validateUInt($id)) {
				error_log("dataSyncProblemData: hacker detected");
				return "invalid problem id";
			}

			$this->data_upload_dir = "/var/uoj_data/upload/$id";
			$this->data_dir = "/var/uoj_data/$id";
			$this->prepare_dir = "/var/uoj_data/prepare_$id";

			if (file_exists($this->prepare_dir)) {
				return "please wait until the last sync finish";
			}

			try {
				$this->requirement = array();
				$this->problem_extra_config = json_decode($this->problem['extra_config'], true);

				mkdir($this->prepare_dir, 0755);
				if (!is_file("{$this->data_upload_dir}/problem.conf")) {
					throw new UOJFileNotFoundException("problem.conf");
				}

				$this->problem_conf = getUOJConf("{$this->data_upload_dir}/problem.conf");
				$this->final_problem_conf = $this->problem_conf;
				if ($this->problem_conf === -1) {
					throw new UOJFileNotFoundException("problem.conf");
				} elseif ($this->problem_conf === -2) {
					throw new UOJProblemConfException("syntax error");
				}

				$this->allow_files = array_flip(array_filter(scandir($this->data_upload_dir), function($x){return $x !== '.' && $x !== '..';}));

				$zip_file = new ZipArchive();
				if ($zip_file->open("{$this->prepare_dir}/download.zip", ZipArchive::CREATE) !== true) {
					throw new Exception("<strong>download.zip</strong> : failed to create the zip file");
				}
				
				if (isset($this->allow_files['require']) && is_dir("{$this->data_upload_dir}/require")) {
					$this->copy_to_prepare('require');
				}

				if ($this->check_conf_on('use_builtin_judger')) {
					$n_tests = getUOJConfVal($this->problem_conf, 'n_tests', '10');
					if (!validateUInt($n_tests) || $n_tests <= 0) {
						throw new UOJProblemConfException("n_tests must be a positive integer");
					}
					for ($num = 1; $num <= $n_tests; $num++) {
						$input_file_name = getUOJProblemInputFileName($this->problem_conf, $num);
						$output_file_name = getUOJProblemOutputFileName($this->problem_conf, $num);

						$this->copy_file_to_prepare($input_file_name);
						$this->copy_file_to_prepare($output_file_name);
					}

					if (!$this->check_conf_on('interaction_mode')) {
						if (isset($this->problem_conf['use_builtin_checker'])) {
							if (!preg_match('/^[a-zA-Z0-9_]{1,20}$/', $this->problem_conf['use_builtin_checker'])) {
								throw new Exception("<strong>" . htmlspecialchars($this->problem_conf['use_builtin_checker']) . "</strong> is not a valid checker");
							}
						} else {
							$this->copy_file_to_prepare('chk.cpp');
							$this->compile_at_prepare('chk', array('need_include_header' => true));
						}
					}

					if ($this->check_conf_on('submit_answer')) {
						if ($this->problem['hackable']) {
							throw new UOJProblemConfException("the problem can't be hackable if submit_answer is on");
						}

						for ($num = 1; $num <= $n_tests; $num++) {
							$input_file_name = getUOJProblemInputFileName($this->problem_conf, $num);
							$output_file_name = getUOJProblemOutputFileName($this->problem_conf, $num);
							
							if (!isset($this->problem_extra_config['dont_download_input'])) {
								$zip_file->addFile("{$this->prepare_dir}/$input_file_name", "$input_file_name");
							}

							$this->requirement[] = array('name' => "output{$num}", 'type' => 'text', 'file_name' => $output_file_name);
						}
					} else {
						$n_ex_tests = getUOJConfVal($this->problem_conf, 'n_ex_tests', '0');
						if (!validateUInt($n_ex_tests) || $n_ex_tests < 0) {
							throw new UOJProblemConfException("n_ex_tests must be a non-negative integer");
						}

						for ($num = 1; $num <= $n_ex_tests; $num++) {
							$input_file_name = getUOJProblemExtraInputFileName($this->problem_conf, $num);
							$output_file_name = getUOJProblemExtraOutputFileName($this->problem_conf, $num);

							$this->copy_file_to_prepare($input_file_name);
							$this->copy_file_to_prepare($output_file_name);
						}

						if ($this->problem['hackable']) {
							$this->copy_file_to_prepare('std.cpp');
							if ($this->check_conf_on('with_implementer')) {
								$this->compile_at_prepare('std',
									array(
										'src' => 'implementer.cpp std.cpp',
										'path' => 'require'
									)
								);
							} else if ($this->check_conf_on('need_require')) {
								$this->compile_at_prepare('std', array('path' => 'require'));
							} else {
								$this->compile_at_prepare('std');
							}
							$this->copy_file_to_prepare('val.cpp');
							$this->compile_at_prepare('val', array('need_include_header' => true));
						}

						if ($this->check_conf_on('interaction_mode')) {
							$this->copy_file_to_prepare('interactor.cpp');
							$this->compile_at_prepare('interactor', array('need_include_header' => true));
						}

						$n_download_tests = getUOJConfVal($this->problem_conf, 'n_download_tests', getUOJConfVal($this->problem_conf, 'n_sample_tests', $n_ex_tests));
						if (!validateUInt($n_download_tests) || $n_download_tests < 0) {
							throw new UOJProblemConfException("n_download_tests must be a non-negative integer");
						}
						if ($n_download_tests > $n_ex_tests) {
							throw new UOJProblemConfException("n_download_tests can't be greater than n_ex_tests");
						}

						if (!isset($this->problem_extra_config['dont_download_sample'])) {
							for ($num = 1; $num <= $n_download_tests; $num++) {
								$input_file_name = getUOJProblemExtraInputFileName($this->problem_conf, $num);
								$output_file_name = getUOJProblemExtraOutputFileName($this->problem_conf, $num);
								$zip_file->addFile("{$this->prepare_dir}/{$input_file_name}", "$input_file_name");
								if (!isset($this->problem_extra_config['dont_download_sample_output'])) {
									$zip_file->addFile("{$this->prepare_dir}/{$output_file_name}", "$output_file_name");
								}
							}
						}

						$this->requirement[] = array('name' => 'answer', 'type' => 'source code', 'file_name' => 'answer.code', 'std_language' => 'C++14');
					}
				} else {
					if ($this->permission_level !== true) {
						throw new UOJProblemConfException("use_builtin_judger must be on.");
					} else {
						foreach ($this->allow_files as $file_name => $file_num) {
							$this->copy_to_prepare($file_name);
						}
						$this->makefile_at_prepare();
						
						$this->requirement[] = array('name' => 'answer', 'type' => 'source code', 'file_name' => 'answer.code', 'std_language' => 'C++14');
					}
				}
				putUOJConf("{$this->prepare_dir}/problem.conf", $this->final_problem_conf);

				if (isset($this->allow_files['download']) && is_dir("{$this->data_upload_dir}/download")) {
					foreach (scandir("{$this->data_upload_dir}/download") as $file_name) {
						if (is_file("{$this->data_upload_dir}/download/{$file_name}")) {
							$zip_file->addFile("{$this->data_upload_dir}/download/{$file_name}", $file_name);
						}
					}
				}
				
				$zip_file->close();

				$orig_requirement = json_decode($this->problem['submission_requirement'], true);
				if (!$orig_requirement) {
					$esc_requirement = DB::escape(json_encode($this->requirement));
					DB::update("update problems set submission_requirement = '$esc_requirement' where id = $id");
				}
			} catch (Exception $e) {
				exec("rm {$this->prepare_dir} -r");
				return $e->getMessage();
			}

			exec("rm {$this->data_dir} -r");
			rename($this->prepare_dir, $this->data_dir);

			exec("cd /var/uoj_data; rm $id.zip; zip $id.zip $id -r -q");

			return '';
		}
	}

	function clearJudgerData($problem_id) {
		DB::delete("delete from judger_data_sync where problem_id=$problem_id");
	}

	function clearSingleJudgerData($judger_name, $problem_id) {
		$esc_judger_name = DB::escape($judger_name);
		DB::delete("delete from judger_data_sync where judger_name='$esc_judger_name' and problem_id=$problem_id");
	}

	function dataSyncProblemData($problem, $permission_level) {
		clearJudgerData($problem["id"]);
		return (new SyncProblemDataHandler($problem, $permission_level, array()))->handle();
	}

	function dataFastSyncProblemData($problem, $permission_level) {
		clearJudgerData($problem["id"]);
		return (new SyncProblemDataHandler($problem, $permission_level, array('no_compile' => '')))->handle();
	}

	function dataAddExtraTest($problem, $input_file_name, $output_file_name) {
		$id = $problem['id'];
		
		$cur_dir = "/var/uoj_data/upload/$id";
		
		$problem_conf = getUOJConf("{$cur_dir}/problem.conf");
		if ($problem_conf == -1 || $problem_conf == -2) {
			return $problem_conf;
		}
		$problem_conf['n_ex_tests'] = getUOJConfVal($problem_conf, 'n_ex_tests', 0) + 1;
		
		$new_input_name = getUOJProblemExtraInputFileName($problem_conf, $problem_conf['n_ex_tests']);
		$new_output_name = getUOJProblemExtraOutputFileName($problem_conf, $problem_conf['n_ex_tests']);
		
		putUOJConf("$cur_dir/problem.conf", $problem_conf);
		move_uploaded_file($input_file_name, "$cur_dir/$new_input_name");
		move_uploaded_file($output_file_name, "$cur_dir/$new_output_name");

		if (dataSyncProblemData($problem, true) === '') {
			rejudgeProblemAC($problem);
		} else {
			error_log('hack successfully but sync failed.');
		}
	}
