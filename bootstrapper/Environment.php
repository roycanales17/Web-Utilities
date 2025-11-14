<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Bootstrap\Exceptions\AppException;
	use App\Utilities\Environment as BaseEnvironment;
	use App\Utilities\Handler\Bootloader;

	final class Environment extends Bootloader
	{
		public function handler(): void {
			$path = $this->argument('path');
			if (!file_exists($path)) {
				throw new AppException('Environment file not found');
			}

			BaseEnvironment::load($path);
			console_log("Environment: %s", [$path]);
		}
	}