<?php

	namespace App\Utilities\Handler;

	use App\Database\DB;
	use App\Utilities\Server;
	use SessionHandlerInterface;

	class DatabaseSessionHandler implements SessionHandlerInterface
	{
		protected string $table = 'sessions';

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
			return DB::table($this->table)->select('data')->where('id', $id)->field();
		}

		public function write($id, $data): bool
		{
			$ip = Server::IPAddress();
			$now = date('Y-m-d H:i:s');

			return (bool) DB::table($this->table)->replace([
				'id' => $id,
				'data' => $data,
				'ip_address' => $ip,
				'last_activity' => $now
			]);
		}

		public function destroy($id): bool
		{
			return (bool) DB::table($this->table)->where('id', $id)->delete();
		}

		public function gc($max_lifetime): int|false
		{
			$threshold = date('Y-m-d H:i:s', time() - $max_lifetime);
			return (bool) DB::table($this->table)->where('last_activity', '<', $threshold)->delete();
		}

		public function session_close(): void
		{

		}
	}