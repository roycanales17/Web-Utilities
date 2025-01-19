<?php

	namespace App\Utilities\Handler;

	use SessionHandlerInterface;

	class SessionHandler implements SessionHandlerInterface
	{
		private string $table;

		function __construct(string $table = 'session')
		{
			$this->table = $table;
		}

		public function open($path, $name): bool
		{
			return true;
		}

		public function close(): bool
		{
			return true;
		}

		public function read($id): string|false
		{
			return false;
		}

		public function write($id, $data): bool
		{
			return false;
		}

		public function destroy($id): bool
		{
			return false;
		}

		public function gc($max_lifetime): int|false
		{
			return false;
		}

		public function session_close(): void
		{

		}
	}
