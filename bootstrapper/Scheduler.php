<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Console\Schedule;
	use App\Utilities\Config;
	use App\Utilities\Handler\Bootloader;

	final class Scheduler extends Bootloader
	{
		public function handler(): void {
			if ($this->isCli()) {
				$cron = Config::get('Cron');
				if ($cron) {
					$path = $cron['path'];
					if (is_string($path)) {
						$path = (array)$path;
					}

					foreach ($path as $route) {
						$this->run($route);
					}
				}
			}
		}

		public function run(string $path): void {
			$cron = base_path($path);

			if (file_exists($cron)) {
				console_log("Route Scheduler: %s", [$cron]);
				Schedule::setPath($cron);
			}
		}
	}