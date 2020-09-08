<?php
	if (!validateUInt($_GET['id']) || !($contest = queryContest($_GET['id']))) {
		become404Page();
	}

	genMoreContestInfo($contest);

	$published = $contest['extra_config']['publish_standings'];
	if (!isset($published) || $published !== true) {
		become404Page();
	}

	$rgroup = isset($contest['extra_config']['is_group_contest']);

	function echoStandings() {
		global $contest;

		$contest_data = queryContestData($contest);
		calcStandings($contest, $contest_data, $score, $standings);

		uojIncludeView('contest-standings-public', [
			'contest' => $contest,
			'standings' => $standings,
			'score' => $score,
			'contest_data' => $contest_data
		]);
	}

	function echoContestCountdown() {
		global $contest;
		$rest_second = $contest['end_time']->getTimestamp() - UOJTime::$time_now->getTimestamp();
		$time_str = UOJTime::$time_now_str;
		$contest_ends_in = UOJLocale::get('contests::contest ends in');
		echo <<<EOD
		<div class="panel panel-info">
			<div class="panel-heading">
				<h3 class="panel-title">$contest_ends_in</h3>
			</div>
			<div class="panel-body text-center countdown" data-rest="$rest_second"></div>
		</div>
		<script type="text/javascript">
			checkContestNotice({$contest['id']}, '$time_str');
		</script>
EOD;
	}

	function echoContestJudgeProgress() {
		global $contest;
		if ($contest['cur_progress'] < CONTEST_TESTING) {
			$rop = 0;
			$title = UOJLocale::get('contests::contest pending final test');
		} else {
			$total = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']}");
			$n_judged = DB::selectCount("select count(*) from submissions where contest_id = {$contest['id']} and status = 'Judged'");
			$rop = $total == 0 ? 100 : (int)($n_judged / $total * 100);
			$title = UOJLocale::get('contests::contest final testing');
		}
		echo <<<EOD
		<div class="panel panel-info">
			<div class="panel-heading">
				<h3 class="panel-title">$title</h3>
			</div>
			<div class="panel-body">
				<div class="progress bot-buffer-no">
					<div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="$rop" aria-valuemin="0" aria-valuemax="100" style="width: {$rop}%; min-width: 20px;">{$rop}%</div>
				</div>
			</div>
		</div>
EOD;
	}

	function echoContestFinished() {
		$title = UOJLocale::get('contests::contest ended');
		echo <<<EOD
		<div class="panel panel-info">
			<div class="panel-heading">
				<h3 class="panel-title">$title</h3>
			</div>
		</div>
EOD;
	}

	$PageTitle = HTML::stripTags($contest['name']);
	$PageMainTitle = UOJConfig::$data['profile']['oj-name'];
	$PageMainTitleOnSmall = UOJConfig::$data['profile']['oj-name-short'];
?>
<!DOCTYPE html>
<html lang="<?= UOJLocale::locale() ?>">
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1" />
		<?php if (isset($_GET['locale'])): ?>
		<meta name="robots" content="noindex, nofollow" />
		<?php endif ?>
		<title><?= $PageTitle ?></title>

		<script type="text/javascript">uojHome = '<?= HTML::url('/') ?>'</script>

		<!-- Bootstrap core CSS -->
		<?= HTML::css_link('/css/bootstrap.min.css') ?>

		<!-- Bootstrap theme -->
		<?= HTML::css_link('/css/bootstrap-theme.min.css') ?>

		<!-- Custom styles for this template -->
		<?= HTML::css_link('/css/soj-theme.css') ?>

		<!-- jQuery (necessary for Bootstrap\'s JavaScript plugins) -->
		<?= HTML::js_src('/js/jquery.min.js') ?>

		<!-- jQuery cookie -->
		<?= HTML::js_src('/js/jquery.cookie.min.js') ?>

		<!-- jQuery modal -->
		<?= HTML::js_src('/js/jquery.modal.js') ?>

		<?php if (isset($REQUIRE_LIB['tagcanvas'])): ?>
		<!-- jQuery tag canvas -->
		<?= HTML::js_src('/js/jquery.tagcanvas.min.js') ?>
		<?php endif ?>

		<!-- Include all compiled plugins (below), or include individual files as needed -->
		<?= HTML::js_src('/js/bootstrap.min.js') ?>

		<!-- Color converter -->
		<?= HTML::js_src('/js/color-converter.min.js') ?>

		<!-- soj -->
		<?= HTML::js_src('/js/soj.js') ?>

		<!-- readmore -->
		<?= HTML::js_src('/js/readmore/readmore.min.js') ?>

		<!-- LAB -->
		<?= HTML::js_src('/js/LAB.min.js') ?>

		<!-- UOJ ico -->
		<link rel="shortcut icon" href="<?= HTML::url('/pictures/SOJ.ico') ?>" />

		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>
	<body role="document">
		<div class="container theme-showcase" role="main">
			<div>
				<h1 class="hidden-xs"><a href="<?= HTML::url('/') ?>"><img src="<?= HTML::url('/pictures/SOJ_small.png') ?>" alt="SOJ Logo" class="img-rounded" style="width: 39px; height: 39px" /></a> <?= $PageMainTitle ?></h1>
				<h1 class="visible-xs"><?= $PageMainTitleOnSmall ?></h1>
			</div>
			<div class="uoj-content">
                <hr/>
                <div class="text-center">
                    <h1><?= $contest['name'] ?></h1>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <?= HTML::tablist($tabs_info, $cur_tab) ?>
                        <div class="top-buffer-md">
                        <?php echoStandings(); ?>
                        </div>
                    </div>

                    <div class="col-sm-12"> <hr/> </div>

                    <div class="col-sm-3">
                        <?php
                            if ($contest['cur_progress'] <= CONTEST_IN_PROGRESS) {
                                echoContestCountdown();
                            } else if ($contest['cur_progress'] <= CONTEST_TESTING) {
                                echoContestJudgeProgress();
                            } else {
                                echoContestFinished();
                            }
                        ?>
                    </div>
                    <div class="col-sm-3">
                        <?php if ($contest['extra_config']['standings_version'] == 1) { ?>
                        <p>此次比赛为 OI 赛制 (0 分不计罚时)。</p>
                        <p><strong>注意：比赛时只显示测样例的结果。</strong></p>
                        <?php } elseif ($contest['extra_config']['standings_version'] == 2) {?>
                        <p>此次比赛为 OI 赛制 (0 分不计罚时)。</p>
                        <p><strong>注意：比赛时只显示测样例的结果。</strong></p>
                        <?php } elseif ($contest['extra_config']['standings_version'] == 3) { ?>
                        <p>此次比赛为 IOI 赛制。</p>
                        <p><strong>注意：比赛时显示的结果就是最终结果。</strong></p>
                        <?php } elseif ($contest['extra_config']['standings_version'] == 4) { ?>
                        <p>此次比赛为 OI 赛制 (0 分不计罚时)。</p>
                        <p><strong>注意：比赛时只显示测样例的结果。</strong></p>
                        <?php } elseif ($contest['extra_config']['standings_version'] == 5) { ?>
                        <p>此次比赛为 ACM 赛制 (单次错误提交罚时 1200 秒)。</p>
                        <p><strong>注意：比赛时显示的结果就是最终结果。</strong></p>
                        <?php } else { ?>
                        <p>此次比赛为随机赛制，请联系管理员。</p>
                        <?php } ?>
                    </div>
                </div>
<?php echoUOJPageFooter() ?>
