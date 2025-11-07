<?php
	declare(strict_types=1);
	namespace App\Bootstrap;

	use App\Bootstrap\Exceptions\AppException;
	use App\Bootstrap\Handler\RuntimeException;
	use App\Bootstrap\Helper\BufferedError;
	use App\Bootstrap\Helper\Configuration;
	use App\Bootstrap\Helper\Performance;
	use App\Utilities\Storage;
	use App\Utilities\Config;
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
			if (!isset(self::$app)) {
				self::$app = new self();
			}
			return self::$app;
		}

		public function run(Closure|null $callback = null): void {
			// CLI Checker
			$cli = php_sapi_name() === 'cli';

			try {
				if (!$cli) {
					while (ob_get_level() > 0) {
						ob_end_clean();
					}

					ob_start();
				}

				$this->performance = new Performance(true);
				if ($this->isBufferedError()) {
					throw new AppException($this->getErrorMessage());
				}

				Request::capture();
				Config::load($this->envPath);

				$this->setupConfig();
				$this->setGlobalDefines();
				$this->setDevelopment();
				$this->setDatabaseConfig();
				$this->setMailConfig();
				$this->setSessionConfig();
				$this->setPreloadFiles();
				$this->setScheduler();
				$this->setStreamAuthentication(run: true);

				// Set storage path default
				Storage::configure(base_path('/storage'));

				// Validate/Configure CSRF token
				if (!$cli) {
					define('CSRF_TOKEN', csrf_token());
					validate_token();
				}

				// Configurations
				$conf = $this->getConfig();

				// Configure cache
				if ($cache = $conf['cache']['driver'] ?? '') {
					$cache_attr = $conf['cache'][$cache];
					Cache::configure($cache_attr['driver'], $cache_attr['server'], $cache_attr['port']);
				}

				// This display the content page
				if ($callback) $callback($conf);

				// Configure Routes
				foreach ([false, true] as $validate) {
					foreach ($conf['routes'] ?? [] as $route) {
						if ($cli && $validate) {
							continue;
						}

						$routeObject = Route::configure(
							root: base_path('/routes'),
							routes: $route['routes'] ?? ['web.php'],
							prefix: $route['prefix'] ?? '',
							domain: $route['domain'] ?? config('APP_URL', 'localhost'),
							middleware: $route['middleware'] ?? [],
							validate: $validate
						);

						if (!$cli) {
							$resolved = Request::header('X-STREAM-WIRE')
								? $routeObject->captured(function ($content) {
									echo $content;
								})
								: $routeObject->captured($route['captured']);

							if ($resolved) {
								break 2;
							}
						}
					}
				}

				if (!$cli && !($resolved ?? false)) {
					if (file_exists(base_path($emptyPagePath = "/views/errors/404.blade.php"))) {
						echo view('errors/404');
					} else {
						throw new Exception("Missing 404 page. Please create the file at: {$emptyPagePath}");
					}
					ob_end_flush();
				}

			} catch (Exception|Throwable $e) {
				if (!$cli && Config::get('DEVELOPMENT', true)) {
					while (ob_get_level() > 0) {
						ob_end_clean();
					}
				}

				if (!$this->runtimeHandler) {
					$this->runtimeHandler = new RuntimeException();
				}

				$this->runtimeHandler->handle($e);
			} finally {
				$this->performance->end();
				if (request()->query('SHOW_PERFORMANCE') === true) {
					print_r($this->performance->generateSummary());
				}
			}
		}

		/**
		 * Set the path to the environment configuration.
		 */
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
			return $this;
		}

		public function withStreamAuthentication(array $action): self
		{
			$this->setStreamAuthentication($action);
			return $this;
		}

		/**
		 * Set the path to the application configuration.
		 */
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
			return $this;
		}

		/**
		 * Handle exception setup via callback.
		 */
		public function withExceptions(Closure $callback): self
		{
			$callback($this->runtimeHandler = new RuntimeException());
			return $this;
		}

		/**
		 * Prevent cloning the singleton instance.
		 * @throws Exception
		 */
		public function __clone()
		{
			throw new Exception("Cloning is not allowed for this singleton.");
		}

		/**
		 * Prevent serialization of the singleton instance.
		 * @throws Exception
		 */
		public function __sleep()
		{
			throw new Exception("Serialization is not allowed for this singleton.");
		}

		/**
		 * Prevent unserialization of the singleton instance.
		 * @throws Exception
		 */
		public function __wakeup()
		{
			throw new Exception("Unserialization is not allowed for this singleton.");
		}
	}
