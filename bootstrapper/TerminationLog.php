<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Handler\Bootloader;

	final class TerminationLog extends Bootloader
	{
		public function handler(): void
		{
			// HTTP status code returned to client
			$statusCode = http_response_code();

			// Content type (response header)
			$contentType = 'text/html';
			foreach (headers_list() as $header) {
				if (stripos($header, 'Content-Type:') === 0) {
					$contentType = trim(substr($header, strlen('Content-Type:')));
					break;
				}
			}

			// Execution time
			$executionTime = $this->argument('executionTime');

			// Memory usage
			$memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2) . ' MB';

			console_log("\n\n\n==========================\n> **Application Terminated** <\n==========================");
			console_log("Response Status Code: %s", [$statusCode]);
			console_log("Response Content-Type: %s", [$contentType]);
			console_log("Execution time: %.6f seconds", [$executionTime]);
			console_log("Peak Memory: %s", [$memoryUsage]);
		}
	}
