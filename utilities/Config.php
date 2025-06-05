<?php

	namespace App\Utilities;

	final class Config
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
			if (defined($key))
				return constant($key);

			$keys = explode('.', $key);
			$value = self::$config;

			foreach ($keys as $keyPart) {
				if (!is_array($value) || !array_key_exists($keyPart, $value)) {
					return $default;
				}
				$value = $value[$keyPart];
			}

			return self::castValue($value);
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

		protected static function castValue(mixed $value): mixed
		{
			if (!is_string($value))
				return $value;

			$value = trim($value);

			if ((str_starts_with($value, '{') && str_ends_with($value, '}')) ||
				(str_starts_with($value, '[') && str_ends_with($value, ']'))) {
				$json = json_decode($value, true);
				if (json_last_error() === JSON_ERROR_NONE) {
					return $json;
				}
			}

			$lower = strtolower($value);

			return match ($lower) {
				'true'  => true,
				'false' => false,
				'null'  => null,
				default => (is_numeric($value) ? (
				str_contains($value, '.') ? (float) $value : (int) $value
				) : $value),
			};
		}
	}
