<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Config;
	use App\Databases\Database as Databases;
	use App\Utilities\Handler\Bootloader;

	final class Database extends Bootloader
	{
		public function handler(): void {
			$databases = Config::get('Database');
			if ($databases) {
				$connections = $databases['connections'] ?? [];

				foreach ($connections as $server => $config) {
					Databases::configure($server, $config);
				}
			}
		}
	}