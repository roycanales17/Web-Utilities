<?php

	namespace Commands;

	use App\Console\Command;

	class Controller extends Command
	{
		protected string $signature = 'make:controller';
		protected string $description = 'Generates controller class';

		public function handle(string $className = ''): void
		{
			if (!$className) {
				$this->error('Controller class name is required.');
				return;
			}

			$this->info('⏳ Initializing controller class file generation...');

			$className = preg_replace('/[^A-Za-z0-9_]/', '', $className);
			$className = ucfirst($className);

			$filename = $className . '.php';
			$content = <<<HTML
			<?php

				namespace Http\Controllers;
				
				use App\Http\Controller;
				
				class {$className} extends Controller {
				
					public function index() {
					
					}
				}
			HTML;

			if ($this->create($filename, $content, dirname('./'). '/Http/Controllers')) {
				$this->success("✅ Controller class file '{$filename}' has been successfully created and is ready for use.");
				return;
			}

			$this->error("❌ Failed to create the file '{$filename}'.");
		}
	}
