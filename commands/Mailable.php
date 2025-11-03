<?php

	namespace Commands;
	
	use App\Console\Command;
	
	class Mailable extends Command {
		
		protected string $signature = 'make:mail';
		protected string $description = 'Generate a new mailable class.';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Controller class name is required.');
				return;
			}

			$this->info('⏳ Initializing controller class file generation...');

			$className = preg_replace('/[^A-Za-z0-9_\/]/', '', "handler/Mails/$className");
			$directories = explode('/', $className);
			$className = ucfirst($directories[count($directories) - 1]);

			array_pop($directories);
			$basePath = base_path("/" . implode('/', $directories));
			$namespaceDeclaration = "namespace ". implode('\\', array_map('ucfirst', $directories)) . ";";

			// Generate file content
			$content = <<<PHP
			<?php

				namespace {$namespaceDeclaration};
				
				use App\Utilities\Handler\Mailable;
				
				class {$className} extends Mailable
				{
					public array \$data;

					public function __construct(array \$data)
					{
						\$this->data = \$data;
					}
				
					public function send(): bool
					{
						return \$this->view('welcome', \$this->data)->build();
					}
				}
			PHP;

			if ($this->create("$className.php", $content, $basePath)) {
				$this->success("✅ Mail class file '{$className}' has been successfully created and is ready for use.");
			} else {
				$this->error("❌ Failed to create the file '{$className}.php' at '{$basePath}'.");
			}
		}
	}