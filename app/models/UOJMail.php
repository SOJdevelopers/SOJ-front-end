<?php

require $_SERVER['DOCUMENT_ROOT'] . '/app/vendor/phpmailer/PHPMailerAutoload.php';

class UOJMail {
	public static function noreply() {
		$mailer = new PHPMailer();  
		$mailer->isSMTP();
		$mailer->Host = UOJConfig::$data['mail']['noreply']['host'];
		$mailer->Port = UOJConfig::$data['mail']['noreply']['port'];
		$mailer->SMTPAuth = true;
		$mailer->SMTPDebug = true;
		$mailer->SMTPSecure = UOJConfig::$data['mail']['noreply']['secure'];
		$mailer->Username = UOJConfig::$data['mail']['noreply']['username'];
		$mailer->Password = UOJConfig::$data['mail']['noreply']['password'];
		$mailer->setFrom(UOJConfig::$data['mail']['noreply']['username'], 'Stupid Online Judge');
		$mailer->CharSet = "utf-8";
		$mailer->Encoding = "base64";
		return $mailer;
	}
}
