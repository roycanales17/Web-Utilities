<?php

	namespace App\Utilities;

	use App\utilities\Blueprints\CacheDriver;
	use Closure;
	use Exception;
	use Memcached;
	use Redis;

	class Cache
	{
		private static array $configured = [];
		private static Memcached|Redis|null $driver = null;
		private static ?Closure $callback = null;

		public static function configure(
			CacheDriver $driver = CacheDriver::Memcached,
			string $server = '',
			string $port = ''
		): void {
			if (!$server || !$port) {
				return;
			}

			self::$configured = [
				'driver' => $driver,
				'server' => $server,
				'port' => $port
			];
		}

		protected static function configureRedis(string $host, string $port): void
		{
			if (!class_exists('Redis')) {
				throw new Exception("Redis extension not installed");
			}

			$redis = new Redis();
			if (!$redis->connect($host, (int) $port)) {
				throw new Exception("Could not connect to Redis server");
			}

			self::$driver = $redis;
		}

		protected static function configureMemcached(string $host, string $port): void
		{
			if (!class_exists('Memcached')) {
				throw new Exception("Memcached extension not installed");
			}

			$memcached = new Memcached();
			if (!$memcached->addServer($host, (int) $port)) {
				throw new Exception("Could not connect to Memcached server");
			}

			self::$driver = $memcached;
		}

		protected static function cache(): Memcached|Redis
		{
			if (!self::$driver) {
				if (empty(self::$configured)) {
					throw new Exception("Cache is not configured.");
				}

				$server = self::$configured['server'];
				$port = self::$configured['port'];

				match (self::$configured['driver']) {
					CacheDriver::Redis => self::configureRedis($server, $port),
					CacheDriver::Memcached => self::configureMemcached($server, $port),
				};
			}

			return self::$driver ?? throw new Exception("Cache is not configured.");
		}

		public static function remember(string $key, callable $callback, int $expiration = 60): mixed
		{
			$cache = self::cache();
			$key = "remember:$key";

			$data = $cache->get($key);
			if ($data !== false) {
				$data = $cache instanceof Redis ? unserialize($data) : $data;
				return $data['data'] ?? $data;
			}

			$value = $callback();
			self::set($key, $value, $expiration);
			return $value;
		}

		public static function has(string $key): bool
		{
			return self::cache()->get($key) !== false;
		}

		public static function set(string $key, mixed $value, int $expiration = 0): bool
		{
			$cache = self::cache();
			$data = [
				'data' => $value,
				'expires_at' => time() + $expiration,
			];

			return $cache instanceof Redis
				? $cache->setEx($key, $expiration, serialize($data))
				: $cache->set($key, $data, $expiration);
		}

		public static function get(string $key, mixed $default = false): mixed
		{
			$cache = self::cache();
			$data = $cache->get($key);
			if ($data === false) {
				return $default;
			}

			$data = $cache instanceof Redis ? unserialize($data) : $data;
			return $data['data'] ?? $default;
		}

		public static function delete(string $key): bool
		{
			$cache = self::cache();
			return $cache instanceof Redis
				? $cache->del($key)
				: $cache->delete($key);
		}

		public static function clear(): bool
		{
			$cache = self::cache();

			return match (true) {
				$cache instanceof Memcached => $cache->flush(),
				$cache instanceof Redis => $cache->flushAll(),
				default => false,
			};
		}

		public static function getExpiration(string $key): mixed
		{
			$cache = self::cache();
			$data = $cache->get($key);

			if ($data === false) {
				return false;
			}

			$data = $cache instanceof Redis ? unserialize($data) : $data;
			return $data['expires_at'] ?? false;
		}
	}
