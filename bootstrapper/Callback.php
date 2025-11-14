<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Handler\Bootloader;

	final class Callback extends Bootloader
	{
		public function handler(): void {
			$callback = $this->argument('callback');

			if ($callback) {
				console_log("Running callback...");
				$callback();
			}
		}
	}