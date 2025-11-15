<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Handler\Bootloader;
	use App\Utilities\Server;

	final class StartupLog extends Bootloader
	{
		public function handler(): void
		{
			// Full URL
			$fullUrl = Server::makeURL(Server::RequestURI());

			// Request method
			$method = Server::RequestMethod();

			// User Agent
			$userAgent = Server::UserAgent();

			// Client IP
			$ip = Server::IPAddress();

			// Content Type
			$contentType = Server::ContentType();

			// Referrer domain (if exists)
			$referrer = Server::Referer();
			$referrerDomain = $referrer !== 'No Referer' ? parse_url($referrer, PHP_URL_HOST) : 'N/A';

			// Log to console
			console_log("\n\n\n\n\n=======================\n> **Application Request** <\n=======================");
			console_log("User Agent: %s", [$userAgent]);
			console_log("Full URL: %s", [$fullUrl]);
			console_log("Referrer Domain: %s", [$referrerDomain]);
			console_log("Content Type: %s", [$contentType]);
			console_log("Method: %s", [$method]);
			console_log("IP: %s", [$ip]);
			console_log("\n\n\n========================\n> **Starting Application** <\n========================");
		}
	}
