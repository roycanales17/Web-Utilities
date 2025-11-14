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

			// Referrer domain (if exists)
			$referrer = Server::Referer();
			$referrerDomain = $referrer !== 'No Referer' ? parse_url($referrer, PHP_URL_HOST) : 'N/A';

			// Log to console
			console_log("\n====================\n**Application Request**\n====================");
			console_log("User Agent: %s", [$userAgent]);
			console_log("Full URL: %s", [$fullUrl]);
			console_log("Referrer Domain: %s", [$referrerDomain]);
			console_log("Method: %s", [$method]);
			console_log("IP: %s", [$ip]);
			console_log("Client Port: %d", [Server::ClientPort()]);
			console_log("Is Ajax: %s", [Server::isAjaxRequest() ? 'Yes' : 'No']);
			console_log("Request ID: %s", [Server::RequestId()]);
			console_log("\n====================\n**Starting Application**\n====================");
		}
	}