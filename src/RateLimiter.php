<?php

	namespace Core;

	use App\Utilities\Cache;
	use App\Utilities\Server;

	class RateLimiter
	{
		public static function attempt(string $key, int $limit = 10, int $decayRate = 120): bool {

			$ip = Server::IPAddress();
			$cacheKey = "rate:$ip:$key";
			$rateLimit = Cache::get($cacheKey);

			if ($rateLimit === false) {
				Cache::set($cacheKey, $limit - 1, $decayRate);
				return true;
			}

			if ($rateLimit > 0) {
				$expirationTime = Cache::getExpiration($cacheKey);
				if ($expirationTime !== false) {
					Cache::set($cacheKey, $rateLimit - 1, $expirationTime);
					return true;
				} else {
					return self::attempt($key, $limit, $decayRate);
				}
			}

			return false;
		}

		public static function perMinute(string $key, int $limit = 1): bool {

			if (self::attempt($key, $limit, 60)) {
				return true;
			}

			return false;
		}

		public static function perHour(string $key, int $limit = 1): bool {

			if (self::attempt($key, $limit, 60 * 60)) {
				return true;
			}

			return false;
		}

		public static function perDay(string $key, int $limit = 1): bool {

			if (self::attempt($key, $limit, 60 * 60 * 24)) {
				return true;
			}

			return false;
		}

		public static function perMonth(string $key, int $limit = 1): bool {

			if (self::attempt($key, $limit, 60 * 60 * 24 * 30)) {
				return true;
			}

			return false;
		}
	}
