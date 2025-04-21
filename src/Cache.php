<?php

	namespace App\Utilities;

	use App\Utilities\Blueprints\CacheDriver;
	use Closure;
	use Memcached;
	use Redis;

	class Cache
	{
		private static Memcached|Redis|null $driver = null;
		private static ?Closure $callback = null;

		public static function configure(
			CacheDriver $type = CacheDriver::Memcached,
			string $server = '',
			string $port = '',
			?Closure $callback = null
		): void {
			self::$callback = $callback;

			if (!$server || !$port)
				return;

			match ($type) {
				CacheDriver::Redis => self::configureRedis($server, $port),
				CacheDriver::Memcached => self::configureMemcached($server, $port),
			};
		}

		protected static function configureRedis(string $host, string $port): void
		{
			if (!class_exists('Redis')) {
				self::throw('Redis class not found.');
				return;
			}

			$redis = new Redis();
			if (!$redis->connect($host, (int) $port)) {
				self::throw('Failed to connect to Redis server.');
				return;
			}

			self::$driver = $redis;
		}

		protected static function configureMemcached(string $host, string $port): void
		{
			if (!class_exists('Memcached')) {
				self::throw('Memcached class not found.');
				return;
			}

			$memcached = new Memcached();
			if (!$memcached->addServer($host, (int) $port)) {
				self::throw('Failed to connect to Memcached server.');
				return;
			}

			self::$driver = $memcached;
		}

		protected static function cache(): Memcached|Redis|false
		{
			if (self::$driver) return self::$driver;

			self::throw('Cache is not configured.');
			return false;
		}

		protected static function throw(string $message): void
		{
			if (self::$callback)
				call_user_func(self::$callback, $message);
		}

		public static function remember(string $key, callable $callback, int $expiration = 60): mixed
		{
			if ($cache = self::cache()) {
				$key = "remember:$key";
				$data = $cache->get($key);

				if ($data !== false) {
					return is_array($data) && isset($data['data']) ? $data['data'] : $data;
				}

				$value = $callback();
				self::set($key, $value, $expiration);
				return $value;
			}

			return $callback();
		}

		public static function has(string $key): bool
		{
			return self::cache()?->get($key) !== false;
		}

		public static function set(string $key, mixed $value, int $expiration = 0): bool
		{
			if ($cache = self::cache()) {
				$data = [
					'data' => $value,
					'expires_at' => time() + $expiration,
				];

				return $cache instanceof Redis
					? $cache->setEx($key, $expiration, serialize($data))
					: $cache->set($key, $data, $expiration);
			}
			return false;
		}

		public static function get(string $key, mixed $default = false): mixed
		{
			if ($cache = self::cache()) {
				$data = $cache->get($key);
				if ($data === false) return $default;

				$data = $cache instanceof Redis ? unserialize($data) : $data;
				return is_array($data) && isset($data['data']) ? $data['data'] : $default;
			}
			return $default;
		}

		public static function delete(string $key): bool
		{
			return self::cache()?->del($key) ?? false;
		}

		public static function clear(): bool
		{
			$cache = self::cache();
			if (!$cache) return false;

			if ($cache instanceof Memcached)
				return $cache->flush();

			if ($cache instanceof Redis)
				return $cache->flushAll();

			return false;
		}
	}
