<?php

function uojHandleSign($str) {
	$len = strlen($str);
	$referrers = array();
	$res = '';
	$cnt_emoticon = 0;
	for ($i = 0; $i < $len; ) {
		$c = $str[$i++];
		if ($c === '@') {
			if ($i === $len or $str[$i] === '@') {
				$res .= '@';
				++$i;
			} elseif ($str[$i] === '/') {
				$res .= '/';
				++$i;
			} else {
				list($cur, $i) = uojIdentifierResolve($str, $i, $len);
				$user = queryUser($cur);
				if ($user == null) {
					$res .= '@' . $cur;
				} else {
					$referrers[] = $user['username'];
					$res .= '<span class="uoj-username" data-rating="' . $user['rating'] . '">@' . $user['username'] . '</span>';
				}
			}
		} elseif ($c === '/') {
			if ($i === $len or $cnt_emoticon >= 256) {
				$res .= '/';
			} else {
				list($cur, $i) = uojIdentifierResolve($str, $i, $len);
				if (is_file(UOJContext::documentRoot() . '/pictures/emoticon/' . $cur . '.png')) {
					$res .= '<img src="/pictures/emoticon/' . $cur . '.png" alt="/' . $cur . '" />';
					++$cnt_emoticon;
				} elseif (is_file(UOJContext::documentRoot() . '/pictures/emoticon/' . $cur . '.gif')) {
					$res .= '<img src="/pictures/emoticon/' . $cur . '.gif" alt="/' . $cur . '" />';
					++$cnt_emoticon;
				} else {
					$res .= '/' . $cur;
				}
			}
		} else {
			$res .= $c;
		}
	}
	return array($res, $referrers);
}

function uojFilePreview($file_name, $output_limit) {
	return strOmit(file_get_contents($file_name, false, null, 0, $output_limit + 4), $output_limit);
}

function uojIncludeView($name, $view_params = array()) {
	extract($view_params);
	include UOJContext::documentRoot() . '/app/views/' . $name . '.php';
}

function redirectTo($url) {
	header('Location: ' . $url);
	die();
}
function permanentlyRedirectTo($url) {
	header("HTTP/1.1 301 Moved Permanently"); 
	header('Location: ' . $url);
	die();
}
function redirectToLogin() {
	if (UOJContext::isAjax()) {
		die('please <a href="' . HTML::url('/login') . '">login</a>');
	} else {
		header('Location: ' . HTML::url('/login'));
		die();
	}
}
function becomeMsgPage($msg, $title = '消息') {
	if (UOJContext::isAjax()) {
		die($msg);
	} else {
		echoUOJPageHeader($title);
		echo $msg;
		echoUOJPageFooter();
		die();
	}
}
function become404Page() {
	header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
	becomeMsgPage('<div class="text-center"><div style="font-size: 233px">404</div><p>唔……未找到该页面……你是从哪里点进来的……&gt;_&lt;……</p></div>', '404');
}
function become403Page() {
	header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403); 
	becomeMsgPage('<div class="text-center"><div style="font-size: 233px">403</div><p>禁止入内！ T_T</p></div>', '403');
}

function getUserLink($username, $rating = null) {
	if (validateUsername($username) && ($user = queryUser($username))) {
		if ($rating == null) {
			$rating = $user['rating'];
		}
		return '<span class="uoj-username" data-rating="' . $rating. '">' . $username. '</span>';
	} else {
		$esc_username = HTML::escape($username);
		return '<span>' . $esc_username . '</span>';
	}
}

function getGroupLink($groupname, $rating = null) {
	if (validateUsername($groupname) && ($group = queryGroup($groupname))) {
		if ($rating == null) {
			$rating = $group['rating'];
		}
		return '<span class="uoj-groupname" data-rating="' . $rating . '">' . $groupname . '</span>';
	} else {
		$esc_groupname = HTML::escape($groupname);
		return '<span>' . $esc_groupname. '</span>';
	}
}

function getUserOrGroupLink($name, $rating = null) {
	if (!validateUsername($name)) {
		return '<span>' . HTML::escape($name) . '</span>';
	} elseif ($user = queryUser($name)) {
		if ($rating == null) {
			$rating = $user['rating'];
		}
		return '<span class="uoj-username" data-rating="' . $rating. '">' . $name. '</span>';
	} elseif ($group = queryGroup($name)) {
		if ($rating == null) {
			$rating = $group['rating'];
		}
		return '<span class="uoj-groupname" data-rating="' . $rating . '">' . $name . '</span>';
	}
}

function getProblemLink($problem, $problem_title = '!title_only') {
	if ($problem_title == '!title_only') {
		$problem_title = $problem['title'];
	} else if ($problem_title == '!id_and_title') {
		$problem_title = "#{$problem['id']}. {$problem['title']}";
	}
	return '<a href="/problem/' . $problem['id'] . '">' . $problem_title.'</a>';
}
function getContestProblemLink($problem, $contest_id, $problem_title = '!title_only') {
	if ($problem_title == '!title_only') {
		$problem_title = $problem['title'];
	} else if ($problem_title == '!id_and_title') {
		$problem_title = "#{$problem['id']}. {$problem['title']}";
	}
	return '<a href="/contest/' . $contest_id . '/problem/' . $problem['id'] . '">' . $problem_title . '</a>';
}

function getBlogLink($id) {
	if (validateUInt($id) && $blog = queryBlog($id)) {
		return '<a href="/blog/' . $id . '">' . $blog['title'] . '</a>';
	}
}

function getClickZanBlock($type, $id, $cnt, $val = null) {
	if ($val == null) {
		$val = queryZanVal($id, $type, Auth::user());
	}
	return '<div class="uoj-click-zan-block" data-id="' . $id . '" data-type="' . $type . '" data-val="' . $val . '" data-cnt="' . $cnt . '"></div>';
}


function getLongTablePageRawUri($page) {
		$path = strtok(UOJContext::requestURI(), '?');
		$query_string = strtok('?');
		parse_str($query_string, $param);
			
		$param['page'] = $page;
		if ($page == 1)
			unset($param['page']);
			
		if ($param) {
			return $path . '?' . http_build_query($param);
		} else {
			return $path;
		}
	}
function getLongTablePageUri($page) {
	return HTML::escape(getLongTablePageRawUri($page));
}

