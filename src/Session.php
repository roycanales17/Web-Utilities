<?php

	namespace App\Utilities;

	class Session
	{
		public static function start(): void
		{
			if (session_status() === PHP_SESSION_NONE) {
				@session_start();
			}
		}

		public static function started(): bool
		{
			return session_status() === PHP_SESSION_ACTIVE;
		}

		public static function set(string $key, mixed $value): void
		{
			self::start();
			$_SESSION[$key] = $value;
		}

		public static function get(string $key, mixed $default = null): mixed
		{
			self::start();
			return $_SESSION[$key] ?? $default;
		}

		public static function has(string $key): bool
		{
			self::start();
			return isset($_SESSION[$key]);
		}

		public static function remove(string $key): void
		{
			self::start();
			if (isset($_SESSION[$key])) {
				unset($_SESSION[$key]);
			}
		}

		public static function flash(string $key, mixed $value = null): mixed
		{
			self::start();

			if ($value !== null) {
				$_SESSION['__flash'][$key] = $value;
				return null;
			}

			$flashValue = $_SESSION['__flash'][$key] ?? null;
			if (isset($_SESSION['__flash'][$key])) {
				unset($_SESSION['__flash'][$key]);
			}

			return $flashValue;
		}

		public static function destroy(): void
		{
			if (self::started()) {
				session_destroy();
				$_SESSION = [];
			}
		}

		public static function regenerate(bool $deleteOldSession = true): void
		{
			self::start();
			session_regenerate_id($deleteOldSession);
		}
	}