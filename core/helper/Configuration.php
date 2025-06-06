<?php

	namespace App\Bootstrap\Helper;

	use App\Bootstrap\Exceptions\AppException;
	use App\Bootstrap\Handler\StreamWireConfig;
	use App\Database\DB;
	use App\Routes\Route;
	use App\Utilities\Cache;
	use App\Utilities\Mail;
	use App\Utilities\Session;
	use App\Utilities\Stream;

	trait Configuration
	{
		private string $configPath = '';
		private array $config = [];

		protected function setupConfig(): void {
			if (empty(trim($this->configPath))) {
				throw new AppException('Config file path is required to run the application.');
			}

			if (!is_array($configPath = require($this->configPath))) {
				throw new AppException('Config file path is not valid');
			}

			$this->config = $configPath ?? [];
		}

		protected function setConfiguration(string $config): void {
			$this->configPath = $config;
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

		protected function setStreamWire(StreamWireConfig|null $object): void {
			if (!is_null($object)) {
				Stream::configure($object->getPath(), $object->getAuthentication(), $object->getOnFailed());
			}
		}

		protected function setupCacheDriver(): void {
			if ($cache = $this->config['cache']['driver'] ?? '') {
				$cache_attr = $this->config['cache'][$cache];
				Cache::configure($cache_attr['driver'], $cache_attr['server'], $cache_attr['port']);
			}
		}

		protected function setupMailingService(): void {
			$mail = $this->config['mailing'] ?? [];
			if (!empty($mail['enabled'])) {
				$credentials = [];

				if (!empty($mail['username']) && !empty($mail['password'])) {
					$credentials = [
						'username' => $mail['username'],
						'password' => $mail['password'],
					];
				}

				Mail::configure($mail['host'], $mail['port'], $credentials);
			}
		}

		protected function setupRoutingAndCapture(): string {
			ob_start();
			foreach ($conf['routes'] ?? [] as $route) {
				Route::configure(
					root: $route['root'] ?? "../routes",
					routes: $route['routes'] ?? ['web.php'],
					prefix: $route['prefix'] ?? '',
					domain: $route['domain'] ?? config('APP_DOMAIN', 'localhost')
				)->captured($route['captured']);
			}
			return ob_get_clean();
		}
	}