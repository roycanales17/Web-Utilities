<?php

	namespace App\Utilities;

	use App\Utilities\Blueprints\CacheDriver;
	use Closure;
	use Exception;
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
			if (!class_exists('Redis'))
				throw new Exception("Redis extension not installed");

			$redis = new Redis();
			if (!$redis->connect($host, (int) $port))
				throw new Exception("Could not connect to redis server");

			self::$driver = $redis;
		}

		protected static function configureMemcached(string $host, string $port): void
		{
			if (!class_exists('Memcached'))
				throw new Exception("Memcached extension not installed");

			$memcached = new Memcached();
			if (!$memcached->addServer($host, (int) $port))
				throw new Exception("Could not connect to memcached server");

			self::$driver = $memcached;
		}

		protected static function cache(): Memcached|Redis
		{
			if (self::$driver)
				return self::$driver;

			throw new Exception("Cache is not configured.");
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
				if ($data === false)
					return $default;

				$data = $cache instanceof Redis ? unserialize($data) : $data;
				return is_array($data) && isset($data['data']) ? $data['data'] : $default;
			}

			return false;
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
