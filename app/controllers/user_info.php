<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	$username = $_GET['username'];

	requireLib('flot');
?>
<?php if (validateUsername($username) && ($user = queryUser($username))): ?>
<?php echoUOJPageHeader($user['username'] . ' - ' . UOJLocale::get('user profile')) ?>
	<?php
		$esc_sex = HTML::escape($user['extra_config']['sex']);
		$col_sex = '';
		if ($esc_sex == 'M') {
			$esc_sex = '&#9794;';
			$col_sex = 'color: blue';
		} else if ($esc_sex == 'F') {
			$esc_sex = '&#9792;';
			$col_sex = "color: red";
		} else {
			$esc_sex = '';
			$col_sex = 'color: black';
		}
	?>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h2 class="panel-title"><?= UOJLocale::get('user profile') ?></h2>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-md-4 col-md-push-8">
					<img class="media-object img-thumbnail center-block" alt="<?= $user['username'] ?> Avatar" src="<?= HTML::avatar_addr($user, 256) ?>" />
				</div>
				<div class="col-md-8 col-md-pull-4">
					<h2><span class="uoj-honor" data-rating="<?= $user['rating'] ?>"><?= $user['username'] ?></span> <span><strong style="<?= $col_sex ?>"><?= $esc_sex ?></strong></span></h2>
					<div class="list-group">
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('rating') ?></h4>
							<p class="list-group-item-text"><strong style="color: red"><?= $user['rating'] ?></strong></p>
						</div>
						<?php
							$isAdmin = isSuperUser(Auth::user());
							$locale = UOJLocale::locale();
							foreach (UOJConfig::$user as $seg => $data) {
								if ($data['hidden'] === true) continue;
								if ($data['publish'] === true || $isAdmin) {
						?>
									<div class="list-group-item">
										<h4 class="list-group-item-heading"><?= $data['locale'][$locale] ?></h4>
										<p class="list-group-item-text"><?= HTML::escape($user['extra_config'][$seg]) ?></p>
									</div>
						<?php
								}
							}
							if ($isAdmin) {
						?>
						<div class="list-group-item">
							<h4 class="list-group-item-heading">register time</h4>
							<p class="list-group-item-text"><?= $user['register_time'] ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading">remote_addr</h4>
							<p class="list-group-item-text"><?= HTML::escape($user['remote_addr']) ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading">http_x_forwarded_for</h4>
							<p class="list-group-item-text"><?= HTML::escape($user['http_x_forwarded_for']) ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading">latest_active</h4>
							<p class="list-group-item-text"><?= $user['latest_login'] ?></p>
						</div>
						<?php } ?>
						<?php if (Auth::id() === $user['username']) { ?>
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('api password') ?></h4>
							<p class="list-group-item-text"><?= $user['api_password'] ?></p>
						</div>
						<?php } ?>
					</div>
				</div>
			</div>
			<?php if (Auth::id() !== $user['username']) { ?>
			<a type="button" class="btn btn-info btn-sm" href="/user/msg?enter=<?= $user['username'] ?>"><span class="glyphicon glyphicon-envelope"></span> <?= UOJLocale::get('send private message') ?></a>
			<?php } else { ?>
			<a type="button" class="btn btn-info btn-sm" href="/user/modify-profile"><span class="glyphicon glyphicon-pencil"></span> <?= UOJLocale::get('modify my profile') ?></a>
			<a type="button" class="btn btn-warning btn-sm" href="javascript:void(0);" onclick="regenerateAPIPassword();"><span class="glyphicon glyphicon-refresh"></span> <?= UOJLocale::get('regenerate api password') ?></a>
			<a type="button" class="btn btn-danger btn-sm" href="javascript:void(0);" onclick="logoutAll();"><span class="glyphicon glyphicon-log-out"></span> <?= UOJLocale::get('log out all') ?></a>
			<?php } ?>

			<a type="button" class="btn btn-success btn-sm" href="<?= HTML::blog_url($user['username'], '/') ?>"><span class="glyphicon glyphicon-arrow-right"></span> <?= UOJLocale::get('visit his blog', $user['username']) ?></a>

			<div class="top-buffer-lg"></div>
			<div class="list-group">
				<div class="list-group-item">
					<h4 class="list-group-item-heading"><?= UOJLocale::get('rating changes') ?></h4>
					<div class="list-group-item-text" id="rating-plot" style="height:500px;"></div>
				</div>
				<div class="list-group-item">
					<?php
						$ac_problems = DB::selectAll("select problem_id from best_ac_submissions where problem_id in (select id from problems where is_hidden = 0) and submitter = '{$user['username']}' order by problem_id");
					?>
					<h4 class="list-group-item-heading"><?= UOJLocale::get('accepted problems'), '：', UOJLocale::get('n problems in total', count($ac_problems)) ?></h4>
					<p class="list-group-item-text">
					<?php
						foreach ($ac_problems as $problem) {
							echo '<a href="/problem/', $problem['problem_id'], '" style="display: inline-block; width: 4em">', $problem['problem_id'], '</a>';
						}
						if (empty($ac_problems)) {
							echo UOJLocale::get('none');
						}
					?>
					</p>
				</div>
			</div>
		</div>
	</div>
