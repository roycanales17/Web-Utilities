<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Handler\Bootloader;

	final class Defines extends Bootloader
	{
		public function handler(): void {
			$path = base_path("app/Defines.php");

			if (file_exists($path)) {
				include $path;
				console_log("Defines: %s", [$path]);
			}
		}
	}