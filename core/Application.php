<?php
	namespace App\Bootstrap;

	use App\Bootstrap\Exceptions\AppException;
	use App\Bootstrap\Handler\RuntimeException;
	use App\Bootstrap\Handler\StreamWireConfig;
	use App\Bootstrap\Helper\BufferedError;
	use App\Bootstrap\Helper\Configuration;
	use App\Bootstrap\Helper\Performance;
	use App\Utilities\Config;
	use App\Headers\Request;
	use Closure;
	use Exception;
	use Throwable;

	final class Application
	{
		use BufferedError;
		use Configuration;

		private static ?self $app = null;
		private Performance $performance;
		private ?RuntimeException $runtimeHandler = null;
		private ?StreamWireConfig $streamWireConfig = null;

		public static function boot(): self {
			if (!isset(self::$app)) {
				self::$app = new self();
			}
			return self::$app;
		}

		/**
		 * Allows us to use stream wire feature.
		 *
		 * @param Closure $callback
		 * @return $this
		 */
		public function withStreamWire(Closure $callback): self {
			$this->streamWireConfig = new StreamWireConfig();
			$callback($this->streamWireConfig);
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
			$this->runtimeHandler = new RuntimeException();
			$callback($this->runtimeHandler);
			return $this;
		}

		public function __destruct()
		{
			try {
				$this->performance = new Performance(true);
				if ($this->isBufferedError()) {
					throw new AppException($this->getErrorMessage());
				}

				Request::capture();
				$this->setupConfig();
				$this->setGlobalDefines();
				$this->setDevelopment();
				$this->setDatabaseConfig();
				$this->setSessionConfig();
				$this->setPreloadFiles();
				$this->setStreamWire($this->streamWireConfig);
				$this->setupCacheDriver();
				$this->setupMailingService();

				define('CSRF_TOKEN', csrf_token());
				validate_token();

				echo($this->setupRoutingAndCapture());

			} catch (Exception|Throwable $e) {
				if ($this->runtimeHandler) {
					$this->runtimeHandler->handle($e);
				} else {
					throw $e;
				}
			} finally {
				$this->performance->end();
				if (request()->query('SHOW_PERFORMANCE') === true) {
					print_r($this->performance->generateSummary());
				}
			}
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
