<?php

	namespace App\Utilities;

	/**
	 * Class Config
	 *
	 * A utility class for loading and accessing configuration values.
	 */
	final class Environment
	{
		/**
		 * The environment data loaded from a file.
		 *
		 * @var array<string, mixed>
		 */
		protected static array $env = [];

		/**
		 * Checks if the environment is empty.
		 *
		 * @return bool True if the configuration is empty, false otherwise.
		 */
		public static function isEmpty(): bool {
			return empty(self::$env);
		}

		/**
		 * Loads environment from an .env file.
		 * Lines starting with '#' are treated as comments and ignored.
		 * Supports ${VAR_NAME} substitution using already loaded values.
		 *
		 * @param string $path The full path to the config file.
		 * @return void
		 */
		public static function load(string $path): void
		{
			if (!file_exists($path)) {
				return;
			}

			$lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

			foreach ($lines as $line) {
				$line = trim($line);

				// Skip comments or empty lines
				if ($line === '' || str_starts_with($line, '#')) {
					continue;
				}

				[$key, $value] = array_pad(explode('=', $line, 2), 2, null);
				if ($key === null || $value === null) {
					continue;
				}

				$key = trim($key);
				$value = trim($value);

				// Handle quoted values
				if (
					(str_starts_with($value, '"') && str_ends_with($value, '"')) ||
					(str_starts_with($value, "'") && str_ends_with($value, "'"))
				) {
					$value = substr($value, 1, -1);
				}

				// Perform variable substitution like ${VAR}
				$value = preg_replace_callback('/\$\{([A-Z0-9_]+)\}/i', function ($matches) {
					$var = $matches[1];
					return self::$env[$var] ?? getenv($var) ?? '';
				}, $value);

				// Store in config
				self::$env[$key] = $value;

				// Also set as environment variables
				putenv("$key=$value");
				$_ENV[$key] = $value;
			}
		}

		public static function get(string $key, mixed $default = null): mixed
		{
			$data = null;
			$keys = explode('.', $key);
			$env = self::$env;

			foreach ($keys as $keyPart) {
				if (!is_array($env) || !array_key_exists($keyPart, $env)) {
					return $default;
				}
				$data = $env[$keyPart] ?? $default;
			}

			if (is_null($data)) {
				$data = $_ENV[$key] ?? $default;
			}

			return self::castValue($data);
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