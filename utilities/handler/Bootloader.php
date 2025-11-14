<?php

	namespace App\Utilities\Handler;

	abstract class Bootloader {
		private array $args;

		public function __construct(array $params = []) {
			$this->args = $params;
		}

		protected function argument(string $key): mixed {
			return $this->args[$key] ?? null;
		}

		protected function isCli(): bool {
			return get_constant('CLI_MODE', false);
		}

		protected function isDevelopment(): bool {
			return get_constant('DEVELOPMENT', true);
		}

		abstract public function handler(): void;
	}