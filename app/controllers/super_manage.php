<?php
	requirePHPLib('form');
	requirePHPLib('judger');

	if (!(Auth::check() and isSuperUser(Auth::user()))) {
		become403Page();
	}

	if (!isset($_GET['tab'])) {
		redirectTo('/super-manage/analytics');
	}

	$cur_tab = $_GET['tab'];

	$tabs_info = array(
		'analytics' => array(
			'name' => '统计信息',
			'url' => '/super-manage/analytics',
			'method' => 'showAnalytics'
		),
		'judgerinfo' => array(
			'name' => '评测机信息',
			'url' => '/super-manage/judgerinfo',
			'method' => 'showJudgerInfo'
		),
		'users' => array(
			'name' => '用户操作',
			'url' => '/super-manage/users',
			'method' => 'showUserModification'
		),
		'groups' => array(
			'name' => '组操作',
			'url' => '/super-manage/groups',
			'method' => 'showGroupModification'
		),
		'blogs' => array(
			'name' => '博客管理',
			'url' => '/super-manage/blogs',
			'method' => 'showBlogLinks'
		),
		'submissions' => array(
			'name' => '提交记录',
			'url' => '/super-manage/submissions',
			'method' => 'showSubmissions'
		),
		'custom-test' => array(
			'name' => '自定义测试',
			'url' => '/super-manage/custom-test',
			'method' => 'showCustomTests'
		),
		'send-message' => array(
			'name' => '发送公告',
			'url' => '/super-manage/send-message',
			'method' => 'showSystemMessages'
		),
		'links' => array(
			'name' => '链接管理',
			'url' => '/super-manage/links',
			'method' => 'showFriendLinks'
		)
	);

	if (!isset($tabs_info[$cur_tab])) {
		become404Page();
	}

function printHeader() {
	echoUOJPageHeader('系统管理');
	global $tabs_info, $cur_tab;
	?>
	<div class="row">
	<div class="col-sm-3">
		<?= HTML::tablist($tabs_info, $cur_tab, 'nav-pills nav-stacked') ?>
	</div>
	<div class="col-sm-9">
	<?php
}

function showAnalytics() {
	$analytics = array();
	$analytics['problems_count'] = DB::selectCount('select count(*) from problems');
	$analytics['public_problems_count'] = DB::selectCount('select count(*) from problems where is_hidden = 0');
	$analytics['submissions_count'] = DB::selectCount('select count(*) from submissions');
	$analytics['submissions_count_today'] = DB::selectCount('select count(*) from submissions where to_days(submit_time) = to_days(now())');
	$analytics['hack_count'] = DB::selectCount('select count(*) from hacks');
	$analytics['hack_count_today'] = DB::selectCount('select count(*) from hacks where to_days(submit_time) = to_days(now())');
	$analytics['user_count'] = DB::selectCount('select count(*) from user_info');
	$analytics['contest_count'] = DB::selectCount('select count(*) from contests');
	$analytics['blog_count'] = DB::selectCount('select count(*) from blogs where is_draft = 0');
	$analytics['judge_client_count'] = DB::selectCount('select count(*) from judger_info');
	$analytics['judge_client_online'] = DB::selectCount('select count(*) from judger_info where latest_login > date_sub(now(), interval 1 minute)');

	printHeader();
	?>
	<div class="table-responsive">
		<table class="table table-bordered table-hover table-striped table-text-center">
			<thead>
				<tr>
					<th>参数</th>
					<th>数值</th>
				</tr>
			</thead>
			<tbody>
				<tr><td>题目数量</td><td><?= $analytics['problems_count'] ?></td></tr>
				<tr><td>公开题目数量</td><td><?= $analytics['public_problems_count'] ?></td></tr>
				<tr><td>评测数量</td><td><?= $analytics['submissions_count'] ?></td></tr>
				<tr><td>今日评测</td><td><?= $analytics['submissions_count_today'] ?></td></tr>
				<tr><td>Hack 总数</td><td><?= $analytics['hack_count'] ?></td></tr>
				<tr><td>今日 Hack 数</td><td><?= $analytics['hack_count_today'] ?></td></tr>
				<tr><td>用户总数</td><td><?= $analytics['user_count'] ?></td></tr>
				<tr><td>比赛数量</td><td><?= $analytics['contest_count'] ?></td></tr>
				<tr><td>博客数量</td><td><?= $analytics['blog_count'] ?></td></tr>
				<tr><td>评测机数量</td><td><?= $analytics['judge_client_count'] ?></td></tr>
				<tr><td>活动评测机数量</td><td><?= $analytics['judge_client_online'] ?></td></tr>
			</tbody>
		</table>
	</div>
	<?php
}

