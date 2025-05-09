<?php

	namespace App\Utilities;

	class Logger
	{
		protected string $logDirectory;
		protected string $logFile;
		protected string $logLevel;
		protected array $logLevels = ['debug', 'info', 'warning', 'error'];

		public function __construct(string $logDirectory = 'logs', string $logFile = 'app.log', string $logLevel = 'debug')
		{
			$this->logDirectory = trim($logDirectory, '/'). '/';
			$this->logFile = $logFile;
			$this->logLevel = in_array(strtolower($logLevel), $this->logLevels) ? strtolower($logLevel) : 'debug';

			$this->ensureLogDirectoryExists();
		}

		protected function ensureLogDirectoryExists(): void
		{
			if (!is_dir($this->logDirectory)) {
				mkdir($this->logDirectory, 0777, true);
			}
		}

		public function debug(string $message, array $context = []): void
		{
			$this->log('debug', $message, $context);
		}

		public function info(string $message, array $context = []): void
		{
			$this->log('info', $message, $context);
		}

		public function warning(string $message, array $context = []): void
		{
			$this->log('warning', $message, $context);
		}

		public function error(string $message, array $context = []): void
		{
			$this->log('error', $message, $context);
		}

		protected function formatContext(array $context): string
		{
			if (empty($context)) {
				return '';
			}

			return "\n\n".print_r($context, true)."|\n|\n|\n|";
		}

		protected function getLogFilePath(): string
		{
			return "{$this->logDirectory}/{$this->logFile}";
		}

		public function log(string $level, string $message, array $context = []): void
		{
			if (!in_array($level, $this->logLevels)) {
				throw new \InvalidArgumentException("Invalid log level: {$level}");
			}

			if ($this->shouldLog($level)) {
				$logEntry = $this->formatLogEntry($level, $message, $context);
				file_put_contents($this->getLogFilePath(), $logEntry, FILE_APPEND);
			}
		}

		protected function shouldLog(string $level): bool
		{
			$currentLevelIndex = array_search($this->logLevel, $this->logLevels);
			$messageLevelIndex = array_search($level, $this->logLevels);
			return $messageLevelIndex >= $currentLevelIndex;
		}

		protected function formatLogEntry(string $level, string $message, array $context): string
		{
			$timestamp = Carbon::format('Y-m-d H:i:s');
			$level = strtolower($level);
			$levelUpper = strtoupper($level);

			$icons = [
				'info' => 'ℹ️',
				'error' => '🚨',
				'warning' => '⚠️',
				'debug' => '🐞',
			];

			$icon = $icons[$level] ?? '📌';
			$log = "{$icon} [{$levelUpper}] [{$timestamp}]\n\n";
			$log .= "Message  : {$message}\n";

			if (isset($context['file'])) {
				$log .= "File     : {$context['file']}\n";
			}
			if (isset($context['line'])) {
				$log .= "Line     : {$context['line']}\n";
			}

			if (isset($context['trace'])) {
				$log .= "\n🔍 Trace:\\n" . trim($context['trace']) . "\n";
			}

			$log .= str_repeat('-', 47) . "\n\n\n\n";

			return $log;
		}
	}
