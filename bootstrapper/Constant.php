<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Handler\Bootloader;

	final class Constant extends Bootloader
	{
		public function handler(): void {
			define('CLI_MODE', php_sapi_name() === 'cli');
		}
	}