function showJudgerInfo() {
	printHeader();
	?>
	<div class="table-responsive">
		<table class="table table-bordered table-hover table-striped table-text-center">
			<thead>
				<tr>
					<th>评测机</th>
					<th>ip地址</th>
					<th>上次在线时间</th>
					<th>开启状态</th>
				</tr>
			</thead>
			<tbody>
				<?php foreach (DB::selectAll("select * from judger_info") as $judger) { ?>
					<tr><td><?= $judger["judger_name"] ?></td><td><?= $judger["ip"] ?></td><td><?= $judger["latest_login"] ?></td><td><?= $judger["enabled"] ?></td></tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	<?php
}

function showUserModification() {
	/*
	 * User modification
	 */
	$user_form = new UOJForm('user');
	$user_form->addInput('username', 'text', '用户名', '',
		function ($str) {
			foreach (explode(',', $str) as $line_id => $raw_str) {
				$username = trim($raw_str);
				if (!validateUsername($username)) {
					return '用户 #' . ($line_id + 1) . '：用户名不合法';
				}
				if (!queryUser($username)) {
					return '用户 #' . ($line_id + 1) . '：用户不存在';
				}
			}
			return '';
		},
		null
	);
	$user_form->addInput('realname', 'text', '真实姓名', '',
		function ($realname) {
			if ($realname === '%delete') {
				return '';
			}
			if (validateRealname($realname)) {
				return '';
			}
			return '真实姓名不合法';
		},
		null
	);

	$html = <<<EOD
<p>提示：若要同时对多个用户设置，可以用逗号将用户名隔开。</p>
<p>若要删除用户，请填入 <code>%delete</code>。</p>
<p><strong>若要修改权限，请右转组功能。</strong></p>
EOD;
	$user_form->appendHTML($html);

	$user_form->handle = function() {
		global $user_form;
		
		$users = $_POST['username'];
		$real = $_POST['realname'];

		foreach (explode(',', $users) as $raw_str) {
			$username = trim($raw_str);

			if ($real === '%delete') {
				deleteUser($username);
				continue;
			}
			
			$conf = queryUser($username)['extra_config'];
			$conf['realname'] = $real;
			$conf_s = json_encode($conf);
			DB::update("update user_info set extra_config='" . DB::escape($conf_s) . "' where username = '{$username}'");
		}
	};
	$user_form->runAtServer();

	/*
	 * User info
	 */

	$userlist_cols = array('username', 'extra_config', 'rating', 'ac_num', 'remote_addr', 'latest_login');
	$userlist_config = array('page_len' => 50);
	$userlist_header_row = '<tr>';
	$userlist_header_row .= '<th><a href="' . HTML::url(UOJContext::requestURI(), array('params' => array('sort' => 'username'))) . '">用户名</a></th>';
	// $userlist_header_row .= '<th><a href="' . HTML::url(UOJContext::requestURI(), array('params' => array('sort' => 'real_name'))) . '">真实姓名</a></th>';
	$userlist_header_row .= '<th>真实姓名</th>';
	$userlist_header_row .= '<th><a href="' . HTML::url(UOJContext::requestURI(), array('params' => array('sort' => 'rating'))) . '">Rating</a></th>';
	$userlist_header_row .= '<th><a href="' . HTML::url(UOJContext::requestURI(), array('params' => array('sort' => 'ac_num'))) . '">AC 题数</a></th>';
	$userlist_header_row .= '<th><a href="' . HTML::url(UOJContext::requestURI(), array('params' => array('sort' => 'remote_addr'))) . '">远程地址</a></th>';
	$userlist_header_row .= '<th><a href="' . HTML::url(UOJContext::requestURI(), array('params' => array('sort' => 'latest_active'))) . '">最后活动</a></th>';
	$userlist_header_row .= '<th title="全站管理员">S</th>';
	$userlist_header_row .= '<th title="题目管理员">P</th>';
	$userlist_header_row .= '<th title="题面管理员">T</th>';
	$userlist_header_row .= '<th title="封禁">B</th>';
	$userlist_header_row .= '<th title="Unrated">O</th>';

	$userlist_print_row = function($row) {
		echo '<tr>';
		echo '<td>', getUserLink($row['username']), '</td>';
		echo '<td>', json_decode($row['extra_config'], true)['realname'], '</td>';
		echo '<td>', $row['rating'], '</td>';
		echo '<td>', $row['ac_num'], '</td>';
		echo '<td>', $row['remote_addr'], '</td>';
		echo '<td>', $row['latest_login'], '</td>';
		echo isSuperUser($row) ? '<td class="success">S</td>' : '<td></td>';
		echo isProblemManager($row) ? '<td class="success">P</td>' : '<td></td>';
		echo isStatementMaintainer($row) ? '<td class="success">T</td>' : '<td></td>';
		echo isBannedUser($row) ? '<td class="success">B</td>' : '<td></td>';
		echo isGroupAssociated($row, array('group_name' => 'outdated'))['member_state'] === 'U' ? '<td class="success">O</td>' : '<td></td>';
		echo '</tr>';
	};

	$userlist_tail = 'order by username asc';
	if (isset($_GET['sort'])) {
		/*if ($_GET['sort'] === 'real_name') {
			$userlist_tail = 'order by real_name asc, username asc';
		} else*/if ($_GET['sort'] === 'rating') {
			$userlist_tail = 'order by rating desc, username asc';
		} elseif ($_GET['sort'] === 'ac_num') {
			$userlist_tail = 'order by ac_num desc, username asc';
		} elseif ($_GET['sort'] === 'remote_addr') {
			$userlist_tail = 'order by remote_addr asc, username asc';
		} elseif ($_GET['sort'] === 'latest_active') {
			$userlist_tail = 'order by latest_login desc, username asc';
		}
	}

	printHeader();
	echo '<h4>注册新用户：<a href="/register" target="_blank">链接</a></h4>';
	echo '<p><del>这貌似已经变成了真实姓名修改器了？</del></p>';
	$user_form->printHTML();
	echo '<h4>用户列表</h4>';
	echoLongTable($userlist_cols, 'user_info', '1', $userlist_tail, $userlist_header_row, $userlist_print_row, $userlist_config);
}

function showBlogLinks() {
	$blog_link_contests = new UOJForm('blog_link_contests');
	$blog_link_contests->addInput('blog_id', 'text', '博客ID', '',
		function ($x) {
			if (!validateUInt($x)) return 'ID不合法';
			if (!queryBlog($x)) return '博客不存在';
			return '';
		},
		null
	);
	$blog_link_contests->addInput('contest_id', 'text', '比赛ID', '',
		function ($x) {
			if (!validateUInt($x)) return 'ID不合法';
			if (!queryContest($x)) return '比赛不存在';
			return '';
		},
		null
	);
	$blog_link_contests->addInput('title', 'text', '标题', '',
		function ($x) {
			return '';
		},
		null
	);
	$options = array(
		'add' => '添加',
		'del' => '删除'
	);
	$blog_link_contests->addSelect('op_type', $options, '操作类型', '');
	$blog_link_contests->handle = function() {
		$blog_id = $_POST['blog_id'];
		$contest_id = $_POST['contest_id'];
		$str = DB::selectFirst("select * from contests where id = '{$contest_id}'");
		$all_config = json_decode($str['extra_config'], true);
		$config = $all_config['links'];

		$n = count($config);
		
		if ($_POST['op_type'] == 'add') {
			$row = array();
			$row[0] = $_POST['title'];
			$row[1] = $blog_id;
			$config[$n] = $row;
		}
		if ($_POST['op_type'] == 'del') {
			for ($i = 0; $i < $n; $i++)
				if ($config[$i][1] == $blog_id) {
					$config[$i] = $config[$n - 1];
					unset($config[$n - 1]);
					break;
				}
		}

		$all_config['links'] = $config;
		$str = json_encode($all_config);
		$str = DB::escape($str);
		DB::update("update contests set extra_config = '{$str}' where id = '{$contest_id}'");
	};
	$blog_link_contests->runAtServer();

	$blog_link_index = new UOJForm('blog_link_index');
	$blog_link_index->addInput('blog_id2', 'text', '博客ID', '',
		function ($x) {
			if (!validateUInt($x)) return 'ID不合法';
			if (!queryBlog($x)) return '博客不存在';
			return '';
		},
		null
	);
	$blog_link_index->addInput('blog_level', 'text', '置顶级别（删除不用填）', '0',
		function ($x) {
			if (!validateUInt($x)) return '数字不合法';
			if ($x > 3) return '该级别不存在';
			return '';
		},
		null
	);
	$options = array(
		'add' => '添加',
		'del' => '删除'
	);
	$blog_link_index->addSelect('op_type2', $options, '操作类型', '');
	$blog_link_index->handle = function() {
		$blog_id = $_POST['blog_id2'];
		$blog_level = $_POST['blog_level'];
		if ($_POST['op_type2'] == 'add') {
			DB::insert("insert into important_blogs (blog_id, level) values ({$blog_id}, {$blog_level}) on duplicate key update level = {$blog_level}");
		} elseif ($_POST['op_type2'] == 'del') {
			DB::delete("delete from important_blogs where blog_id = {$blog_id}");
		}
	};
	$blog_link_index->runAtServer();

	$blog_deleter = new UOJForm('blog_deleter');
	$blog_deleter->addInput('blog_del_id', 'text', '博客ID', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'ID不合法';
			}
			if (!queryBlog($x)) {
				return '博客不存在';
			}
			return '';
		},
		null
	);
	$blog_deleter->handle = function() {
		deleteBlog($_POST['blog_del_id']);
	};
	$blog_deleter->runAtServer();

	printHeader();
	?>
	<div>
		<h4>添加到比赛链接</h4>
		<?php $blog_link_contests->printHTML(); ?>
	</div>

	<div>
		<h4>添加到公告</h4>
		<?php $blog_link_index->printHTML(); ?>
	</div>

	<div>
		<h4>删除博客</h4>
		<?php $blog_deleter->printHTML(); ?>
	</div>
	<?php
}

