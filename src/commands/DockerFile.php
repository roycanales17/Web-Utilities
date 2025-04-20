<?php

	namespace Commands;

	use App\Console\Command;
	use Dotenv\Dotenv;

	class DockerFile extends Command
	{
		protected string $signature = 'make:docker';
		protected string $description = 'Generates Docker images for the application for development and testing';

		public function loadEnv(string $path): void
		{
			$envFilePath = $path . '/.env';
			if (!file_exists($envFilePath)) {
				$this->error("The .env file was not found at: $envFilePath");
				return;
			}

			$lines = file($envFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			foreach ($lines as $line) {
				if (empty($line) || $line[0] === '#') {
					continue;
				}

				[$key, $value] = explode('=', $line, 2);

				$key = trim($key);
				$value = trim($value);

				putenv("$key=$value");
				$_ENV[$key] = $value;
			}

			$this->info("Environment variables loaded successfully.");
		}

		public function handle(): void
		{
			$root = dirname('../');
			$dockerComposeDir = $root . '/vendor/roy404/utilities';

			$this->loadEnv($root);

			$this->info("⏳ Initializing Docker images...");
			$command = "cd $dockerComposeDir && docker-compose up --build -d";
			$output = shell_exec($command);

			if ($output === null) {
				$this->error("Failed to run docker-compose.");
			} else {
				$this->success("Docker Compose command executed successfully.");
			}
		}
	}
