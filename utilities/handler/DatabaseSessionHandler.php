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

		public function read($id): string
		{
			$data = DB::table($this->table)->select('data')->where('id', $id)->field();
			return $data ?? '';
		}

		public function write($id, $data): bool
		{
			$ip = Server::IPAddress();
			$now = date('Y-m-d H:i:s');
			$exists = DB::table($this->table)->where('id', $id)->count();

			if ($exists) {
				return (bool) DB::table($this->table)->where('id', $id)->update([
					'data' => $data,
					'ip_address' => $ip,
					'last_activity' => $now
				]);
			} else {
				return (bool) DB::table($this->table)->create([
					'id' => $id,
					'data' => $data,
					'ip_address' => $ip,
					'last_activity' => $now
				]);
			}
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
	}