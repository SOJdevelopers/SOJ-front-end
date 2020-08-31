<?php
	if (!Auth::check()) {
		redirectToLogin();
	}

	$blogs = DB::selectAll('select blogs.id, title, poster, post_time from important_blogs, blogs where is_hidden = 0 and important_blogs.blog_id = blogs.id order by level desc, blogs.post_time desc limit 5');

	$links = DB::selectAll('select name, url from links order by name asc limit 5');
?>
<?php echoUOJPageHeader('SOJ') ?>
<div class="panel panel-default">
	<div class="panel-body">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-7">
				<table class="table">
					<thead>
						<tr>
							<th style="width: 50%"><?= UOJLocale::get('announcements') ?></th>
							<th style="width: 25%"></th>
							<th style="width: 25%"></th>
						</tr>
					</thead>
				  	<tbody>
						<?php
							$now_cnt = 0;
							foreach ($blogs as $blog) {
								++$now_cnt;
								$new_tag = '';
								if ((time() - strtotime($blog['post_time'])) / 3600 / 24 <= 7) {
									$new_tag = '<sup style="color: red">&nbsp;new</sup>';
								}
								echo '<tr>';
								echo '<td><a href="/blog/', $blog['id'], '">', $blog['title'], '</a>', $new_tag, '</td>';
								echo '<td>by ', getUserLink($blog['poster']), '</td>';
								echo '<td><small>', $blog['post_time'], '</small></td>';
								echo '</tr>';
							}
							for ($i = $now_cnt + 1; $i <= 5; $i++) {
								echo '<tr><td colspan="233">&nbsp;</td></tr>';
							}
						?>
						<tr><td class="text-right" colspan="233"><a href="/announcements"><?= UOJLocale::get('all the announcements') ?></a></td></tr>
					</tbody>
				</table>
			</div>
			<div class="col-xs-6 col-sm-8 col-md-2">
				<table class="table">
					<thead>
						<tr>
							<th><?= UOJLocale::get('links') ?></th>
						</tr>
					</thead>
					<tbody>
						<?php
							$now_cnt = 0;
							foreach ($links as $link) {
								++$now_cnt;
								echo '<tr>';
								echo '<td><a href="', $link['url'], '">', $link['name'], '</a></td>';
								echo '</tr>';
							}
							for ($i = $now_cnt + 1; $i <= 6; $i++) {
								echo '<tr><td colspan="233">&nbsp;</td></tr>';
							}
						?>
					</tbody>
				</table>
			</div>
			<div class="col-xs-6 col-sm-4 col-md-3">
				<img class="media-object img-thumbnail" src="/pictures/SOJ.png" style="width: 100%" alt="SOJ logo" />
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-sm-12">
		<center><h3><a href="/ranklist" class="uoj-index-ranklist"><?= UOJLocale::get('top rated') ?></a></h3></center>
		<?php echoRanklist(array('echo_full' => '', 'top10' => '')) ?>
	</div>
</div>
<div class="row">
	<div class="col-sm-12">
		<center><h3><a href="/acranklist" class="uoj-index-ranklist"><?= UOJLocale::get('top cutted') ?></a></3></center>
		<?php echoACRanklist(array('echo_full' => '', 'top10' => '')) ?>
	</div>
</div>
<div class="row">
	<div class="col-sm-12">
		<center><h3><a href="/groups" class="uoj-index-ranklist"><?= UOJLocale::get('top rated groups') ?></a></h3></center>
		<?php echoGrouplist(array('echo_full' => '', 'top10' => '')) ?>
	</div>
</div>
<?php echoUOJPageFooter() ?>