function echoLongTable($col_names, $table_name, $cond, $tail, $header_row, $print_row, $config) {
	$pag_config = $config;
	$pag_config['col_names'] = $col_names;
	$pag_config['table_name'] = $table_name;
	$pag_config['cond'] = $cond;
	$pag_config['tail'] = $tail;
	$pag = new Paginator($pag_config);

	$div_classes = isset($config['div_classes']) ? $config['div_classes'] : array('table-responsive');
	$table_classes = isset($config['table_classes']) ? $config['table_classes'] : array('table', 'table-bordered', 'table-hover', 'table-striped', 'table-text-center');
		
	echo '<div class="', join($div_classes, ' '), '">';
	echo '<table class="', join($table_classes, ' '), '">';
	echo '<thead>';
	echo $header_row;
	echo '</thead>';
	echo '<tbody>';

	foreach ($pag->get() as $idx => $row) {
		if (isset($config['get_row_index'])) {
			$print_row($row, $idx);
		} else {
			$print_row($row);
		}
	}
	if ($pag->isEmpty()) {
		echo '<tr><td colspan="233">', UOJLocale::get('none'), '</td></tr>';
	}

	echo '</tbody>';
	echo '</table>';
	echo '</div>';
	
	if (isset($config['print_after_table'])) {
		$fun = $config['print_after_table'];
		$fun();
	}
		
	echo $pag->pagination();
}

function getSubmissionStatusDetails($submission) {
	$html = '<td colspan="233" style="vertical-align: middle">';
	
	$out_status = explode(', ', $submission['status'])[0];
	
	$fly = '<img src="/pictures/emoticon/flying.gif" alt="小熊像超人一样飞" class="img-rounded" />';
	$think = '<img src="/pictures/emoticon/thinking.gif" alt="小熊像在思考" class="img-rounded" />';
	
	if ($out_status == 'Judged') {
		$status_text = '<strong>Judged!</strong>';
		$status_img = $fly;
	} else {
		if ($submission['status_details'] !== '') {
			$status_img = $fly;
			$status_text = HTML::escape($submission['status_details']);
		} else  {
			$status_img = $think;
			$status_text = $out_status;
		}
	}
	$html .= '<div class="uoj-status-details-img-div">' . $status_img . '</div>';
	$html .= '<div class="uoj-status-details-text-div">' . $status_text . '</div>';

	$html .= '</td>';
	return $html;
}

function echoJudgerInfo($judger_name) {
	if($judger_name == ''){
		echo "未知";
		return;
	}
	echo "评测机名称：" . $judger_name;
}

function echoSubmission($submission, $config, $user) {
	$limitLevel = querySubmissionDetailPermission($user, $submission);
	$problem = queryProblemBrief($submission['problem_id']);
	$status = explode(', ', $submission['status'])[0];
	$show_status_details = isOurSubmission($user, $submission) && $status !== 'Judged';

	$submission_id_str = "<a href=\"/submission/{$submission['id']}\">#{$submission['id']}</a>";
	$problem_link_str = '/';
	$submitter_link_str = '/';
	$submission_status_str = '/';
	$used_time_str = '/';
	$used_memory_str = '/';
	$language_str = '/';
	$size_str = '/';
	$submit_time_str = "<small>{$submission['submit_time']}</small>";
	$judge_time_str = "<small>{$submission['judge_time']}</small>";

	// SUBMISSION_STATUS_LIMIT : code length, language, time used, memory used
	if ($limitLevel & SUBMISSION_STATUS_LIMIT) {
		if ($submission['contest_id']) {
			$problem_link_str = getContestProblemLink($problem, $submission['contest_id'], '!id_and_title');
		} else {
			$problem_link_str = getProblemLink($problem, '!id_and_title');
		}

		$submitter_link_str = getUserOrGroupLink($submission['submitter']);

		if ($status == 'Judged') {
			if ($submission['score'] == null) {
				$submission_status_str = "<a href=\"/submission/{$submission['id']}\" class=\"small\">{$submission['result_error']}</a>";
			} else {
				$submission_status_str = "<a href=\"/submission/{$submission['id']}\" class=\"uoj-score\">{$submission['score']}</a>";
			}
		} else {
			$submission_status_str = "<a href=\"/submission/{$submission['id']}\" class=\"small\">$status</a>";
		}
	}

	// SUBMISSION_CODE_LIMIT : problem link, submitter link, submission status
	if ($limitLevel & SUBMISSION_CODE_LIMIT) {
		if ($submission['score'] != null) {
			$used_time_str = $submission['used_time'] . 'ms';
			$used_memory_str = $submission['used_memory'] . 'kb';
		}

		$language_str = "<a href=\"/submission/{$submission['id']}\">{$submission['language']}</a>";

		if ($submission['tot_size'] < 1024) {
			$size_str = $submission['tot_size'] . 'b';
		} else {
			$size_str = sprintf("%.1f", $submission['tot_size'] / 1024) . 'kb';
		}
	}
	
	if (!$show_status_details) {
		echo '<tr>';
	} else {
		echo '<tr class="warning">';
	}
	if (!isset($config['id_hidden']))
		echo '<td>', $submission_id_str, '</td>';
	if (!isset($config['problem_hidden']))
		echo '<td>', $problem_link_str, '</td>';
	if (!isset($config['submitter_hidden']))
		echo '<td>', $submitter_link_str, '</td>';
	if (!isset($config['result_hidden']))
		echo '<td>', $submission_status_str, '</td>';
	if (!isset($config['used_time_hidden']))
		echo '<td>', $used_time_str, '</td>';
	if (!isset($config['used_memory_hidden']))
		echo '<td>', $used_memory_str, '</td>';
	echo '<td>', $language_str, '</td>';
	echo '<td>', $size_str, '</td>';
	if (!isset($config['submit_time_hidden']))
		echo '<td>', $submit_time_str, '</td>';
	if (!isset($config['judge_time_hidden']))
		echo '<td>', $judge_time_str, '</td>';
	echo '</tr>';
	if ($show_status_details) {
		echo '<tr id="', "status_details_{$submission['id']}", '" class="info">';
		echo getSubmissionStatusDetails($submission);
		echo '</tr>';
		echo '<script>update_judgement_status_details(', $submission['id'], ')</script>';
	}
}

