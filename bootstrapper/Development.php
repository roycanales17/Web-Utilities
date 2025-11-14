<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Handler\Bootloader;

	final class Development extends Bootloader
	{
		public function handler(): void {
			$status = get_constant('DEVELOPMENT', true);

			error_reporting($status);
			ini_set('display_errors', $status);
		}
	}