function showSubmissions() {
	$contest_submissions_deleter = new UOJForm('contest_submissions');
	$contest_submissions_deleter->addInput('contest_id', 'text', '比赛ID', '',
		function ($x) {
			if (!validateUInt($x)) {
				return 'ID不合法';
			}
			if (!queryContest($x)) {
				return '比赛不存在';
			}
			return '';
		},
		null
	);
	$contest_submissions_deleter->handle = function() {
		$contest = queryContest($_POST['contest_id']);
		genMoreContestInfo($contest);
		
		$contest_problems = DB::selectAll("select problem_id from contests_problems where contest_id = {$contest['id']}");
		foreach ($contest_problems as $problem) {
			$submissions = DB::selectAll("select * from submissions where problem_id = {$problem['problem_id']} and submit_time < '{$contest['start_time_str']}'");
			foreach ($submissions as $submission) {
				deleteSubmission($submission);
			}
		}
	};
	$contest_submissions_deleter->runAtServer();

	printHeader();
	?>
	<div>
		<h4>删除赛前提交记录</h4>
		<?php $contest_submissions_deleter->printHTML(); ?>
	</div>

	<div>
		<h4>测评失败的提交记录</h4>
		<?php
			echoSubmissionsList("result_error = 'Judgement Failed'", 'order by id desc', array(), Auth::user());
		?>
	</div>
	<?php
}

