<?php

	namespace App\Utilities;

	use InvalidArgumentException;

	/**
	 * Class Logger
	 *
	 * A simple file-based logging utility supporting different log levels.
	 */
	final class Logger
	{
		/** @var string The directory where logs will be saved. */
		protected string $logDirectory;

		/** @var string The name of the log file. */
		protected string $logFile;

		/**
		 * Logger constructor.
		 *
		 * @param string $logDirectory Directory to store logs (default: 'logs').
		 * @param string $logFile Log file name (default: 'app.log').
		 */
		public function __construct(string $logDirectory = 'logs', string $logFile = 'app.log')
		{
			$this->logDirectory = trim($logDirectory, '/');
			$this->logFile = $logFile;

			$this->ensureLogDirectoryExists();
		}

		/**
		 * Ensures that the log directory exists.
		 *
		 * @return void
		 */
		protected function ensureLogDirectoryExists(): void
		{
			if (!is_dir($this->logDirectory)) {
				if (!mkdir($this->logDirectory, 0777, true) && !is_dir($this->logDirectory)) {
					throw new InvalidArgumentException("Failed to create log directory: {$this->logDirectory}");
				}
			}
		}

		/**
		 * Logs a debug-level message.
		 *
		 * @param string $message
		 * @param array $context
		 * @return void
		 */
		public function debug(string $message, array $context = []): void
		{
			$this->log('🐞', 'debug', $message, $context);
		}

		/**
		 * Logs an info-level message.
		 *
		 * @param string $message
		 * @param array $context
		 * @return void
		 */
		public function info(string $message, array $context = []): void
		{
			$this->log('ℹ️', 'info', $message, $context);
		}

		/**
		 * Logs a warning-level message.
		 *
		 * @param string $message
		 * @param array $context
		 * @return void
		 */
		public function warning(string $message, array $context = []): void
		{
			$this->log('⚠️', 'warning', $message, $context);
		}

		/**
		 * Logs an error-level message.
		 *
		 * @param string $message
		 * @param array $context
		 * @return void
		 */
		public function error(string $message, array $context = []): void
		{
			$this->log('🚨', 'error', $message, $context);
		}

		/**
		 * Gets the full path to the log file.
		 *
		 * @return string
		 */
		protected function getLogFilePath(): string
		{
			return "{$this->logDirectory}/{$this->logFile}";
		}

		/**
		 * Logs a message at the given level with optional context.
		 *
		 * @param string $title
		 * @param string $level
		 * @param string $message
		 * @param array $context
		 * @return void
		 */
		protected function log(string $title, string $level, string $message, array $context = []): void
		{
			$logEntry = $this->formatLogEntry($level, $title, $message, $context);
			file_put_contents($this->getLogFilePath(), $logEntry, FILE_APPEND | LOCK_EX);
		}

		/**
		 * Formats the log entry as a string.
		 *
		 * @param string $level
		 * @param string $icon
		 * @param string $message
		 * @param array $context
		 * @return string
		 */
		protected function formatLogEntry(string $level, string $icon, string $message, array $context): string
		{
			$timestamp = Carbon::format();
			$level = strtolower($level);
			$levelUpper = strtoupper($level);

			$log = "{$icon} [{$levelUpper}] [{$timestamp}]\n\n";

			switch ($level) {
				case 'error':
					$this->error_template($message, $log, $context);
					break;

				case 'warning':
					$this->warningTemplate($message, $log, $context);
					break;

				case 'debug':
					$log .= "Message  : {$message}\n";
					$log .= "Memory   : " . round(memory_get_usage(true) / 1024 / 1024, 2) . " MB\n";
					if (empty($context['trace'])) {
						$context['trace'] = (new \Exception())->getTraceAsString();
					}
					$this->appendContext($log, $context);
					break;

				case 'info':
					$log .= "Message  : {$message}\n";
					$this->appendContext($log, $context);
					break;
			}

			$log .= str_repeat('-', 47) . "\n\n\n\n";
			return $log;
		}

		private function warningTemplate(string $message, string &$log, array $context): void
		{
			$log .= "Message  : {$message}\n";
			if (isset($context['file'])) {
				$log .= "File     : {$context['file']}\n";
			}
			if (isset($context['line'])) {
				$log .= "Line     : {$context['line']}\n";
			}

			$this->appendContext($log, $context);
		}

		private function error_template(string $message, string &$log, array $context): void
		{
			if (isset($context['exception'])) {
				$log .= "Type     : {$context['exception']}\n";
			}

			$log .= "Message  : {$message}\n";

			if (isset($context['file'])) {
				$log .= "File     : {$context['file']}\n";
			}
			if (isset($context['line'])) {
				$log .= "Line     : {$context['line']}\n";
			}

			$this->appendContext($log, $context);

			if (isset($context['trace'])) {
				$log .= "\n🔍 Trace:\n" . trim($context['trace']) . "\n";
			}
		}

		private function appendContext(string &$log, array $context): void
		{
			if (empty($context['context']) || !is_array($context['context'])) {
				return;
			}

			$log .= "\n🌐 Context:\n";
			$ctx = $context['context'];
			$maxKeyLength = max(array_map('strlen', array_keys($ctx)));

			foreach ($ctx as $key => $value) {
				if (is_array($value) || is_object($value)) {
					$value = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
				} elseif (is_bool($value)) {
					$value = $value ? 'true' : 'false';
				} elseif ($value === null) {
					$value = 'null';
				}

				$log .= str_pad($key, $maxKeyLength) . " : {$value}\n";
			}
		}
	}
