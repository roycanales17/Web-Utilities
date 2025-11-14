<?php
	declare(strict_types=1);
	namespace App\Bootstrap;

	use App\Bootstrap\Bootstrapper\StreamWireAuthentication;
	use App\Bootstrap\Bootstrapper\ValidateToken;
	use App\Bootstrap\Bootstrapper\PreloadFiles;
	use App\Bootstrap\Bootstrapper\Development;
	use App\Bootstrap\Bootstrapper\Environment;
	use App\Bootstrap\Handler\RuntimeException;
	use App\Bootstrap\Exceptions\AppException;
	use App\Bootstrap\Bootstrapper\Scheduler;
	use App\Bootstrap\Bootstrapper\Callback;
	use App\Bootstrap\Bootstrapper\Constant;
	use App\Bootstrap\Bootstrapper\Database;
	use App\Bootstrap\Bootstrapper\Requests;
	use App\Bootstrap\Bootstrapper\OBStart;
	use App\Bootstrap\Bootstrapper\Defines;
	use App\Bootstrap\Bootstrapper\Session;
	use App\Bootstrap\Bootstrapper\Storage;
	use App\Bootstrap\Helper\BufferedError;
	use App\Bootstrap\Bootstrapper\Routes;
	use App\Bootstrap\Helper\Performance;
	use App\Bootstrap\Bootstrapper\Cache;
	use App\Bootstrap\Bootstrapper\Mail;
	use Exception;
	use Throwable;
	use Closure;

	final class Application
	{
		use BufferedError;

		private static ?self $app = null;
		private string $envPath = '';
		private Performance $performance;
		private ?RuntimeException $runtimeHandler = null;

		public static function boot(string $envPath): self {
			if (!isset(self::$app)) {
				self::$app = new self($envPath);
			}
			return self::$app;
		}

		private function __construct(string $envPath) {
			switch (true) {
				case empty(trim($envPath)):
					$this->throwError('Environment file is required');
					break;

				case !file_exists($envPath):
					$this->throwError('Environment file does not exist');
					break;
			}

			$this->envPath = $envPath;
		}

		public function run(Closure|null $callback = null): void {
			try {
				$this->performance = new Performance(true);
				if ($this->isBufferedError()) {
					throw new AppException($this->getErrorMessage());
				}

				$this->load(Constant::class);
				$this->load(OBStart::class);
				$this->load(Requests::class);
				$this->load(Environment::class, ['path' => $this->envPath]);
				$this->load(Defines::class);
				$this->load(Development::class);
				$this->load(Database::class);
				$this->load(Mail::class);
				$this->load(Session::class);
				$this->load(PreloadFiles::class);
				$this->load(Scheduler::class);
				$this->load(StreamWireAuthentication::class);
				$this->load(Storage::class);
				$this->load(ValidateToken::class);
				$this->load(Cache::class);
				$this->load(Callback::class, ['callback' => $callback]);
				$this->load(Routes::class);

			} catch (Exception|Throwable $e) {
				if (!get_constant('CLI_MODE', false) && get_constant('DEVELOPMENT', true)) {
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

		private function load(string $class, array $params = []): mixed {
			if (!class_exists($class)) {
				throw new AppException('Class "' . $class . '" not found');
			}

			$instance = new $class($params);
			return $instance->handler();
		}

		public function withExceptions(Closure $callback): self
		{
			$callback($this->runtimeHandler = new RuntimeException());
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