function showCustomTests() {
	$custom_test_deleter = new UOJForm('custom_test_deleter');
	$custom_test_deleter->addInput('last', 'text', '删除末尾记录', '5',
		function ($x, &$vdata) {
			if (!validateUInt($x)) {
				return '不合法';
			}
			$vdata['last'] = $x;
			return '';
		},
		null
	);
	$custom_test_deleter->handle = function(&$vdata) {
		$all = DB::selectAll("select * from custom_test_submissions order by id asc limit {$vdata['last']}");
		foreach ($all as $submission) {
			$content = json_decode($submission['content'], true);
			unlink(UOJContext::storagePath().$content['file_name']);
		}
		DB::delete("delete from custom_test_submissions order by id asc limit {$vdata['last']}");
	};
	$custom_test_deleter->runAtServer();

	printHeader();
	$custom_test_deleter->printHTML();

	$submissions_pag = new Paginator(array(
		'col_names' => array('*'),
		'table_name' => 'custom_test_submissions',
		'cond' => '1',
		'tail' => 'order by id asc',
		'page_len' => 5
	));
	foreach ($submissions_pag->get() as $submission)
	{
		$problem = queryProblemBrief($submission['problem_id']);
		$submission_result = json_decode($submission['result'], true);
		echo '<dl class="dl-horizontal">';
		echo '<dt>id</dt>';
		echo '<dd>', "#{$submission['id']}", '</dd>';
		echo '<dt>problem_id</dt>';
		echo '<dd>', "#{$submission['problem_id']}", '</dd>';
		echo '<dt>submit time</dt>';
		echo '<dd>', $submission['submit_time'], '</dd>';
		echo '<dt>submitter</dt>';
		echo '<dd>', $submission['submitter'], '</dd>';
		echo '<dt>judge_time</dt>';
		echo '<dd>', $submission['judge_time'], '</dd>';
		echo '<dt>judger_name</dt>';
		echo '<dd>', $submission['judger_name'], '</dd>';
		echo '</dl>';
		echoSubmissionContent($submission, getProblemCustomTestRequirement($problem));
		echoCustomTestSubmissionDetails($submission_result['details'], "submission-{$submission['id']}-details");
	}
	echo $submissions_pag->pagination();
}

