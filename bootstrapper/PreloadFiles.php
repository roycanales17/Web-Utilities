<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Config;
	use App\Utilities\Handler\Bootloader;

	final class PreloadFiles extends Bootloader
	{
		public function handler(): void {
			foreach (Config::get('PreloadFiles') as $path) {
				if (file_exists($path)) {
					require_once $path;
				} else {
					$path = trim($path, '/');
					$path = base_path("/$path");
					if (file_exists($path)) {
						console_log("Preload File: %s", [$path]);
						require_once $path;
					}
				}
			}
		}
	}