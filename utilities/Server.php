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
		 * Gets the client's IP address, checking common proxy headers.
		 *
		 * @return string The client IP address or "Unknown IP" if not available or invalid.
		 */
		public static function IPAddress(): string
		{
			if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
				$ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
			} elseif (!empty($_SERVER['HTTP_X_REAL_IP'])) {
				$ip = $_SERVER['HTTP_X_REAL_IP'];
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
		public static function UserAgent(): string
		{
			return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent';
		}

		/**
		 * Gets the requested hostname.
		 *
		 * @return string The host name or "Unknown Host" if not available.
		 */
		public static function HostName(): string
		{
			return $_SERVER['HTTP_HOST'] ?? 'Unknown Host';
		}

		/**
		 * Gets the HTTP request method (GET, POST, etc).
		 *
		 * @return string The request method or "Unknown Method" if not available.
		 */
		public static function RequestMethod(): string
		{
			return $_SERVER['REQUEST_METHOD'] ?? 'Unknown Method';
		}

		/**
		 * Gets the full request URI.
		 *
		 * @return string The request URI or "/" if not available.
		 */
		public static function RequestURI(): string
		{
			return $_SERVER['REQUEST_URI'] ?? '/';
		}

		/**
		 * Gets the HTTP referer.
		 *
		 * @return string The referer URL or "No Referer" if not available.
		 */
		public static function Referer(): string
		{
			return $_SERVER['HTTP_REFERER'] ?? 'No Referer';
		}

		/**
		 * Gets the query string from the request.
		 *
		 * @return string The query string or an empty string if not available.
		 */
		public static function QueryString(): string
		{
			return $_SERVER['QUERY_STRING'] ?? '';
		}

		/**
		 * Checks if the current connection is secure (HTTPS).
		 *
		 * @return bool True if the connection is HTTPS, false otherwise.
		 */
		public static function IsSecureConnection(): bool
		{
			return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
				($_SERVER['SERVER_PORT'] ?? null) == 443;
		}

		/**
		 * Gets the client's port number.
		 *
		 * @return int The client port number or 0 if not available.
		 */
		public static function ClientPort(): int
		{
			return (int) ($_SERVER['REMOTE_PORT'] ?? 0);
		}

		/**
		 * Gets the server's IP address.
		 *
		 * @return string The server IP address or "Unknown Server IP" if not available.
		 */
		public static function ServerIPAddress(): string
		{
			return $_SERVER['SERVER_ADDR'] ?? 'Unknown Server IP';
		}

		/**
		 * Gets the timestamp of the request.
		 *
		 * @return int The Unix timestamp of the request or current time if not available.
		 */
		public static function RequestTime(): int
		{
			return $_SERVER['REQUEST_TIME'] ?? time();
		}
	}