function showSystemMessages() {
	$sysmsg_form = new UOJForm('system_message');
	$sysmsg_form->addInput('title', 'text', '标题', '',
		function ($title) {
			if (!$title) {
				return '标题不能为空';
			}
			return '';
		},
		null
	);
	$sysmsg_form->addTextArea('content', '内容', '',
		function ($comment) {
			if (!$comment) {
				return '内容不能为空';
			}
			if (strlen($comment) > 1000) {
				return '不能超过 1000 个字节';
			}
			return '';
		},
		null
	);
	$sysmsg_form->handle = function() {
		$msg_title = HTML::escape($_POST['title']);
		$purifier = HTML::purifier();
		$msg_content = $purifier->purify($_POST['content']);
		$user_result = DB::select('select username from user_info');
		$users = array();
		while ($row = DB::fetch($user_result)){
			$users[] = $row['username'];
		}
		sendSystemMsgToUsers($users, $msg_title, $msg_content);
	};
	$sysmsg_form->runAtServer();

	printHeader();
	$sysmsg_form->printHTML();
}

function showFriendLinks() {
	$friend_links = new UOJForm('friend_links');
	$friend_links->addInput('name', 'text', '名称', '',
		function ($x) {
			if (!$x) {
				return '名称不能为空';
			}
			if (strlen($x) > 25) {
				return '不能超过 25 个字节';
			}
			return '';
		},
		null
	);
	$friend_links->addInput('link', 'text', '链接', '',
		function ($x) {
			if (!$x) {
				return '链接不能为空';
			}
			if (strlen($x) > 100) {
				return '不能超过 100 个字节';
			}
			return '';
		},
		null
	);
	$options = array(
		'add' => '添加',
		'del' => '删除'
	);
	$friend_links->addSelect('op_type', $options, '操作类型', '');
	$friend_links->handle = function() {
		$name = $_POST['name'];
		$link = $_POST['link'];
		$op = $_POST['op_type'];
		$esc_name = DB::escape($name);
		$esc_link = DB::escape($link);
		if ($op == 'add') {
			DB::insert("insert into links (name, url) values ('{$esc_name}', '{$esc_link}') on duplicate key update url = '{$esc_link}'");
		} elseif ($op == 'del') {
			DB::delete("delete from links where name = '{$esc_name}'");
		} else
			var_dump($op);
	};
	$friend_links->runAtServer();

	printHeader();
	echo '<div>', '<h4>添加/删除链接</h4>';
	$friend_links->printHTML();
	echo '</div>';
}

