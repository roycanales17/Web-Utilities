<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Config;
	use App\Utilities\Mail as BaseMail;
	use App\Utilities\Handler\Bootloader;

	final class Mail extends Bootloader
	{
		public function handler(): void {
			$mail = Config::get('Mailing');
			if (!empty($mail['enabled'])) {
				$credentials = [];

				if (!empty($mail['username']) && !empty($mail['password'])) {
					$credentials = [
						'username' => $mail['username'],
						'password' => $mail['password'],
					];
				}

				BaseMail::configure($mail['host'], $mail['port'], $mail['encryption'], $mail['smtp'], $credentials);
			}
		}
	}