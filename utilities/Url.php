<?php

	namespace App\Utilities;

	use App\Routes\Route;

	final class Url
	{
		/**
		 * Generate a URL relative to the application base.
		 *
		 * @param string $path  Relative path
		 * @param array  $query Optional query parameters
		 * @return string
		 */
		public function to(string $path = '', array $query = []): string
		{
			$base = rtrim($this->base(), '/');
			$path = ltrim($path, '/');
			$url = $base . ($path ? '/' . $path : '');

			if (!empty($query)) {
				$url .= '?' . http_build_query($query);
			}

			return $url;
		}

		/**
		 * Generate a URL to a named route with parameters and query string.
		 *
		 * @param string $name
		 * @param array  $params
		 * @param array  $query
		 * @return string
		 */
		public function route(string $name, array $params = [], array $query = []): string
		{
			$url = Route::link($name, $params);

			if (!empty($query)) {
				$url .= '?' . http_build_query($query);
			}

			return $url;
		}

		/**
		 * Return the current URL path (without query string).
		 *
		 * @return string
		 */
		public function current(): string
		{
			$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
			$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
			$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

			return "$scheme://$host$uri";
		}

		/**
		 * Return the full current URL including query string.
		 *
		 * @return string
		 */
		public function full(): string
		{
			$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
			$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
			$uri = $_SERVER['REQUEST_URI'] ?? '/';

			return "$scheme://$host$uri";
		}

		/**
		 * Return the previous URL (referrer) or a default.
		 *
		 * @param string $default
		 * @return string
		 */
		public function previous(string $default = '/'): string
		{
			return $_SERVER['HTTP_REFERER'] ?? $this->to($default);
		}

		/**
		 * Generate a URL to a public asset.
		 *
		 * @param string $path
		 * @param array  $query
		 * @return string
		 */
		public function asset(string $path, array $query = []): string
		{
			$path = ltrim($path, '/');
			return $this->to($path, $query);
		}

		/**
		 * Determine the application base URL.
		 * Uses 'app.url' config if available, otherwise detects from server.
		 *
		 * @return string
		 */
		public function base(): string
		{
			$url = env('APP_URL');
			if ($url) {
				return rtrim($url, '/');
			}

			$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
			$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

			return "$scheme://$host";
		}

		/**
		 * Generate a secure HTTPS URL.
		 *
		 * @param string $path
		 * @param array  $query
		 * @return string
		 */
		public function secure(string $path = '', array $query = []): string
		{
			$url = $this->to($path, $query);
			return preg_replace('#^http:#', 'https:', $url);
		}
	}