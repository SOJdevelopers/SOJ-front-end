
<ul class="nav nav-tabs" role="tablist">
	<li class="active"><a href="#tab-question" role="tab" data-toggle="tab"><?= UOJLocale::get('contests::contest asks') ?></a></li>
	<?php if ($post_notice): ?>
		<li><a href="#tab-notice" role="tab" data-toggle="tab"><?= UOJLocale::get('contests::contest notice') ?></a></li>
	<?php endif ?>
	<?php if ($standings_data): ?>
		<li><a href="#tab-standings" role="tab" data-toggle="tab"><?= UOJLocale::get('contests::contest final standings') ?></a></li>
	<?php endif ?>
</ul>
<div class="tab-content">
	<div class="tab-pane active" id="tab-question">
		<h3><?= UOJLocale::get('contests::contest asks') ?></h3>
		<?php uojIncludeView('contest-question-table', ['pag' => $questions_pag, 'can_reply' => true, 'reply_question' => $reply_question]) ?>
	</div>
	<?php if ($post_notice): ?>
		<div class="tab-pane" id="tab-notice">
			<h3>发布比赛公告</h3>
			<?php $post_notice->printHTML() ?>
		</div>
	<?php endif ?>
	<?php if ($standings_data): ?>
		<div class="tab-pane" id="tab-standings">
			<h3><?= UOJLocale::get('contests::contest final standings') ?></h3>
			<?php uojIncludeView('contest-standings', $standings_data) ?>
		</div>
	<?php endif ?>
</div>
