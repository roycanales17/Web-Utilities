<?php

	namespace App\Utilities;

	use App\Utilities\Handler\DatabaseSessionHandler;
	// use App\Utilities\Handler\RedisSessionHandler;

	class Session
	{
		public static function configure(array $config): void
		{
			if (session_status() === PHP_SESSION_NONE) {

				// Set default driver
				$config['driver'] = ($config['driver'] ?? 'file');

				// Session lifetime
				ini_set('session.gc_maxlifetime', ($config['lifetime'] ?? 120) * 60);

				// Session save path (for file driver)
				if ($config['driver'] === 'file') {
					$storagePath = $config['storage_path'] ?? '../storage/sessions';
					if (!is_dir($storagePath)) {
						mkdir($storagePath, 0755, true);
					}
					session_save_path($storagePath);
				}

				// Cookie parameters
				session_set_cookie_params([
					'lifetime' => $config['expire_on_close'] ? 0 : ($config['lifetime'] ?? 120) * 60,
					'path'     => $config['path'] ?? '/',
					'domain'   => $config['domain'] ?? '',
					'secure'   => $config['secure'] ?? false,
					'httponly' => $config['http_only'] ?? true,
					'samesite' => $config['same_site'] ?? 'lax',
				]);
			}

			switch ($config['driver']) {
				case 'redis':
					// session_set_save_handler(new RedisSessionHandler($object), true);
					break;

				case 'database':
					session_set_save_handler(new DatabaseSessionHandler, true);
					break;
			}
		}

		public static function start(): void
		{
			if (session_status() === PHP_SESSION_NONE) {
				session_start();
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