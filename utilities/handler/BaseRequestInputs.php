<?php

	namespace App\Utilities\Handler;

	use App\Utilities\Server;

	/**
	 * Trait BaseRequestInputs
	 *
	 * Provides convenient access to HTTP request input data, including JSON payloads,
	 * query parameters, POST data, cookies, and uploaded files.
	 *
	 * Supports "all", "only", and "except" methods for filtering data,
	 * and magic methods for dynamic property access.
	 * @internal
	 */
	trait BaseRequestInputs
	{
		/**
		 * Merge JSON payload into internal data array if Content-Type is application/json.
		 */
		protected function populateJson(): void
		{
			if (Server::contentType() === 'application/json') {
				$input = json_decode(file_get_contents('php://input'), true);
				if (is_array($input)) {
					$this->data = array_merge($this->data, $input);
				}
			}
		}

		public function input($key, $default = null)
		{
			return $this->data[$key] ?? $default;
		}

		public function query($key, $default = null)
		{
			return $_GET[$key] ?? $default;
		}

		public function post($key, $default = null)
		{
			return $_POST[$key] ?? $default;
		}

		public function json($key, $default = null)
		{
			return $this->data[$key] ?? $default;
		}

		public function cookie($key, $default = null)
		{
			return fetchCookie($key, $default);
		}

		public function file($key, $default = null)
		{
			return $this->files[$key] ?? $default;
		}

		public function all(): array
		{
			return $this->data;
		}

		public function only(array|string $keys): array
		{
			$keys = is_string($keys) ? explode(',', $keys) : $keys;
			$result = [];
			foreach ($keys as $key) {
				if (isset($this->data[$key])) {
					$result[$key] = $this->data[$key];
				}
			}
			return $result;
		}

		public function except(array|string $keys): array
		{
			$keys = is_string($keys) ? explode(',', $keys) : $keys;
			$result = $this->data;
			foreach ($keys as $key) {
				unset($result[$key]);
			}
			return $result;
		}

		// -------------------------
		// Magic methods for dynamic property access
		// -------------------------
		public function __get($name)
		{
			return $this->input($name);
		}

		public function __set($name, $value)
		{
			$this->data[$name] = $value;
			if (isset(self::$cachedData)) {
				self::$cachedData[$name] = $value;
			}
		}

		public function __isset($name)
		{
			return isset($this->data[$name]);
		}

		public function __unset($name)
		{
			unset($this->data[$name]);
		}
	}
