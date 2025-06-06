<?php

	namespace App\Bootstrap\Helper;

	use App\Bootstrap\Exceptions\AppException;
	use App\Database\DB;
	use App\Utilities\Config;
	use App\Utilities\Session;

	trait Configuration
	{
		private string $configPath = '';
		private array $config = [];

		protected function setupConfig(): void {
			if (!is_array($configPath = require($this->configPath))) {
				throw new AppException('Config file path is not valid');
			}

			$this->config = $configPath;
		}

		protected function setConfiguration(string $config): void {
			$this->configPath = $config;
		}

		protected function setGlobalDefines(): void {
			foreach ($this->config['defines'] ?? [] as $key => $value) {
				if (is_string($key) && !defined($key)) {
					Config::set($key, $value);
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