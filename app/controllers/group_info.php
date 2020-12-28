<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	if (!validateUsername($_GET['username']) || !($group = queryGroup($_GET['username']))) {
		become404Page();
	}
/*
	Joinable tags:
	'A' : Join Directly
	'C' : Need verify by managers
	'N' : Forbidden
	------
	Member states:
	'A' : Manager
	'U' : Common user
	'W' : Pending verifying
*/
	requireLib('flot');

	$in_group = isGroupMember(Auth::user(), $group);
	$is_verify = isGroupAssociated(Auth::user(), $group);
	assert($in_group or !$is_verify or $group['joinable'] === 'C');
	$esc_description = HTML::escape($group['description']);

	if (isset($_POST['toggle'])) {
		if ($group['group_name'] === UOJConfig::$data['profile']['default-group']) {
			die('管理员已经将这个写死在代码里了，你是退不出的 O(∩_∩)O哈哈哈~');
		}
		if (!$in_group and $is_verify) {
			DB::delete("delete from group_members where group_name = '{$group['group_name']}' and username = '{$myUser['username']}'");
			die('ok');
		}
		if (DB::selectFirst("select * from contests_registrants where username = '{$group['group_name']}' and exists (select 1 from contests where contests.id = contests_registrants.contest_id and contests.status != 'finished')")) {
			die('组 ' . $group['group_name'] . ' 已经报名了未结束的比赛，因此无法加入/退出。如果比赛尚未开始，请先退出报名，等成员确定后重新报名。');
		}
		if ($in_group) {
			$n_members = DB::selectCount("select count(username) from group_members where group_name = '{$group['group_name']}' and member_state = 'A'");
			if ($n_members == 1 and isGroupManager(Auth::user(), $group)) {
				die('该组只剩下 1 个管理员，不可退出该组。若要退出，请先转让管理员。');
			} else {
				DB::delete("delete from group_members where group_name = '{$group['group_name']}' and username = '{$myUser['username']}'");
				die('ok');
			}
		} else {
			if ($group['joinable'] === 'A') {
				DB::insert("insert into group_members (group_name, username, member_state) values ('{$group['group_name']}', '{$myUser['username']}', 'U')");
				die('ok');
			} elseif ($group['joinable'] === 'C') {
				DB::insert("insert into group_members (group_name, username, member_state) values ('{$group['group_name']}', '{$myUser['username']}', 'W')");	
				die('ok');
			} else {
				die('此组不可加入！');
			}
		}
	}

	$REQUIRE_LIB['dialog'] = '';
	echoUOJPageHeader($group['group_name'] . ' - ' . UOJLocale::get('group profile'));
