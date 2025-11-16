<?php

	namespace App\Utilities\Handler;

	use App\Databases\Database;
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

		public function read($id): string
		{
			$data = Database::table($this->table)
				->select('data')
				->where('id', $id)
				->field();

			return $data ?? '';
		}

		public function write($id, $data): bool
		{
			$now = date('Y-m-d H:i:s');
			$ip = Server::IPAddress();
			$userAgent = Server::UserAgent();

			Database::replace($this->table, [
				'id' => $id,
				'data' => $data,
				'user_id' => $_SESSION['user_id'] ?? null,
				'ip_address' => $ip,
				'user_agent' => $userAgent,
				'last_activity' => $now,
				'created_at' => $now
			]);

			return true;
		}

		public function destroy($id): bool
		{
			return (bool) Database::table($this->table)
				->where('id', $id)
				->delete();
		}

		public function gc($max_lifetime): int|false
		{
			$threshold = date('Y-m-d H:i:s', time() - $max_lifetime);

			return (bool) Database::table($this->table)
				->where('last_activity', '<', $threshold)
				->delete();
		}
	}