function showGroupModification() {
	/*
	 * 因为 selector 和 operator 的组合在大部分场景的较方便。
	 * 所以改成管道操作？以后再说，说不定用起来还不方便呢。
	 */

	$scripts = getGroupScripts('/utility/group_scripts/');

	$groups = new UOJForm('groups');

	$options = array();
	foreach ($scripts['selector'] as $select)
		$options[$select['filename']] = $select['description'];
	$groups->addSelect('selector_type', $options, '选择器脚本', 'default');
	$groups->addTextArea('selector_args', '选择器参数', '',
		function ($x) {
			if (strlen($x) > 1024) {
				return '参数不能超过 1024 个字节';
			}
			return '';
		},
		null
	);

	$options = array();
	foreach ($scripts['operator'] as $operate)
		$options[$operate['filename']] = $operate['description'];
	$groups->addSelect('operator_type', $options, '操作器脚本', 'default');

	$groups->handle = function() {
		$res = selectUsersByScript($_POST['selector_type'], $_POST['selector_args']);
		$res = operateUsersByScript($_POST['operator_type'], $res);
		echo $res;
	};

	$groups->submit_button_config['text'] = '提交操作';

	$groups->succ_href = 'none';
	$groups->ctrl_enter_submit = true;

	$groups->setAjaxSubmit(<<<EOD
	function(response_text) {
		let obj = $("#result_textarea");
		obj.text(response_text);
		obj.trigger('input.autosize');
	}
EOD
);

	$document = new UOJForm('document');
	$options = array();
	foreach ($scripts['selector'] as $select)
		$options[$select['jsonpath']] = '[选择器]' . $select['description'];
	foreach ($scripts['operator'] as $operate)
		$options[$operate['jsonpath']] = '[操作器]' . $operate['description'];
	$document->addSelect('jsonpath', $options, '脚本文档', 'selector_default.json');

	$document->handle = function() {
		$ret = getScriptDocument($_POST['jsonpath']);
		$res = '';
		$lst = false;
		foreach ($ret as $content) {
			if ($lst) $res .= "\n";
			$lst = true;
			$res .= $content;
		}
		echo $res;
	};

	$document->submit_button_config['text'] = '查询文档';

	$document->succ_href = 'none';
	$document->ctrl_enter_submit = true;
	$document->setAjaxSubmit(<<<EOD
	function(response_text) {
		let obj = $("#document_textarea");
		obj.text(response_text);
		obj.trigger('input.autosize');
	}
EOD
);

	$groups->runAtServer();
	$document->runAtServer();

	printHeader();
	$html = <<<EOD
	<div class="form-group">
		<label class="col-sm-2 control-label">返回值</label>
		<div class="col-sm-10">
			<textarea id="result_textarea" class="form-control" readonly="readonly"> </textarea>
		</div>
	</div>

EOD;

	$groups->appendHTML($html);
	$groups->printHTML();

	echo '<hr>';

	$html = <<<EOD
	<div class="form-group">
		<label class="col-sm-2 control-label">文档内容</label>
		<div class="col-sm-10">
			<textarea id="document_textarea" class="form-control" readonly="readonly"> </textarea>
		</div>
	</div>

EOD;

	$document->appendHTML($html);
	$document->printHTML();

}

	requireLib('shjs');
	requireLib('morris');

	$tabs_info[$cur_tab]['method']();
?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
