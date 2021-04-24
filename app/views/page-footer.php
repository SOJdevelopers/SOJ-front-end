<?php
	if (!isset($ShowPageFooter)) {
		$ShowPageFooter = true;
	}
?>
			</div>
<?php if ($ShowPageFooter): ?>
			<div class="uoj-footer">
				<p>
					<a href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'zh-cn'))) ?>"><img src="/utility/cn.png" alt="中文" /></a> 
					<a href="<?= HTML::url(UOJContext::requestURI(), array('params' => array('locale' => 'en'))) ?>"><img src="/utility/gb.png" alt="English" /></a>
				</p>

				<ul class="list-inline">
					<li><?= UOJConfig::$data['profile']['oj-name'] ?></li>|<li>version: <?= UOJConfig::$data['profile']['oj-version'] ?></li>
				</ul>
				<?php if (UOJConfig::$data['switch']['ICP-license'] === true) { ?>
				<p> <?= UOJConfig::$data['profile']['ICP-license'] ?> </p>
				<?php } ?>

				<p>Server time: <?= UOJTime::$time_now_str ?> | <a href="https://github.com/SOJdevelopers"><?= UOJLocale::get('open source') ?></a></p>
			</div>
<?php endif ?>
		</div>
	</body>
</html>
