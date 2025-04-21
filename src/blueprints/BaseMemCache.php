<?php

	namespace App\Utilities\Blueprints;

	use Closure;
	use Memcached;

	abstract class BaseMemCache
	{
		private static ?Memcached $cache = null;
		private static ?Closure $callback = null;

		public static function configure(string $serverName, string $port, ?Closure $callback = null): void
		{
			if (!$serverName || !$port)
				return;

			self::registerCallback($callback);

			if (class_exists('Memcached')) {

				$cache = new Memcached();
				if ($cache->addServer($serverName, $port)) {
					self::registerCache($cache);
					return;
				}

				self::throw('Failed to connect to Memcache server.');
				return;
			}

			self::throw('Cache is not available.');
		}

		protected static function cache(): Memcached|false
		{
			if (self::$cache)
				return self::$cache;

			self::throw('Cache is not configured yet.');
			return false;
		}

		protected static function throw(string $message): bool
		{
			$closure = self::$callback;

			if ($closure)
				call_user_func($closure, $message);

			return false;
		}

		protected static function registerCache(Memcached $cache): void
		{
			if (self::$cache)
				self::$cache = null;

			self::$cache = $cache;
		}

		protected static function registerCallback($callback): void
		{
			if ($callback)
				self::$callback = $callback;
		}
	}