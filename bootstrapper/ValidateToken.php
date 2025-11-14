<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Handler\Bootloader;

	final class ValidateToken extends Bootloader
	{
		public function handler(): void {
			if (!$this->isCli()) {
				define('CSRF_TOKEN', csrf_token());
				validate_token();
				console_log("CSRF token: ready");
			}
		}
	}