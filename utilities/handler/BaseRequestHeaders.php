<?php

	namespace App\Utilities\Handler;

	use App\Utilities\Server;

	trait BaseRequestHeaders
	{
		public function header(string $header): ?string
		{
			return Server::header($header);
		}

		public function server($key, $default = null): mixed
		{
			return $_SERVER[$key] ?? $default;
		}

		public function method(): string
		{
			return Server::requestMethod();
		}

		public function isMethod(string $method): bool
		{
			return strtoupper($method) === $this->method();
		}

		public function path(): string
		{
			$uri = Server::requestURI();
			return strtok($uri, '?');
		}

		public function fullUrl(): string
		{
			$scheme = Server::isSecureConnection() ? 'https://' : 'http://';
			$host = Server::hostName();
			return $scheme . $host . Server::requestURI();
		}

		public function isAjaxRequest(): bool
		{
			return Server::isAjaxRequest();
		}
	}