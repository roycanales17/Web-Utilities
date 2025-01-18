<?php

	namespace App\Utilities;

	use App\Utilities\Blueprints\BaseMemCache;

	class Cache extends BaseMemCache
	{
		public static function remember(string $key, callable $callback, int $expiration = 60): mixed
		{
			if ($cache = self::cache()) {

				$key = "remember:$key";
				$cached = $cache->get($key);

				if ($cached !== false) {
					return $cached['data'];
				}

				self::set($key, $value = $callback(), $expiration);
				return $value;
			}

			return $callback();
		}

		public static function has(string $key): bool
		{
			if ($cache = self::cache())
				return $cache->get($key) !== false;

			return false;
		}

		public static function set(string $key, mixed $value, int $expiration = 0): bool
		{
			if ($cache = self::cache()) {
				$format = [
					'data' => $value,
					'expires_at' => time() + $expiration,
				];
				return $cache->set($key, $format, $expiration);
			}

			return false;
		}

		public static function get(string $key, mixed $default = false): mixed
		{
			if ($cache = self::cache()) {
				$data = $cache->get($key);

				if ($data !== false)
					return $data['data'];
			}

			return $default;
		}

		public static function delete(string $key): bool
		{
			if ($cache = self::cache())
				return $cache->delete($key);

			return false;
		}

		public static function clear(): bool
		{
			if ($cache = self::cache()) {
				$cache->flush();
				return true;
			}

			return false;
		}

		public static function fetchAll(): array|bool
		{
			if ($cache = self::cache())
				return $cache->getAllKeys();

			return false;
		}

		public static function getExpiration(string $key): ?int
		{
			if ($cache = self::cache()) {
				$data = $cache->get($key);

				if ($data && isset($data['expires_at']))
					return max($data['expires_at'] - time(), 0);
			}

			return false;
		}
	}