<?php

	namespace App\Utilities;

	/**
	 * Class Server
	 *
	 * Provides static utility methods to retrieve server and request-related information.
	 *
	 * @package App\Utilities
	 */
	final class Server
	{
		/**
		 * Returns all request headers (cached).
		 *
		 * @return array
		 */
		private static function headers(): array
		{
			static $headers = null;

			if ($headers === null) {
				if (function_exists('getallheaders')) {
					$headers = getallheaders();
				} else {
					$headers = [];
					foreach ($_SERVER as $key => $value) {
						if (strpos($key, 'HTTP_') === 0) {
							$name = str_replace('_', '-', substr($key, 5));
							$headers[$name] = $value;
						}
					}

					// Content-Type and Content-Length are not prefixed with HTTP_
					if (isset($_SERVER['CONTENT_TYPE'])) {
						$headers['Content-Type'] = $_SERVER['CONTENT_TYPE'];
					}
					if (isset($_SERVER['CONTENT_LENGTH'])) {
						$headers['Content-Length'] = $_SERVER['CONTENT_LENGTH'];
					}
				}
			}

			return $headers;
		}

		/**
		 * Returns a single header value (case-insensitive).
		 *
		 * @param string $name
		 * @return string|null
		 */
		public static function header(string $name): ?string
		{
			$headers = self::headers();
			$name = strtolower($name);

			foreach ($headers as $key => $value) {
				if (strtolower($key) === $name) {
					return $value;
				}
			}

			return null;
		}

		/**
		 * Gets the client's IP address, checking common proxy headers.
		 *
		 * @return string The client IP address or "Unknown IP" if not available or invalid.
		 */
		public static function IPAddress(): string
		{
			$forwarded = self::header('X-Forwarded-For');
			$real      = self::header('X-Real-IP');

			if (!empty($forwarded)) {
				$ip = explode(',', $forwarded)[0];
			} elseif (!empty($real)) {
				$ip = $real;
			} else {
				$ip = $_SERVER['REMOTE_ADDR'] ?? '';
			}

			return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'Unknown IP';
		}

		/**
		 * Gets the client's User Agent string.
		 *
		 * @return string The User Agent or "Unknown User Agent" if not available.
		 */
		public static function userAgent(): string
		{
			return self::header('User-Agent') ?? 'Unknown User Agent';
		}

		/**
		 * Gets the requested hostname.
		 *
		 * @return string The host name or "Unknown Host" if not available.
		 */
		public static function hostName(): string
		{
			return self::header('Host') ?? 'Unknown Host';
		}

		/**
		 * Gets the HTTP request method (GET, POST, etc).
		 *
		 * @return string The request method or "Unknown Method" if not available.
		 */
		public static function requestMethod(): string
		{
			return $_SERVER['REQUEST_METHOD'] ?? 'Unknown Method';
		}

		/**
		 * Gets the full request URI.
		 *
		 * @return string The request URI or "/" if not available.
		 */
		public static function requestURI(): string
		{
			return $_SERVER['REQUEST_URI'] ?? '/';
		}

		/**
		 * Gets the HTTP referer.
		 *
		 * @return string The referer URL or "No Referer" if not available.
		 */
		public static function referer(): string
		{
			return self::header('Referer') ?? 'No Referer';
		}

		/**
		 * Gets the query string from the request.
		 *
		 * @return string The query string or an empty string if not available.
		 */
		public static function queryString(): string
		{
			return $_SERVER['QUERY_STRING'] ?? '';
		}

		/**
		 * Checks if the current connection is secure (HTTPS).
		 *
		 * @return bool True if the connection is HTTPS, false otherwise.
		 */
		public static function isSecureConnection(): bool
		{
			return
				(self::header('X-Forwarded-Proto') === 'https') ||
				(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
				(($_SERVER['SERVER_PORT'] ?? null) == 443);
		}

		/**
		 * Gets the client's port number.
		 *
		 * @return int The client port number or 0 if not available.
		 */
		public static function clientPort(): int
		{
			return (int) ($_SERVER['REMOTE_PORT'] ?? 0);
		}

		/**
		 * Gets the server's IP address.
		 *
		 * @return string The server IP address or "Unknown Server IP" if not available.
		 */
		public static function serverIPAddress(): string
		{
			return $_SERVER['SERVER_ADDR'] ?? 'Unknown Server IP';
		}

		/**
		 * Gets the timestamp of the request.
		 *
		 * @return int The Unix timestamp of the request or current time if not available.
		 */
		public static function requestTime(): int
		{
			return $_SERVER['REQUEST_TIME'] ?? time();
		}

		/**
		 * Checks if the current request is an AJAX request.
		 *
		 * Detection logic:
		 *  1. Checks the "X-Requested-With" HTTP header (commonly set by jQuery).
		 *  2. Falls back to checking if an "ajax" key is present in $_POST or $_GET.
		 *
		 * @return bool True if the request is AJAX, false otherwise.
		 */
		public static function isAjaxRequest(): bool
		{
			// 1. Standard AJAX header
			$requestedWith = self::header('X-Requested-With');

			if (!empty($requestedWith) && strtolower($requestedWith) === 'xmlhttprequest') {
				return true;
			}

			// 2. Stream/Wire header (use header getter instead of $_SERVER)
			if (self::header('X-STREAM-WIRE')) {
				return true;
			}

			// 3. JSON-based detection
			$accept = strtolower(self::header('Accept') ?? '');
			if (str_contains($accept, 'application/json') || str_contains($accept, 'json')) {
				return true;
			}

			// 4. Query-based JSON request
			if (isset($_GET['format']) && strtolower($_GET['format']) === 'json') {
				return true;
			}

			// 5. URL ajax flag
			if (isset($_POST['ajax']) || isset($_GET['ajax'])) {
				return true;
			}

			return false;
		}

		/**
		 * Gets the Content-Type of the request (e.g., application/json).
		 *
		 * @return string The content type or "Unknown Content-Type" if not available.
		 */
		public static function contentType(): string
		{
			return self::header('Content-Type') ?? 'text/html';
		}

		/**
		 * Gets the Accept header (e.g., application/json, text/html).
		 *
		 * @return string The Accept header or "Unknown Accept" if not available.
		 */
		public static function accept(): string
		{
			return self::header('Accept') ?? 'Unknown Accept';
		}

		/**
		 * Gets the HTTP protocol version (e.g., HTTP/1.1, HTTP/2).
		 *
		 * @return string The protocol or "Unknown Protocol" if not available.
		 */
		public static function protocol(): string
		{
			return $_SERVER['SERVER_PROTOCOL'] ?? 'Unknown Protocol';
		}

		/**
		 * Gets the request correlation ID (X-Request-ID header).
		 * Generates a random ID if not provided by the client.
		 *
		 * @return string The request ID.
		 */
		public static function requestId(): string
		{
			return self::header('X-Request-ID') ?? bin2hex(random_bytes(8));
		}

		/**
		 * Builds a fully qualified URL with optional query parameters.
		 *
		 * @param string $path   The URL path (e.g. "/reset-password")
		 * @param array  $params Optional query parameters to append (e.g. ['token' => 'abc123'])
		 *
		 * @return string
		 */
		public static function makeURL(string $path, array $params = []): string
		{
			$path  = '/' . ltrim($path, '/');
			$query = $params ? '?' . http_build_query($params) : '';

			$scheme = self::isSecureConnection() ? 'https://' : 'http://';
			$host   = self::hostName();

			return rtrim($scheme . $host, '/') . $path . $query;
		}
	}