<?php

	namespace App\Bootstrap\Handler;

	use Closure;
	use ReflectionException;
	use ReflectionFunction;
	use Throwable;

	final class RuntimeException
	{
		protected array $reportCallbacks = [];
		protected array $dontReport = [];
		protected bool $suppressDuplicates = false;
		protected static array $reportedHashes = [];

		/**
		 * Register a callback to handle a specific type of exception.
		 */
		public function report(Closure $closure): void {
			$ref = new ReflectionFunction($closure);
			$params = $ref->getParameters();

			if (isset($params[0]) && $type = $params[0]->getType()) {
				$this->reportCallbacks[$type->getName()] = $closure;
			}
		}

		/**
		 * Set a view file to be rendered with exception data.
		 */
		public function view(string $path, array $extract = []): void {
			if (file_exists($path)) {
				view($path, array_merge($extract, ['exception' => $this]));
			}
		}

		/**
		 * Prevent duplicate exceptions from being reported multiple times.
		 */
		public function dontReportDuplicates(): void {
			$this->suppressDuplicates = true;
		}

		/**
		 * Exclude specific exception classes from being reported.
		 */
		public function dontReport(array $exclude = []): void {
			$this->dontReport = array_merge($this->dontReport, $exclude);
		}

		/**
		 * Run the report logic if applicable.
		 */
		public function handle(Throwable $e): void {
			$class = get_class($e);

			// Skip if in exclusion list
			foreach ($this->dontReport as $excluded) {
				if ($e instanceof $excluded) {
					return;
				}
			}

			// Skip if duplicate and suppression is enabled
			if ($this->suppressDuplicates) {
				$hash = md5($class . $e->getMessage() . $e->getFile() . $e->getLine());
				if (in_array($hash, self::$reportedHashes, true)) {
					return;
				}
				self::$reportedHashes[] = $hash;
			}

			// Dispatch to registered handler if available
			foreach ($this->reportCallbacks as $type => $callback) {
				if ($e instanceof $type) {
					$callback($e);
					return;
				}
			}

			// If the exception has a custom report method, use it
			if (method_exists($e, 'report')) {
				echo($e->report());
				exit();
			}

			// Default fallback
			error_log("Unhandled exception: " . $e->getMessage());
			error_log("Trace:". $e->getTraceAsString());
		}
	}
