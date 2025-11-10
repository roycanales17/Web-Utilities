<?php

	namespace App\Bootstrap\Helper;

	use App\Bootstrap\Exceptions\AppException;
	use App\Utilities\Config;
	use App\Utilities\Mail;
	use App\Utilities\Session;
	use App\Utilities\Stream;
	use App\Databases\Database;
	use App\Console\Schedule;

	trait Configuration
	{
		private string $configPath = '';
		private array $config = [];
		private array $streamAuthentication = [];

		protected function getConfig(): array {
			return $this->config;
		}

		protected function setupConfig(): void {
			if ($this->configPath) {
				if (!is_array($configPath = require($this->configPath))) {
					throw new AppException('Config file path is not valid');
				}

				$this->config = $configPath;
			}
		}

		protected function setConfiguration(string $config): void {
			$this->configPath = $config;
		}

		protected function setStreamAuthentication(array $actions = [], bool $run = false): void {
			if ($run) {
				if ($this->streamAuthentication) {
					Stream::authentication($this->streamAuthentication);
				}
				return;
			}

			$this->streamAuthentication = $actions;
		}

		protected function setGlobalDefines(): void {
			if (($this->config['defines'] ?? false) && is_callable($this->config['defines'])) {
				$this->config['defines']();
			}
		}

		protected function setDevelopment(): void {
			$status = get_constant('DEVELOPMENT', true);
			error_reporting($status);
			ini_set('display_errors', $status);
		}

		protected function setDatabaseConfig(): void {
			$databases = $this->config['database'] ?? [];
			if ($databases) {
				$connections = $databases['connections'] ?? [];

				foreach ($connections as $server => $config) {
					Database::configure($server, $config);
				}
			}
		}

		protected function setMailConfig(): void {
			$mail = $this->config['mailing'] ?? [];
			if (!empty($mail['enabled'])) {
				$credentials = [];

				if (!empty($mail['username']) && !empty($mail['password'])) {
					$credentials = [
						'username' => $mail['username'],
						'password' => $mail['password'],
					];
				}

				Mail::configure($mail['host'], $mail['port'], $mail['encryption'], $mail['smtp'], $credentials);
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
					$path = base_path("/$path");
					if (file_exists($path))
						require_once $path;
				}
			}
		}

		protected function setScheduler(): void {
			if (php_sapi_name() == 'cli') {
				$cron = base_path($this->config['cron'] ?? '');

				if (file_exists($cron)) {
					Schedule::setPath($cron);
				}
			}
		}
	}
