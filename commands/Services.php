<?php

	namespace Commands;

	use App\Console\Command;

	class Services extends Command
	{
		protected string $signature = 'make:service';
		protected string $description = 'Generates custom service class.';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Services class name is required.');
				return;
			}

			$this->info('⏳ Initializing services class file generation...');

			$className = preg_replace('/[^A-Za-z0-9_\/]/', '', "handler/Services/$className");
			$directories = explode('/', $className);
			$className = ucfirst($directories[count($directories) - 1]);

			array_pop($directories);
			$basePath = base_path("/" . implode('/', $directories));
			$namespaceDeclaration = "namespace ". implode('\\', array_map('ucfirst', $directories)) . ";";

			$content = <<<PHP
			<?php
			
				{$namespaceDeclaration}
				
				class {$className}
				{
					
				}
			PHP;

			// Create the PHP file
			if ($this->create("$className.php", $content, $basePath)) {
				$this->success("✅ Service class file '{$className}' has been successfully created and is ready for use.");
			} else {
				$this->error("❌ Failed to create the file '{$className}.php' at '{$basePath}'.");
			}
		}
	}
