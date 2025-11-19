<?php

	namespace App\Utilities\Handler;

	use App\Utilities\Server;

	/**
	 * Trait BaseRequestHeaders
	 *
	 * Provides convenient methods to access HTTP request information
	 * such as headers, server variables, request method, URL, and AJAX detection.
	 *
	 * This trait relies on the Server utility class for most of its operations.
	 * @internal
	 */
	trait BaseRequestHeaders
	{
		/**
		 * Retrieve the value of a specific HTTP header.
		 *
		 * @param string $header
		 * @return string|null
		 */
		public function header(string $header): ?string
		{
			return Server::header($header);
		}

		/**
		 * Access a $_SERVER variable or return a default value.
		 *
		 * @param string $key
		 * @param mixed|null $default
		 * @return mixed
		 */
		public function server(string $key, mixed $default = null): mixed
		{
			return $_SERVER[$key] ?? $default;
		}

		/**
		 * Get the HTTP request method (GET, POST, etc.).
		 *
		 * @return string
		 */
		public function method(): string
		{
			return Server::requestMethod();
		}

		/**
		 * Check if the request method matches a given method.
		 *
		 * @param string $method
		 * @return bool
		 */
		public function isMethod(string $method): bool
		{
			return strtoupper($method) === $this->method();
		}

		/**
		 * Get the requested path without query parameters.
		 *
		 * @return string
		 */
		public function path(): string
		{
			$uri = Server::requestURI();
			return strtok($uri, '?');
		}

		/**
		 * Get the full URL of the request, including scheme and host.
		 *
		 * @return string
		 */
		public function fullUrl(): string
		{
			$scheme = Server::isSecureConnection() ? 'https://' : 'http://';
			$host = Server::hostName();
			return $scheme . $host . Server::requestURI();
		}

		/**
		 * Determine if the current request is an AJAX request.
		 *
		 * @return bool
		 */
		public function isAjaxRequest(): bool
		{
			return Server::isAjaxRequest();
		}
	}