function echoSubmissionsListOnlyOne($submission, $config, $user) {
	echo '<div class="table-responsive">';
	echo '<table class="table table-bordered table-text-center">';
	echo '<thead>';
	echo '<tr>';
	if (!isset($config['id_hidden']))
		echo '<th>ID</th>';
	if (!isset($config['problem_hidden']))
		echo '<th>', UOJLocale::get('problems::problem'), '</th>';
	if (!isset($config['submitter_hidden']))
		echo '<th>', UOJLocale::get('problems::submitter'), '</th>';
	if (!isset($config['result_hidden']))
		echo '<th>', UOJLocale::get('problems::result'), '</th>';
	if (!isset($config['used_time_hidden']))
		echo '<th>', UOJLocale::get('problems::used time'), '</th>';
	if (!isset($config['used_memory_hidden']))
		echo '<th>', UOJLocale::get('problems::used memory'), '</th>';
	echo '<th>', UOJLocale::get('problems::language'), '</th>';
	echo '<th>', UOJLocale::get('problems::file size'), '</th>';
	if (!isset($config['submit_time_hidden']))
		echo '<th>', UOJLocale::get('problems::submit time'), '</th>';
	if (!isset($config['judge_time_hidden']))
		echo '<th>', UOJLocale::get('problems::judge time'), '</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	echoSubmission($submission, $config, $user);
	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}

