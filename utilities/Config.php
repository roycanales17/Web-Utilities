<?php

	namespace App\Utilities;

	/**
	 * Class Config
	 *
	 * Loads and accesses configuration values from config files.
	 */
	final class Config
	{
		/** @var array<string, mixed> */
		protected static array $config = [];

		/**
		 * Loads a configuration file from the app's config directory.
		 *
		 * @param string $name Config file name without ".php"
		 * @return void
		 */
		public static function load(string $name): void
		{
			$name = ucfirst($name);
			if (isset(self::$config[$name])) {
				return;
			}

			$file = base_path("app/$name.php");

			if (!file_exists($file)) {
				self::$config[$name] = [];
				return;
			}

			$data = include $file;

			if (!is_array($data)) {
				$data = [];
			}

			self::$config[$name] = $data;
		}

		/**
		 * Retrieves a configuration value using dot notation.
		 *
		 * Example: `Config::get('app.name')`
		 *
		 * @param string $key     Dot-notated key (e.g., "app.debug")
		 * @param mixed  $default Default value if not found
		 * @return mixed
		 */
		public static function get(string $key, mixed $default = null): mixed
		{
			$parts = explode('.', $key);
			$top = array_shift($parts);

			self::load($top);

			$value = self::$config[$top] ?? [];

			foreach ($parts as $part) {
				if (!is_array($value) || !array_key_exists($part, $value)) {
					return $default;
				}
				$value = $value[$part];
			}

			return $value;
		}

		/**
		 * Sets a configuration value using dot notation.
		 *
		 * @param string $key
		 * @param mixed $value
		 * @return void
		 */
		public static function set(string $key, mixed $value): void
		{
			$parts = explode('.', $key);
			$top = array_shift($parts);

			self::load($top);

			$config =& self::$config[$top];

			foreach ($parts as $part) {
				if (!isset($config[$part]) || !is_array($config[$part])) {
					$config[$part] = [];
				}
				$config =& $config[$part];
			}

			$config = $value;
		}
	}