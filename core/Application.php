<?php
	declare(strict_types=1);
	namespace App\Bootstrap;

	use App\Bootstrap\Exceptions\AppException;
	use App\Bootstrap\Handler\RuntimeException;
	use App\Bootstrap\Helper\BufferedError;
	use App\Bootstrap\Helper\Configuration;
	use App\Bootstrap\Helper\Performance;
	use App\Utilities\Environment;
	use App\Utilities\Storage;
	use App\Utilities\Cache;
	use App\Headers\Request;
	use App\Routes\Route;
	use Exception;
	use Throwable;
	use Closure;

	final class Application
	{
		use BufferedError;
		use Configuration;

		private static ?self $app = null;
		private string $envPath = '';
		private Performance $performance;
		private ?RuntimeException $runtimeHandler = null;

		public static function boot(): self {
			console_log("Booting application", "info");
			if (!isset(self::$app)) {
				self::$app = new self();
				console_log("Application instance created", "success");
			} else {
				console_log("Application instance already exists", "debug");
			}
			return self::$app;
		}

		public function run(Closure|null $callback = null): void {
			$cli = php_sapi_name() === 'cli';
			console_log("Running application in " . ($cli ? "CLI" : "Web") . " mode", "info");

			try {
				if (!$cli) {
					while (ob_get_level() > 0) {
						ob_end_clean();
					}
					ob_start();
					console_log("Output buffering started", "debug");
				}

				$this->performance = new Performance(true);
				console_log("Performance timer started", "debug");

				if ($this->isBufferedError()) {
					console_log("Buffered error detected", "error");
					throw new AppException($this->getErrorMessage());
				}

				Request::capture();
				console_log("Request captured", "debug");

				Environment::load($this->envPath);
				console_log("Environment loaded from {$this->envPath}", "info");

				$this->setupConfig();
				$this->setGlobalDefines();
				$this->setDevelopment();
				$this->setDatabaseConfig();
				$this->setMailConfig();
				$this->setSessionConfig();
				$this->setPreloadFiles();
				$this->setScheduler();
				$this->setStreamAuthentication(run: true);
				console_log("Core configurations initialized", "success");

				Storage::configure(base_path('/storage'));
				console_log("Storage configured at /storage", "debug");

				if (!$cli) {
					define('CSRF_TOKEN', csrf_token());
					validate_token();
					console_log("CSRF token defined and validated", "debug");
				}

				$conf = $this->getConfig();
				console_log("Application configuration loaded", "debug");

				if ($cache = $conf['cache']['driver'] ?? '') {
					$cache_attr = $conf['cache'][$cache];
					Cache::configure($cache_attr['driver'], $cache_attr['server'], $cache_attr['port']);
					console_log("Cache configured using driver: {$cache_attr['driver']}", "info");
				}

				if ($callback) {
					console_log("Executing run callback", "debug");
					$callback($conf);
				}

				foreach ([false, true] as $validate) {
					foreach ($conf['routes'] ?? [] as $route) {
						if ($cli && $validate) {
							continue;
						}

						$routeFiles = $route['routes'] ?? ['web.php'];
						$routeFilesStr = implode(', ', $routeFiles);

						if ($validate) {
							console_log("Validating route files: {$routeFilesStr}", "info");
						}

						$routeObject = Route::configure(
							root: base_path('/routes'),
							routes: $routeFiles,
							prefix: $route['prefix'] ?? '',
							domain: $route['domain'] ?? env('APP_URL', 'localhost'),
							middleware: $route['middleware'] ?? [],
							validate: $validate
						);

						if (!$cli) {
							$resolved = Request::header('X-STREAM-WIRE')
								? $routeObject->captured(fn($content) => print($content))
								: $routeObject->captured($route['captured'] ?? null);

							if ($resolved) {
								console_log("Route resolved successfully: {$routeFilesStr}", "success");
								break 2;
							}
						}
					}
				}

				if (!$cli && !($resolved ?? false)) {
					if (file_exists(base_path($emptyPagePath = "/views/errors/404.blade.php"))) {
						echo view('errors/404');
						console_log("404 page displayed", "warning");
					} else {
						throw new Exception("Missing 404 page. Please create the file at: {$emptyPagePath}");
					}
					ob_end_flush();
				}

			} catch (Exception|Throwable $e) {
				console_log("Exception caught: {$e->getMessage()}", "error");

				if (!$cli && get_constant('DEVELOPMENT', true)) {
					while (ob_get_level() > 0) {
						ob_end_clean();
					}
				}

				if (!$this->runtimeHandler) {
					$this->runtimeHandler = new RuntimeException();
					console_log("RuntimeException handler created", "debug");
				}

				$this->runtimeHandler->handle($e);
			} finally {
				$this->performance->end();
				console_log("Performance timer ended", "debug");

				if (request()->query('SHOW_PERFORMANCE') === true) {
					print_r($this->performance->generateSummary());
					console_log("Performance summary printed", "info");
				}
			}
		}

		public function withEnvironment(string $envPath): self
		{
			switch (true) {
				case empty(trim($envPath)):
					$this->throwError('Environment file is required');
					break;

				case !file_exists($envPath):
					$this->throwError('Environment file does not exist');
					break;
			}

			$this->envPath = $envPath;
			console_log("Environment path set to {$envPath}", "info");
			return $this;
		}

		public function withStreamAuthentication(array $action): self
		{
			$this->setStreamAuthentication($action);
			console_log("Stream authentication configured", "info");
			return $this;
		}

		public function withConfiguration(string $configPath): self
		{
			switch (true) {
				case (empty(trim($configPath))):
					$this->throwError('Configuration file is required');
					break;

				case !file_exists($configPath):
					$this->throwError('Configuration file does not exist');
					break;
			}

			$this->setConfiguration($configPath);
			console_log("Configuration path set to {$configPath}", "info");
			return $this;
		}

		public function withExceptions(Closure $callback): self
		{
			$callback($this->runtimeHandler = new RuntimeException());
			console_log("Exception handler configured via callback", "debug");
			return $this;
		}

		public function __clone()
		{
			throw new Exception("Cloning is not allowed for this singleton.");
		}

		public function __sleep()
		{
			throw new Exception("Serialization is not allowed for this singleton.");
		}

		public function __wakeup()
		{
			throw new Exception("Unserialization is not allowed for this singleton.");
		}
	}