<script type="text/javascript">
function regenerateAPIPassword() {
	$.post('/user/modify-profile', {
		'regenerate_apipassword': ''
	}, function(msg) {
		if (msg == 'ok') {
			alert('修改成功');
			window.location.reload();
		} else {
			alert('修改失败：' + msg);
		}
	});
}
function logoutAll() {
	$.post('/user/modify-profile', {
		'logout_all': ''
	}, function(msg) {
		if (msg == 'ok') {
			alert('登出成功');
			window.location.reload();
		} else {
			alert('登出失败：' + msg);
		}
	});
}
var rating_data = [[
<?php
	$user_rating_min = $user_rating_max = 1500;
	$result = DB::select("select contest_id, rank, user_rating from contests_registrants left join contests on contests_registrants.contest_id = contests.id where username = '{$user['username']}' and has_participated = 1 order by contests.start_time asc");
	$is_first_row = true;
	$last_rating = 1500;
	while ($row = DB::fetch($result)) {
		$contest = queryContest($row['contest_id']);
		$rating_delta = $row['user_rating'] - $last_rating;
		if (!$is_first_row) {
			echo "[{$last_contest_time}, {$row['user_rating']}, {$last_contest_id}, '{$last_contest_name}', {$last_rank}, {$rating_delta}], ";
		} else {
			$is_first_row = false;
		}
		$contest_start_time = new DateTime($contest['start_time']);
		$last_contest_time = ($contest_start_time->getTimestamp() + $contest_start_time->getOffset()) * 1000;
		$last_contest_name = $contest['name'];
		$last_contest_id = $contest['id'];
		$last_rank = $row['rank'];
		$last_rating = $row['user_rating'];
		
		if ($row['user_rating'] < $user_rating_min) {
			$user_rating_min = $row['user_rating'];
		}
		if ($row['user_rating'] > $user_rating_max) {
			$user_rating_max = $row['user_rating'];
		}
	}
	if ($is_first_row) {
		$time_now_stamp = (UOJTime::$time_now->getTimestamp() + UOJTime::$time_now->getOffset()) * 1000;
		echo "[{$time_now_stamp}, {$user['rating']}, 0]";
	} else {
		$rating_delta = $user['rating'] - $last_rating;
		echo "[{$last_contest_time}, {$user['rating']}, {$last_contest_id}, '{$last_contest_name}', {$last_rank}, {$rating_delta}]";
	}
	if ($user['rating'] < $user_rating_min) {
		$user_rating_min = $user['rating'];
	}
	if ($user['rating'] > $user_rating_max) {
		$user_rating_max = $user['rating'];
	}
		
	$user_rating_min -= 400;
	$user_rating_max += 400;
?>

]];
var rating_plot = $.plot($("#rating-plot"), [{
	color: "#3850eb",
	label: "<?= $user['username'] ?>",
	data: rating_data[0]
}], {
	series: {
		lines: {
			show: true
		},
		points: {
			show: true
		}
	},
	xaxis: {
		mode: "time"
	},
	yaxis: {
		min: <?= $user_rating_min ?>,
		max: <?= $user_rating_max ?>

	},
	legend: {
		labelFormatter: function(username) {
			return getUserLink(username, <?= $user['rating'] ?>, false);
		}
	},
	grid: {
		clickable: true,
		hoverable: true
	},
	hooks: {
		drawBackground: [
			function(plot, ctx) {
				var plotOffset = plot.getPlotOffset();
				for (var y = 0; y < plot.height(); y++) {
					var rating = <?= $user_rating_max ?> - <?= $user_rating_max - $user_rating_min ?> * y / plot.height();
					ctx.fillStyle = getColOfRating(rating);
					ctx.fillRect(plotOffset.left, plotOffset.top + y, plot.width(), Math.min(5, plot.height() - y));
				}
			}
		]
	}
});

function showTooltip(x, y, contents) {
    $('<div id="rating-tooltip">' + contents + '</div>').css({
        position: 'absolute',
        display: 'none',
        top: y - 20,
        left: x + 10,
        border: '1px solid #fdd',
        padding: '2px',
        'font-size' : '11px',
        'background-color': '#fee',
        opacity: 0.80
    }).appendTo("body").fadeIn(200);
}

var prev = -1;
function onHoverRating(event, pos, item) {
	if (prev != item.dataIndex) {
		$("#rating-tooltip").remove();
		var params = rating_data[item.seriesIndex][item.dataIndex];

		var total = params[1];
		var contestId = params[2];
		if (contestId != 0) {
			var change = params[5] > 0 ? "+" + params[5] : params[5];
			var contestName = params[3];
			var rank = params[4];
			var html = "= " + total + " (" + change + "), <br/>"
			+ "Rank: " + rank + "<br/>"
            + '<a href="' + '/contest/' + contestId + '">' + contestName + '</a>';
		} else {
			var html = "= " + total + "<br/>"
			+ "Unrated";
		}
		showTooltip(item.pageX, item.pageY, html);
		prev = item.dataIndex;
	}
}
$("#rating-plot").bind("plothover", function (event, pos, item) {
    if (item) {
    	onHoverRating(event, pos, item);
    }
});
$("#rating-plot").bind("plotclick", function (event, pos, item) {
    if (item && prev == -1) {
    	onHoverRating(event, pos, item);
    } else {
		$("#rating-tooltip").fadeOut(200);
		prev = -1;
	}
});
</script>
<?php else: ?>
<?php echoUOJPageHeader(UOJLocale::get('this user not exist') . ' - ' . UOJLocale::get('user profile')) ?>
	<div class="panel panel-danger">
		<div class="panel-heading">
			<h2 class="panel-title"><?= UOJLocale::get('user profile') ?></h2>
		</div>
		<div class="panel-body">
		<h4><?= UOJLocale::get('this user not exist') ?></h4>
		</div>
	</div>
<?php endif ?>

<?php echoUOJPageFooter() ?>