function echoSubmissionsList($cond, $tail, $config, $user) {
	$header_row = '<tr>';
	$col_names = array();
	$col_names[] = 'submissions.status_details';
	$col_names[] = 'submissions.status';
	$col_names[] = 'submissions.result_error';
	$col_names[] = 'submissions.score';
	
	if (!isset($config['id_hidden'])) {
		$header_row .= '<th>ID</th>';
		$col_names[] = 'submissions.id';
	}
	if (!isset($config['problem_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
		$col_names[] = 'submissions.problem_id';
		$col_names[] = 'submissions.contest_id';
	}
	if (!isset($config['submitter_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::submitter') . '</th>';
		$col_names[] = 'submissions.submitter';
	}
	if (!isset($config['result_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::result') . '</th>';
	}
	if (!isset($config['used_time_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::used time') . '</th>';
		$col_names[] = 'submissions.used_time';
	}
	if (!isset($config['used_memory_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::used memory') . '</th>';
		$col_names[] = 'submissions.used_memory';
	}
	$header_row .= '<th>' . UOJLocale::get('problems::language') . '</th>';
	$col_names[] = 'submissions.language';
	$header_row .= '<th>' . UOJLocale::get('problems::file size') . '</th>';
	$col_names[] = 'submissions.tot_size';

	if (!isset($config['submit_time_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::submit time') . '</th>';
		$col_names[] = 'submissions.submit_time';
	}
	if (!isset($config['judge_time_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::judge time') . '</th>';
		$col_names[] = 'submissions.judge_time';
	}
	$header_row .= '</tr>';
	
	$table_name = isset($config['table_name']) ? $config['table_name'] : 'submissions';

	if (!isProblemManager($user)) {
		DB::query("create temporary table group_t (group_name varchar(20) primary key) engine = memory default charset=utf8 as (select group_name from group_members where username = '{$user['username']}' and member_state != 'W')");
		DB::query("create temporary table contest_t (id int(10) primary key) engine = memory as (select distinct contest_id id from contests_visibility where group_name in (select group_name from group_t))");
		DB::query("create temporary table problem_t (id int(10) primary key) engine = memory as (select distinct problem_id id from problems_visibility where group_name in (select group_name from group_t))");
		DB::query("create temporary table problem_t1 (id int(10) primary key) engine = memory as (select id from problem_t where id in (select id from problems where problems.is_hidden = 0) or id in (select problem_id from problems_permissions where username = '{$user['username']}'))");

		$contest_conds = array();

		$in_progress_contests = DB::selectAll("select id from contests where status = 'unfinished' and now() <= date_add(start_time, interval last_min minute)");
		$used_contests = array(0);
		foreach ($in_progress_contests as $contest_id) {
			$contest = queryContest($contest_id['id']);
			genMoreContestInfo($contest);
			if (isset($contest['extra_config']['is_group_contest'])) {
				$agent = queryRegisteredGroup($user, $contest, true);
				if ($agent !== false) $agent = $agent['group_name'];
			} else {
				$agent = $user['username'];
			}
			if (!hasContestPermission($user, $contest) and queryOnlymyselfLimit($contest) === SUBMISSION_NONE_LIMIT) {
				if ($agent !== false) {
					$contest_conds[] = <<<EOD
((contest_id = {$contest_id['id']}) and (submissions.submitter = '{$agent}'))
EOD;
				}
				DB::delete("delete from contest_t where id = {$contest['id']}");
			}
		}

		$contest_conds[] = <<<EOD
(contest_id in (select id from contest_t) and submissions.problem_id in (select id from problem_t))
EOD;

		$contest_conds[] = <<<EOD
(contest_id = 0 and submissions.problem_id in (select id from problem_t1))
EOD;

		$permission_cond = implode(' or ', $contest_conds);

		if ($cond !== '1') {
			$cond = "($cond) and ($permission_cond)";
		} else {
			$cond = "($permission_cond)";
		}
	}
	
	$table_config = isset($config['table_config']) ? $config['table_config'] : null;
	
	echoLongTable($col_names, $table_name, $cond, $tail, $header_row,
		function($submission) use($config, $user) {
			echoSubmission($submission, $config, $user);
		}, $table_config);
}

function echoSubmissionMessages($messages) {
	echo '<!-- credit to https://bootsnipp.com/snippets/xrKXW -->';
	echo '<div class="list-group timeline">';
	foreach ($messages as $mes) {
		$cls = 'list-group-item';
		$main_message = "";
		$main_message .= '<ul class="list-group-item-text list-inline text-info">';
		$main_message .= '<li>';
		if ($mes['time'])
			$main_message .= "<strong>[{$mes['time']}]</strong>";
		else
			$main_message .= '<strong>[error]</strong>';
		$main_message .= '</li>';
		$extra = null;
		switch($mes['message_type']){
			case 'submit':
				$main_message .= '<li>';
				$main_message .= '提交';
				$main_message .= '</li>';
				break;
			case 'judgement':
				$main_message .= '<li>';
				$main_message .= '评测';
				$main_message .= '</li>';
				break;
			case 'current_submission_status':
				$main_message .= '<li>';
				$main_message .= '当前提交记录状态';
				$main_message .= '</li>';
				break;
		}
		$main_message .= '</ul>';
		switch($mes['message_type']){
			case 'submit':
				break;
			case 'judgement':
				$main_message .= '<ul class="list-group-item-text list-inline">';
				$main_message .= '<li>';
				$main_message .= '<strong>测评结果：</strong>';
				$main_message .= isset($mes['result_error'])?"<span class=\"small\">{$mes['result_error']}</span>":"<span class=\"uoj-score\">{$mes['score']}</span>";
				$main_message .= '</li>';
				if (!isset($mes['result_error'])) {
					$main_message .= '<li>';
					$main_message .= '<strong>用时：</strong>';
					$main_message .= $mes['used_time'] . 'ms';
					$main_message .= '</li>';
					$main_message .= '<li>';
					$main_message .= '<strong>内存：</strong>';
					$main_message .= $mes['used_memory'] . 'KiB';
					$main_message .= '</li>';
				}
				$main_message .= '</ul>';
				$extra = '<a href="'."/submission/{$mes['submission_id']}?judgement_id={$mes['judgement_id']}".'"><span class="glyphicon glyphicon-info-sign"></span> 查看</a>';
				break;
			case 'current_submission_status':
				$extra = '<a href="'."/submission/{$mes['submission_id']}".'"><span class="glyphicon glyphicon-info-sign"></span> 查看</a>';
				break;
		}
		echo '<div class="', $cls, '">';
		if ($extra) {
			if(!isset($split_cls))
				$split_cls = ['col-sm-10 vcenter-sm', 'col-sm-2 vcenter-sm'];
			echo '<div class="row">';
			echo '<div class="', $split_cls[0], '">';
			echo $main_message , '</div>';
			echo '<div class="', $split_cls[1],' text-right">', $extra, '</div>';
			echo '</div>';
		}
		else
			echo $main_message;
		echo '</div>';
	}
	echo '</div>';
}

function echoSubmissionTimeline($submission, $time_now) {
	$hiss = DB::select("select judge_time as time, 'judgement' as message_type, submission_id, id as judgement_id, judge_time, judger_name, status, result_error, score, used_time, used_memory from submissions_history where submission_id = {$submission['id']} order by judge_time desc");
	$messages = array();
	$messages[] = array(
		'time' => $time_now,
		'message_type' => 'current_submission_status',
		'submission_id' => $submission['id']
	);
	while ($his = DB::fetch($hiss)) {
		$messages[] = $his;
	}
	$messages[] = array(
		'time' => $submission['submit_time'],
		'message_type' => 'submit',
		'submission_id' => $submission['id']
	);
	echoSubmissionMessages($messages);
}

function echoSubmissionContent($submission, $requirement) {
	$zip_file = new ZipArchive();
	$submission_content = json_decode($submission['content'], true);
	$zip_file->open(UOJContext::storagePath().$submission_content['file_name']);
	
	$config = array();
	foreach ($submission_content['config'] as $config_key => $config_val) {
		$config[$config_val[0]] = $config_val[1];
	}
	
	foreach ($requirement as $req) {
		if ($req['type'] == "source code") {
			$file_content = $zip_file->getFromName("{$req['name']}.code");
			$file_content = uojTextEncode($file_content, array('allow_CR' => true, 'html_escape' => true));
			$file_language = htmlspecialchars($config["{$req['name']}_language"]);
			$footer_text = UOJLocale::get('problems::source code').', '.UOJLocale::get('problems::language').': '.$file_language;
			switch ($file_language) {
				case 'C++98':
				case 'C++11':
				case 'C++14':
				case 'C++17':
					$sh_class = 'sh_cpp';
					break;
				case 'Python2':
				case 'Python3':
					$sh_class = 'sh_python';
					break;
				case 'Java8':
				case 'Java11':
					$sh_class = 'sh_java';
					break;
				case 'C99':
				case 'C11':
					$sh_class = 'sh_c';
					break;
				case 'Pascal':
					$sh_class = 'sh_pascal';
					break;
				default:
					$sh_class = '';
					break;
			}
			echo '<div class="panel panel-info">';
			echo '<div class="panel-heading">';
			echo '<h4 class="panel-title">', $req['name'], '</h4>';
			echo '</div>';
			echo '<div class="panel-body">';
			echo '<pre><code class="', $sh_class, '">', $file_content, '
</code></pre>';
			echo '</div>';
			echo '<div class="panel-footer">', $footer_text, '</div>';
			echo '</div>';
		}
		else if ($req['type'] == "text") {
			$file_content = $zip_file->getFromName($req['file_name'], 504);
			$file_content = strOmit($file_content, 500);
			$file_content = uojTextEncode($file_content, array('allow_CR' => true, 'html_escape' => true));
			$footer_text = UOJLocale::get('problems::text file');
			echo '<div class="panel panel-info">';
			echo '<div class="panel-heading">';
			echo '<h4 class="panel-title">', $req['file_name'], '</h4>';
			echo '</div>';
			echo '<div class="panel-body">';
			echo '<pre>
', $file_content, '
</pre>';
			echo '</div>';
			echo '<div class="panel-footer">', $footer_text, '</div>';
			echo '</div>';
		}
	}

	$zip_file->close();
}


class JudgementDetailsPrinter {
	private $name;
	private $styler;
	private $dom;
	
	private $subtask_num;

	private function _print_c($node) {
		foreach ($node->childNodes as $child) {
			if ($child->nodeName == '#text') {
				echo htmlspecialchars($child->nodeValue);
			} else {
				$this->_print($child);
			}
		}
	}
	private function _print($node) {
		if ($node->nodeName == 'error') {
			echo '<pre>
';
			$this->_print_c($node);
			echo '
</pre>';
		} elseif ($node->nodeName == 'tests') {
			echo '<div class="panel-group" id="', $this->name, '_details_accordion">';
			if ($this->styler->show_small_tip) {
				echo '<div class="text-right text-muted">', UOJLocale::get('small tip'), '</div>';
			}
			$this->_print_c($node);
			echo '</div>';
		} elseif ($node->nodeName == 'subtask') {
			$subtask_num = $node->getAttribute('num');
			$subtask_score = $node->getAttribute('score');
			$subtask_info = $node->getAttribute('info');
			
			echo '<div class="panel ', $this->styler->getTestInfoClass($subtask_info), '">';
			
			$accordion_parent = "{$this->name}_details_accordion";
			$accordion_collapse =  "{$accordion_parent}_collapse_subtask_{$subtask_num}";
			$accordion_collapse_accordion =  "{$accordion_collapse}_accordion";
			echo 	'<div class="panel-heading" data-toggle="collapse" data-parent="#', $accordion_parent, '" data-target="#', $accordion_collapse, '">';
			
			echo 		'<div class="row">';
			echo 			'<div class="col-sm-2">';
			echo 				'<h3 class="panel-title">', 'Subtask #', $subtask_num, ':', '</h3>';
			echo 			'</div>';
			
			if ($this->styler->show_score) {
				echo 		'<div class="col-sm-2">';
				echo 			'score: ', $subtask_score;
				echo 		'</div>';
				echo 		'<div class="col-sm-2">';
				echo 			htmlspecialchars($subtask_info);
				echo 		'</div>';
			} else {
				echo 		'<div class="col-sm-4">';
				echo 			htmlspecialchars($subtask_info);
				echo 		'</div>';
			}

			echo 		'</div>';
			echo 	'</div>';
			
			echo 	'<div id="', $accordion_collapse, '" class="panel-collapse collapse">';
			echo 		'<div class="panel-body">';

			echo 			'<div id="', $accordion_collapse_accordion, '" class="panel-group">';
			$this->subtask_num = $subtask_num;
			$this->_print_c($node);
			$this->subtask_num = null;
			echo 			'</div>';

			echo 		'</div>';
			echo 	'</div>';
			echo '</div>';
		} elseif ($node->nodeName == 'test') {
			$test_info = $node->getAttribute('info');
			$test_num = $node->getAttribute('num');
			$test_dnum = $node->getAttribute('dnum');
			$test_score = $node->getAttribute('score');
			$test_time = $node->getAttribute('time');
			$test_memory = $node->getAttribute('memory');

			echo '<div class="panel ', $this->styler->getTestInfoClass($test_info), '">';
			
			$accordion_parent = "{$this->name}_details_accordion";
			if ($test_dnum) {
				$accordion_collapse =  "{$accordion_parent}_collapse_subtask_{$test_dnum}";
			} else {		
				if ($this->subtask_num != null)
					$accordion_parent .= "_collapse_subtask_{$this->subtask_num}_accordion";
				$accordion_collapse = "{$accordion_parent}_collapse_test_{$test_num}";
			}

			if ($this->styler->shouldFadeDetails($test_info) and !$test_dnum) {
				echo '<div class="panel-heading">';
			} else {
				echo '<div class="panel-heading" data-toggle="collapse" data-parent="#', $accordion_parent, '" data-target="#', $accordion_collapse, '">';
			}

			echo '<div class="row">';
			echo '<div class="col-sm-2">';
			if ($test_dnum > 0) {
				echo '<h4 class="panel-title">', 'Dependency #', $test_dnum, ':', '</h4>';
			} elseif ($test_num > 0) {
				echo '<h4 class="panel-title">', 'Test #', $test_num, ':', '</h4>';
			} else {
				echo '<h4 class="panel-title">', 'Extra Test:', '</h4>';
			}
			echo '</div>';
				
			if ($this->styler->show_score) {
				echo '<div class="col-sm-2">';
				echo 'score: ', $test_score;
				echo '</div>';
				echo '<div class="col-sm-2">';
				echo htmlspecialchars($test_info);
				echo '</div>';
			} else {
				echo '<div class="col-sm-4">';
				echo htmlspecialchars($test_info);
				echo '</div>';
			}
				
			echo '<div class="col-sm-3">';
			if (is_numeric($test_time) and $test_time >= 0) {
				echo 'time: ', $test_time, 'ms';
			}
			echo '</div>';

			echo '<div class="col-sm-3">';
			if (!$test_dnum and $test_memory >= 0) {
				echo 'memory: ', $test_memory, 'kb';
			}
			echo '</div>';

			echo '</div>';
			echo '</div>';

			if (!$this->styler->shouldFadeDetails($test_info) and !$test_dnum) {
				$accordion_collapse_class = 'panel-collapse collapse';
				if ($this->styler->collapse_in) {
					$accordion_collapse_class .= ' in';
				}
				echo '<div id="', $accordion_collapse, '" class="', $accordion_collapse_class, '">';
				echo '<div class="panel-body">';

				$this->_print_c($node);

				echo '</div>';
				echo '</div>';
			}

			echo '</div>';
		} elseif ($node->nodeName == 'custom-test') {
			$test_info = $node->getAttribute('info');
			$test_time = $node->getAttribute('time');
			$test_memory = $node->getAttribute('memory');

			echo '<div class="panel ', $this->styler->getTestInfoClass($test_info), '">';
			
			$accordion_parent = "{$this->name}_details_accordion";
			$accordion_collapse = "{$accordion_parent}_collapse_custom_test";
			if (!$this->styler->shouldFadeDetails($test_info)) {
				echo '<div class="panel-heading" data-toggle="collapse" data-parent="#', $accordion_parent, '" data-target="#', $accordion_collapse, '">';
			} else {
				echo '<div class="panel-heading">';
			}
			echo '<div class="row">';
			echo '<div class="col-sm-2">';
			echo '<h4 class="panel-title">', 'Custom Test: ', '</h4>';
			echo '</div>';
				
			echo '<div class="col-sm-4">';
			echo HTML::escape($test_info);
			echo '</div>';
				
			echo '<div class="col-sm-3">';
			if ($test_time >= 0) {
				echo 'time: ', $test_time, 'ms';
			}
			echo '</div>';

			echo '<div class="col-sm-3">';
			if ($test_memory >= 0) {
				echo 'memory: ', $test_memory, 'kb';
			}
			echo '</div>';

			echo '</div>';
			echo '</div>';

			if (!$this->styler->shouldFadeDetails($test_info)) {
				$accordion_collapse_class = 'panel-collapse collapse';
				if ($this->styler->collapse_in) {
					$accordion_collapse_class .= ' in';
				}
				echo '<div id="', $accordion_collapse, '" class="', $accordion_collapse_class, '">';
				echo '<div class="panel-body">';

				$this->_print_c($node);

				echo '</div>';
				echo '</div>';

				echo '</div>';
			}
		} elseif ($node->nodeName == 'in') {
			echo '<h4>input:</h4><pre>
';
			$this->_print_c($node);
			echo '
</pre>';
		} elseif ($node->nodeName == 'out') {
			echo '<h4>output:</h4><pre>
';
			$this->_print_c($node);
			echo '
</pre>';
		} elseif ($node->nodeName == 'res') {
			echo '<h4>result:</h4><pre>
';
			$this->_print_c($node);
			echo '
</pre>';
		} else {
			echo '<', $node->nodeName;
			foreach ($node->attributes as $attr) {
				echo ' ', $attr->name, '="', htmlspecialchars($attr->value), '"';
			}
			echo '>';
			$this->_print_c($node);
			echo '</', $node->nodeName, '>';
		}
	}

	public function __construct($details, $styler, $name) {
		$this->name = $name;
		$this->styler = $styler;
		$this->details = $details;
		$this->dom = new DOMDocument();
		if (!$this->dom->loadXML($this->details)) {
			throw new Exception('XML syntax error');
		}
		$this->details = '';
	}
	public function printHTML() {
		$this->subtask_num = null;
		$this->_print($this->dom->documentElement);
	}
}

function echoJudgementDetails($raw_details, $styler, $name) {
	try {
		if (!$raw_details) {
			return;
		}
		$printer = new JudgementDetailsPrinter($raw_details, $styler, $name);
		$printer->printHTML();
	} catch (Exception $e) {
		echo 'Failed to show details';
	}
}

class SubmissionDetailsStyler {
	public $show_score = true;
	public $show_small_tip = true;
	public $collapse_in = false;
	public $fade_all_details = false;
	public function getTestInfoClass($info) {
		if ($info === 'Accepted' || $info === 'Extra Test Passed') {
			return 'panel-uoj-accepted';
		} elseif ($info === 'Acceptable Answer') {
			return 'panel-uoj-acceptable-answer';
		} elseif (strpos($info, 'Time Limit Exceeded') !== false) {
			return 'panel-uoj-tle';
		} else {
			return 'panel-uoj-wrong';
		}
	}
	public function shouldFadeDetails($info) {
		return $this->fade_all_details || $info == 'Extra Test Passed';
	}
}
class CustomTestSubmissionDetailsStyler {
	public $show_score = true;
	public $show_small_tip = false;
	public $collapse_in = true;
	public $fade_all_details = false;
	public function getTestInfoClass($info) {
		if ($info == 'Success') {
			return 'panel-uoj-accepted';
		} elseif ($info == 'Time Limit Exceeded') {
			return 'panel-uoj-tle';
		} elseif ($info == 'Acceptable Answer') {
			return 'panel-uoj-acceptable-answer';
		} else {
			return 'panel-uoj-wrong';
		}
	}
	public function shouldFadeDetails($info) {
		return $this->fade_all_details;
	}
}
class HackDetailsStyler {
	public $show_score = false;
	public $show_small_tip = false;
	public $collapse_in = true;
	public $fade_all_details = false;
	public function getTestInfoClass($info) {
		if ($info == 'Accepted' || $info == 'Extra Test Passed') {
			return 'panel-uoj-accepted';
		} elseif ($info == 'Time Limit Exceeded') {
			return 'panel-uoj-tle';
		} elseif ($info == 'Acceptable Answer') {
			return 'panel-uoj-acceptable-answer';
		} else {
			return 'panel-uoj-wrong';
		}
	}
	public function shouldFadeDetails($info) {
		return $this->fade_all_details;
	}
}

function echoSubmissionDetails($submission_details, $name) {
	echoJudgementDetails($submission_details, new SubmissionDetailsStyler(), $name);
}
function echoCustomTestSubmissionDetails($submission_details, $name) {
	echoJudgementDetails($submission_details, new CustomTestSubmissionDetailsStyler(), $name);
}
function echoHackDetails($hack_details, $name) {
	echoJudgementDetails($hack_details, new HackDetailsStyler(), $name);
}

function echoHack($hack, $config, $user) {
	$problem = queryProblemBrief($hack['problem_id']);
	$hasProblemPermission = isProblemVisible($user, $problem);

	$hack_id_str = "<a href=\"/hack/{$hack['id']}\">#{$hack['id']}</a>";
	$submission_id_str = "<a href=\"/submission/{$hack['submission_id']}\">#{$hack['submission_id']}</a>";
	$problem_link_str = '/';
	$hacker_link_str = '/';
	$owner_link_str = '/';
	$hack_status_str = '/';
	$submit_time_str = $hack['submit_time'];
	$judge_time_str = $hack['judge_time'];

	if ($hasProblemPermission === true) {
		if ($hack['contest_id']) {
			$problem_link_str = getContestProblemLink($problem, $hack['contest_id'], '!id_and_title');
		} else {
			$problem_link_str = getProblemLink($problem, '!id_and_title');
		}

		$hacker_link_str = getUserLink($hack['hacker']);
		$owner_link_str = getUserOrGroupLink($hack['owner']);

		if($hack['judge_time'] == null) {
			$hack_status_str = "<a href=\"/hack/{$hack['id']}\">Waiting</a>";
		} elseif ($hack['success'] == null) {
			$hack_status_str = "<a href=\"/hack/{$hack['id']}\">Judging</a>";
		} elseif ($hack['success']) {
			$hack_status_str = "<a href=\"/hack/{$hack['id']}\" class=\"uoj-status\" data-success=\"1\"><strong>Success!</strong></a>";
		} else {
			$hack_status_str = "<a href=\"/hack/{$hack['id']}\" class=\"uoj-status\" data-success=\"0\"><strong>Failed.</strong></a></td>";
		}
	}
	echo '<tr>';
	if (!isset($config['id_hidden']))
		echo '<td>', $hack_id_str, '</td>';
	if (!isset($config['submission_hidden']))
		echo '<td>', $submission_id_str, '</td>';
	if (!isset($config['problem_hidden']))
		echo '<td>', $problem_link_str, '</td>';
	if (!isset($config['hacker_hidden']))
		echo '<td>', $hacker_link_str, '</td>';
	if (!isset($config['owner_hidden']))
		echo '<td>', $owner_link_str, '</td>';
	if (!isset($config['result_hidden']))
		echo '<td>', $hack_status_str, '</td>';	
	if (!isset($config['submit_time_hidden']))
		echo '<td>', $submit_time_str, '</td>';
	if (!isset($config['judge_time_hidden']))
		echo '<td>', $judge_time_str, '</td>';
	echo '</tr>';
}
function echoHackListOnlyOne($hack, $config, $user) {
	echo '<div class="table-responsive">';
	echo '<table class="table table-bordered table-text-center">';
	echo '<thead>';
	echo '<tr>';
	if (!isset($config['id_hidden']))
		echo '<th>ID</th>';
	if (!isset($config['submission_id_hidden']))
		echo '<th>', UOJLocale::get('problems::submission id'), '</th>';
	if (!isset($config['problem_hidden']))
		echo '<th>', UOJLocale::get('problems::problem'), '</th>';
	if (!isset($config['hacker_hidden']))
		echo '<th>', UOJLocale::get('problems::hacker'), '</th>';
	if (!isset($config['owner_hidden']))
		echo '<th>', UOJLocale::get('problems::owner'), '</th>';
	if (!isset($config['result_hidden']))
		echo '<th>', UOJLocale::get('problems::result'), '</th>';
	if (!isset($config['submit_time_hidden']))
		echo '<th>', UOJLocale::get('problems::submit time'), '</th>';
	if (!isset($config['judge_time_hidden']))
		echo '<th>', UOJLocale::get('problems::judge time'), '</th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	echoHack($hack, $config, $user);
	echo '</tbody>';
	echo '</table>';
	echo '</div>';
}
function echoHacksList($cond, $tail, $config, $user) {
	$header_row = '<tr>';
	$col_names = array();
	
	$col_names[] = 'id';
	$col_names[] = 'success';
	$col_names[] = 'judge_time';
	
	if (!isset($config['id_hidden'])) {
		$header_row .= '<th>ID</th>';
	}
	if (!isset($config['submission_id_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::submission id') . '</th>';
		$col_names[] = 'submission_id';
	}
	if (!isset($config['problem_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::problem') . '</th>';
		$col_names[] = 'problem_id';
	}
	if (!isset($config['hacker_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::hacker') . '</th>';
		$col_names[] = 'hacker';
	}
	if (!isset($config['owner_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::owner') . '</th>';
		$col_names[] = 'owner';
	}
	if (!isset($config['result_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::result') . '</th>';
	}
	if (!isset($config['submit_time_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::submit time') . '</th>';
		$col_names[] = 'submit_time';
	}
	if (!isset($config['judge_time_hidden'])) {
		$header_row .= '<th>' . UOJLocale::get('problems::judge time') . '</th>';
	}
	$header_row .= '</tr>';

	if (!isProblemManager($user)) {
		DB::query("create temporary table group_t (group_name varchar(20) primary key) engine = memory default charset=utf8 as (select group_name from group_members where username = '{$user['username']}' and member_state != 'W')");
		DB::query("create temporary table contest_t (id int(10) primary key) engine = memory as (select distinct contest_id id from contests_visibility where group_name in (select group_name from group_t))");
		DB::query("create temporary table problem_t (id int(10) primary key) engine = memory as (select distinct problem_id id from problems_visibility where group_name in (select group_name from group_t))");
		DB::query("create temporary table problem_t1 (id int(10) primary key) engine = memory as (select id from problem_t where id in (select id from problems where problems.is_hidden = 0) or id in (select problem_id from problems_permissions where username = '{$user['username']}'))");

		$contest_conds = array();

		$in_progress_contests = DB::selectAll("select id from contests where status = 'unfinished' and now() <= date_add(start_time, interval last_min minute)");
		$used_contests = array(0);
		foreach ($in_progress_contests as $contest_id) {
			$contest = queryContest($contest_id['id']);
			genMoreContestInfo($contest);
			if (isset($contest['extra_config']['is_group_contest'])) {
				$agent = queryRegisteredGroup($user, $contest, true);
				if ($agent !== false) $agent = $agent['group_name'];
			} else {
				$agent = $user['username'];
			}
			if (!hasContestPermission($user, $contest) and queryOnlymyselfLimit($contest) === SUBMISSION_NONE_LIMIT) {
				if ($agent !== false) {
					$contest_conds[] = <<<EOD
((contest_id = {$contest_id['id']}) and (hacks.owner = '{$agent}'))
EOD;
				}
				DB::delete("delete from contest_t where id = {$contest['id']}");
			}
		}

		$contest_conds[] = <<<EOD
(contest_id in (select id from contest_t) and problem_id in (select id from problem_t))
EOD;

		$contest_conds[] = <<<EOD
(contest_id = 0 and problem_id in (select id from problem_t1))
EOD;

		$permission_cond = implode(' or ', $contest_conds);
		
		if ($cond !== '1') {
			$cond = "($cond) and ($permission_cond)";
		} else {
			$cond = "($permission_cond)";
		}
	}

	echoLongTable($col_names, 'hacks', $cond, $tail, $header_row,
		function($hacks) use($config, $user) {
			echoHack($hacks, $config, $user);
		}, null);
}

function echoBlog($blog, $config = array()) {
	$default_config = array(
		'blog' => $blog,
		'show_title_only' => false,
		'content_only' => false,
		'is_preview' => false
	);
	foreach ($default_config as $key => $val) {
		if (!isset($config[$key])) {
			$config[$key] = $val;
		}
	}
	uojIncludeView('blog-preview', $config);
}
function echoBlogTag($tag) {
	echo '<a class="uoj-blog-tag"><span class="badge">', HTML::escape($tag), '</span></a>';
}

function echoUOJPageHeader($page_title, $extra_config = array()) {
	global $REQUIRE_LIB;
	$config = UOJContext::pageConfig();
	$config['REQUIRE_LIB'] = $REQUIRE_LIB;
	$config['PageTitle'] = $page_title;
	$config = array_merge($config, $extra_config);
	uojIncludeView('page-header', $config);
}
function echoUOJPageFooter($config = array()) {
	uojIncludeView('page-footer', $config);
}

function echoRanklist($config = array()) {
	$header_row = '';
	$header_row .= '<tr>';
	$header_row .= '<th style="width: 5em;">#</th>';
	$header_row .= '<th style="width: 14em;">' . UOJLocale::get('username') . '</th>';
	$header_row .= '<th style="width: 50em;">' . UOJLocale::get('motto') . '</th>';
	$header_row .= '<th style="width: 5em;">' . UOJLocale::get('rating') . '</th>';
	$header_row .= '</tr>';

	$users = array();

	if (isset($config['show_all'])) {
		$banned_cond = '1';
		$second_cond = '0';
	} else {
		$banned_cond = "username not in (select username from group_members where (group_name = 'banned' or group_name = 'outdated') and member_state = 'U')";
		$second_cond = "exists (select 1 from group_members where user_info.username = group_members.username and group_name = 'outdated' and member_state = 'U')";
	}
	$alive = (int)DB::selectCount("select count(*) from user_info where {$banned_cond}");

	$print_row = function($user, $now_cnt) use(&$users, &$config, &$banned_cond, &$alive) {
		if ($now_cnt > $alive) {
			$rank = '-';
		} elseif (!$users) {
			$rank = DB::selectCount("select count(*) from user_info where {$banned_cond} and rating > {$user['rating']}") + 1;
		} elseif ($user['rating'] == $users[count($users) - 1]['rating']) {
			$rank = $users[count($users) - 1]['rank'];
		} else {
			$rank = $now_cnt;
		}

		$user['rank'] = $rank;

		if (isset($config['more_details'])) {
			if (Auth::id() === $user['username']) {
				echo '<tr class="info">';
			} else {
				echo '<tr>';
			}
		} else {
			echo '<tr>';
		}

		echo '<td>', $user['rank'], '</td>';
		echo '<td>', getUserLink($user['username']), '</td>';
		echo '<td>', HTML::escape(queryUserMotto($user['username'])), '</td>';
		echo '<td>', $user['rating'], '</td>';
		echo '</tr>';

		$users[] = $user;
	};
	$col_names = array('username', 'rating');

	if (isset($config['top10'])) {
		$tail = 'order by rating desc, username asc limit 10';
	} else {
		$tail = 'order by outdated asc, rating desc, username asc';
		$config['custom_query'] = 'select * from (select ' . join($col_names, ',') . ", 0 outdated from user_info where {$banned_cond}) _1 union select * from (select " . join($col_names, ',') . ", 1 outdated from user_info where {$second_cond}) _2 {$tail}";
		$config['custom_query_count'] = "select count(*) from user_info where ({$banned_cond}) or ({$second_cond})";
	}

	$config['get_row_index'] = '';
	echoLongTable($col_names, 'user_info', $banned_cond, $tail, $header_row, $print_row, $config);
}

function echoACRanklist($config = array()) {
	$header_row = '';
	$header_row .= '<tr>';
	$header_row .= '<th style="width: 5em;">#</th>';
	$header_row .= '<th style="width: 14em;">' . UOJLocale::get('username') . '</th>';
	$header_row .= '<th style="width: 50em;">' . UOJLocale::get('motto') . '</th>';
	$header_row .= '<th style="width: 5em;">' . UOJLocale::get('account') . '</th>';
	$header_row .= '</tr>';

	$users = array();

	if (isset($config['show_all'])) {
		$banned_cond = '1';
	} else {
		$banned_cond = "username not in (select username from group_members where group_name = 'banned' and member_state = 'U')";
	}

	$print_row = function($user, $now_cnt) use(&$users, &$config, &$banned_cond) {
		if (!$users) {
			$rank = DB::selectCount("select count(*) from user_info where {$banned_cond} and ac_num > {$user['ac_num']}") + 1;
		} else if ($user['ac_num'] == $users[count($users) - 1]['ac_num']) {
			$rank = $users[count($users) - 1]['rank'];
		} else {
			$rank = $now_cnt;
		}

		$user['rank'] = $rank;

		if (isset($config['more_details'])) {
			if (Auth::id() === $user['username']) {
				echo '<tr class="info">';
			} else {
				echo '<tr>';
			}
		} else {
			echo '<tr>';
		}

		echo '<td>', $user['rank'] . '</td>';
		echo '<td>', getUserLink($user['username']), '</td>';
		echo '<td>', HTML::escape(queryUserMotto($user['username'])), '</td>';
		echo '<td>', $user['ac_num'], '</td>';
		echo '</tr>';

		$users[] = $user;
	};
	$col_names = array('username', 'ac_num');
	$tail = 'order by ac_num desc, username asc';

	if (isset($config['top10'])) $tail .= ' limit 10';

	$config['get_row_index'] = '';
	echoLongTable($col_names, 'user_info', $banned_cond, $tail, $header_row, $print_row, $config);
}

function echoGrouplist($config = array(), $mygroups = false) {
	$header_row = '';
	$header_row .= '<tr>';
	$header_row .= '<th style="width: 5em;">#</th>';
	$header_row .= '<th style="width: 14em;">' . UOJLocale::get('groupname') . '</th>';
	$header_row .= '<th style="width: 50em;">' . UOJLocale::get('description') . '</th>';
	$header_row .= '<th style="width: 5em;">' . UOJLocale::get('rating') . '</th>';
	$header_row .= '</tr>';

	$print_row = function($g, $now_cnt) use(&$config) {
		if ($config['is_system_group']) $g['rank'] = '-';

		if (isset($config['more_details'])) {
			if (isGroupManager(Auth::user(), $g)) {
				echo '<tr class="info">';
			} elseif (isGroupMember(Auth::user(), $g)) {
				echo '<tr class="success">';
			} elseif (isGroupAssociated(Auth::user(), $g)) {
				echo '<tr class="warning">';
			} else {
				echo '<tr>';
			}
		} else {
			echo '<tr>';
		}

		echo '<td>', $g['rank'], '</td>';
		echo '<td>', getGroupLink($g['group_name']), '</td>';
		echo '<td>', HTML::escape($g['description']), '</td>';
		echo '<td>', $g['rating'], '</td>';
		echo '</tr>';
	};
	$col_names = array('group_name', 'rating', 'description');
	$tail = 'order by rating desc, group_name asc';
	$config['get_row_index'] = '';

	if ($mygroups) {
		DB::query("create temporary table group_t (group_name varchar(20) primary key) engine = memory default charset=utf8 as (select group_name from group_members where username = '" . Auth::id() ."' and member_state != 'W')");
	}

	$qheader = 'select group_name, rating, description, @a := @a + 1, @r := if(rating = @t, @r, @a) as rank, @t := rating from (select @a := 0, @r := 0, @t := -1) _1, group_info';

	if (isset($config['top10'])) {
		$config['is_system_group'] = false;
		$config['echo_full'] = true;
		$cond = 'group_type = "N"';
		$config['custom_query'] = "{$qheader} where {$cond} {$tail} limit 10";
		echoLongTable($col_names, 'group_info', $cond, $tail, $header_row, $print_row, $config);
	} else {
		$config['is_system_group'] = true;
		echo '<h4>', UOJLocale::get('system groups'), '</h4>';
		$cond = 'group_type = "S"';
		if ($mygroups) {
			$cond .= ' and group_name in (select group_name from group_t)';
		}
		echoLongTable($col_names, 'group_info', $cond, $tail, $header_row, $print_row, $config);

		$config['is_system_group'] = false;
		echo '<h4>', UOJLocale::get('user groups'), '</h4>';
		$cond = 'group_type = "N"';
		if ($mygroups) {
			$config['custom_query'] = "select group_name, rating, description, rank from ({$qheader} where {$cond} {$tail}) _2 where group_name in (select group_name from group_t)";
			$cond .= ' and group_name in (select group_name from group_t)';
		} else {
			$config['custom_query'] = "select group_name, rating, description, rank from ({$qheader} where {$cond} {$tail}) _2";
		}
		echoLongTable($col_names, 'group_info', $cond, $tail, $header_row, $print_row, $config);
	}
}