?>
	<div class="panel panel-info">
		<div class="panel-heading">
			<h2 class="panel-title"><?= UOJLocale::get('group profile') ?></h2>
		</div>
		<div class="panel-body">
			<div class="row">
				<div class="col-md-4 col-md-push-8">
					<img class="media-object img-thumbnail center-block" alt="<?= $group['group_name'] ?> Avatar" src="<?= HTML::escape($group['avatar']) ?>" style="width: 266px" />
				</div>
				<div class="col-md-8 col-md-pull-4">
					<h2><span class="uoj-honor" data-rating="<?= $group['rating'] ?>"><?= $group['group_name'] ?></span></h2>
					<div class="list-group">
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('rating') ?></h4>
							<p class="list-group-item-text"><strong style="color: red"><?= $group['rating'] ?></strong></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('description') ?></h4>
							<p class="list-group-item-text"><?= $esc_description ?></p>
						</div>
						<div class="list-group-item">
							<h4 class="list-group-item-heading"><?= UOJLocale::get('joinable') ?></h4>
							<p class="list-group-item-text"><?= UOJLocale::get('join ' . $group['joinable']) ?></p>
						</div>
					</div>
				</div>
			</div>
			<?php if ($in_group || isSuperUser(Auth::user())) { ?>
			<a type="button" class="btn btn-info btn-sm" href="/group/<?= $group['group_name'] ?>/edit"><span class="glyphicon glyphicon-pencil"></span> <?= UOJLocale::get('edit group') ?></a>
			<?php } ?>
			<?php if ($group['group_name'] !== UOJConfig::$data['profile']['default-group']) { ?>
				<?php if ($in_group) { ?>
				<a type="button" class="btn btn-success btn-sm" id="toggle-group" href=""><span class="glyphicon glyphicon-log-out"></span> <?= UOJLocale::get('exit this group', $group['group_name']) ?></a>
				<?php } elseif ($is_verify) { ?>
				<a type="button" class="btn btn-success btn-sm" id="toggle-group" href=""><span class="glyphicon glyphicon-time"></span> <?= UOJLocale::get('pending verifying') ?></a>
				<?php } else { ?>
				<a type="button" class="btn btn-success btn-sm" id="toggle-group" href=""><span class="glyphicon glyphicon-log-in"></span> <?= UOJLocale::get('join this group', $group['group_name']) ?></a>
				<?php } ?>
			<?php } ?>

			<div class="top-buffer-lg"></div>
			<div class="list-group">
				<div class="list-group-item">
					<h4 class="list-group-item-heading"><?= UOJLocale::get('rating changes') ?></h4>
					<div class="list-group-item-text" id="rating-plot" style="height:500px;"></div>
				</div>
				<div class="list-group-item">
					<?php
						$mem_managers = DB::selectAll("select group_members.username from group_members left join user_info on group_members.username = user_info.username where group_name = '{$group['group_name']}' and member_state = 'A' order by user_info.rating desc, group_members.username asc");
						$mem_nonmanagers = DB::selectAll("select group_members.username from group_members left join user_info on group_members.username = user_info.username where group_name = '{$group['group_name']}' and member_state = 'U' order by user_info.rating desc, group_members.username asc");
					?>
					<h4 class="list-group-item-heading bot-buffer-md"><?= UOJLocale::get('group members') ?>：<?= UOJLocale::get('n members in total', count($mem_managers) + count($mem_nonmanagers)) ?></h4>
					<h5 class="list-group-item-heading"><?= UOJLocale::get('grp managers') ?>：<?= UOJLocale::get('n members in total', count($mem_managers)) ?></h5>
					<ul class="list-group-item-text list-inline">
					<?php
						foreach ($mem_managers as $manager)
							echo '<li>', getUserLink($manager['username']), '</li>';
					?>
					</ul>
					<hr class="top-buffer-md bot-buffer-md" />
					<h5 class="list-group-item-heading"><?= UOJLocale::get('grp non managers') ?>：<?= UOJLocale::get('n members in total', count($mem_nonmanagers)) ?></h5>
					<ul class="list-group-item-text list-inline">
					<?php
						foreach ($mem_nonmanagers as $nonmanager)
							echo '<li>', getUserLink($nonmanager['username']), '</li>';
					?>
					</ul>
				</div>
			</div>
		</div>
	</div>
<script type="text/javascript">
var rating_data = [[
<?php
	$user_rating_min = $user_rating_max = 1500;
	$result = DB::select("select contest_id, rank, user_rating from contests_registrants left join contests on contests_registrants.contest_id = contests.id where username = '{$group['group_name']}' and has_participated = 1 order by contests.start_time asc");
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
		echo "[{$time_now_stamp}, {$group['rating']}, 0]";
	} else {
		$rating_delta = $group['rating'] - $last_rating;
		echo "[{$last_contest_time}, {$group['rating']}, {$last_contest_id}, '{$last_contest_name}', {$last_rank}, {$rating_delta}]";
	}
	if ($group['rating'] < $user_rating_min) {
		$user_rating_min = $group['rating'];
	}
	if ($group['rating'] > $user_rating_max) {
		$user_rating_max = $group['rating'];
	}
		
	$user_rating_min -= 400;
	$user_rating_max += 400;
?>
]];
var rating_plot = $.plot($("#rating-plot"), [{
	color: "#3850eb",
	label: "<?= $group['group_name'] ?>",
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
			return getGroupLink(username, <?= $group['rating'] ?>, false);
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

$('#toggle-group').click(function(e) {
	e.preventDefault();
	if (!confirm('确定<?= $in_group ? '退出' : ($is_verify ? '放弃加入' : '加入') ?>组 <?= $group['group_name'] ?>？')) return;
	$.post('/group/<?= $group['group_name'] ?>', {
		toggle : ''
	}, function (msg) {
		if (msg === 'ok') {
			location.reload();
		} else {
			BootstrapDialog.show({
				title   : 'Failed',
				message : msg,
				type    : BootstrapDialog.TYPE_WARNING,
				buttons: [{
					label: 'OK',
					action: function(dialog) {
						dialog.close();
					}
				}],
			});
		}
	});
});
</script>

<?php echoUOJPageFooter() ?>
