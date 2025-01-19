<?php

	namespace App\Utilities;

	class Server
	{
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

		public static function UserAgent(): string
		{
			return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent';
		}

		public static function HostName(): string
		{
			return $_SERVER['HTTP_HOST'] ?? 'Unknown Host';
		}

		public static function RequestMethod(): string
		{
			return $_SERVER['REQUEST_METHOD'] ?? 'Unknown Method';
		}

		public static function RequestURI(): string
		{
			return $_SERVER['REQUEST_URI'] ?? '/';
		}

		public static function Referer(): string
		{
			return $_SERVER['HTTP_REFERER'] ?? 'No Referer';
		}

		public static function QueryString(): string
		{
			return $_SERVER['QUERY_STRING'] ?? '';
		}

		public static function IsSecureConnection(): bool
		{
			return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
				($_SERVER['SERVER_PORT'] ?? null) == 443;
		}

		public static function ClientPort(): int
		{
			return (int) ($_SERVER['REMOTE_PORT'] ?? 0);
		}

		public static function ServerIPAddress(): string
		{
			return $_SERVER['SERVER_ADDR'] ?? 'Unknown Server IP';
		}

		public static function RequestTime(): int
		{
			return $_SERVER['REQUEST_TIME'] ?? time();
		}
	}