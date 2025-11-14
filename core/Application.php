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
			$cli = php_sapi_name() === 'cli';

			try {
				if (!$cli) {
					while (ob_get_level() > 0) {
						ob_end_clean();
					}

					ob_start();
				}

				$this->performance = new Performance(true);
				console_log('Performance timer started', 'debug');

				if ($this->isBufferedError()) {
					console_log('Buffered error detected: ' . $this->getErrorMessage(), 'error');
					throw new AppException($this->getErrorMessage());
				}

				Request::capture();
				Environment::load($this->envPath);
				console_log('Environment loaded from ' . $this->envPath, 'success');

				$this->setupConfig();
				console_log('Configuration loaded', 'success');

				$this->setGlobalDefines();
				$this->setDevelopment();
				$this->setDatabaseConfig();
				$this->setMailConfig();
				$this->setSessionConfig();
				$this->setPreloadFiles();
				$this->setScheduler();
				$this->setStreamAuthentication(run: true);

				Storage::configure(base_path('/storage'));
				console_log('Storage configured at /storage');

				if (!$cli) {
					define('CSRF_TOKEN', csrf_token());
					validate_token();
					console_log('CSRF token validated');
				}

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
							domain: $route['domain'] ?? env('APP_URL', 'localhost'),
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

			} catch (Exception|Throwable $e) {
				console_log('Exception caught: ' . $e->getMessage(), 'error');
				if (!$this->runtimeHandler) {
					$this->runtimeHandler = new RuntimeException();
				}

				$this->runtimeHandler->handle($e);
			} finally {
				$this->performance->end();
				console_log('Performance timer ended', 'debug');

				if (request()->query('SHOW_PERFORMANCE') === true) {
					console_log($this->performance->generateSummary());
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
