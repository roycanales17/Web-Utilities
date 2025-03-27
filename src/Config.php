<?php

	namespace App\Utilities;

	class Config
	{
		protected static array $config = [];

		public static function load(string $path): void
		{
			if (file_exists($path)) {
				$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				foreach ($lines as $line) {
					if (str_starts_with(trim($line), '#')) {
						continue;
					}
					list($key, $value) = explode('=', $line, 2) + [null, null];
					if ($key !== null && $value !== null) {
						self::$config[trim($key)] = trim($value);
					}
				}
			}
		}

		public static function get(string $key, mixed $default = null): mixed
		{
			$keys = explode('.', $key);
			$value = self::$config;

			foreach ($keys as $keyPart) {
				if (!is_array($value) || !array_key_exists($keyPart, $value)) {
					return $default;
				}
				$value = $value[$keyPart];
			}

			return $value;
		}

		public static function set(string $key, mixed $value): void
		{
			$keys = explode('.', $key);
			$config =& self::$config;

			foreach ($keys as $keyPart) {
				if (!isset($config[$keyPart]) || !is_array($config[$keyPart])) {
					$config[$keyPart] = [];
				}
				$config =& $config[$keyPart];
			}

			$config = $value;
		}
	}
