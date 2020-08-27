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

				<p>Server time: <?= UOJTime::$time_now_str ?> | <a href="http://github.com/vfleaking/uoj"><?= UOJLocale::get('open source') ?></a></p>
			</div>
<?php endif ?>
		</div>
	</body>
</html>
