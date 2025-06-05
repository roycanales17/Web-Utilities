<?php

	namespace App\Bootstrap\Helper;

	use App\Database\DB;
	use App\Utilities\Session;

	trait Configuration
	{
		private array $config = [];

		protected function setConfiguration(array $config): void {
			$this->config = $config;
		}

		protected function setGlobalDefines(): void {
			foreach ($this->config['defines'] ?? [] as $key => $value) {
				if (is_string($key) && !defined($key)) {
					define($key, $value);
				}
			}
		}

		protected function setDevelopment(): void {
			$status = config('DEVELOPMENT', 1);
			error_reporting($status);
			ini_set('display_errors', $status);
		}

		protected function setDatabaseConfig(): void {
			$databases = $this->config['database'] ?? [];
			if ($databases) {
				$default = $databases['default'] ?? '';
				$connections = $databases['connections'] ?? [];

				if ($connections[$default] ?? false)
					DB::configure($connections[$default]);
			}
		}

		protected function setSessionConfig(): void {
			if (php_sapi_name() !== 'cli') {
				Session::configure($this->config['session'] ?? []);
				Session::start();
			}
		}

		protected function setPreloadFiles(): void {
			foreach ($this->config['preload_files'] ?? [] as $path) {
				if (file_exists($path)) {
					require_once $path;
				} else {
					$path = trim($path, '/');
					$path = config('APP_ROOT') . "/$path";
					if (file_exists($path))
						require_once $path;
				}
			}
		}
	}