<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Config;
	use App\Utilities\Handler\Bootloader;
	use App\Utilities\Session as BaseSession;
	use App\Bootstrap\Exceptions\AppException;

	final class Session extends Bootloader
	{
		public function handler(): void {
			$session = Config::get('Session');
			if (!$session) {
				throw new AppException("Session configuration not found");
			}

			if (!$this->isCli()) {
				BaseSession::configure($session);
				BaseSession::start();
				console_log("Session: %s", [session_id()]);
			} else {
				console_log("Session: disabled");
			}
		}
	}