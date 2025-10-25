<?php

	namespace Commands;
	
	use App\Console\Command;
	
	class Mailable extends Command {
		
		protected string $signature = 'make:mail';
		protected string $description = 'Generate a new mailable class.';

		public function handle(string $className = ''): void
		{
			if (!$className) {
				$this->error('Mail class name is required.');
				return;
			}

			$this->info('⏳ Initializing mail class file generation...');

			// Normalize directory separators and trim slashes
			$className = str_replace(['\\', '/'], '/', trim($className, '/'));

			// Split into path parts
			$parts = explode('/', $className);
			$rawClass = array_pop($parts);
			$namespaceParts = array_map('ucfirst', $parts);
			$className = ucfirst(preg_replace('/[^A-Za-z0-9_]/', '', $rawClass));

			// Build namespace (e.g., Mails\Test\Subdir)
			$namespace = 'Mails' . (!empty($namespaceParts) ? '\\' . implode('\\', $namespaceParts) : '');

			// Build directory path (relative to /mails)
			$relativeDir = implode(DIRECTORY_SEPARATOR, $namespaceParts);
			$targetDir = base_path('mails' . ($relativeDir ? DIRECTORY_SEPARATOR . $relativeDir : ''));

			// Filename
			$filename = $className . '.php';

			// Generate file content
			$content = <<<PHP
			<?php

				namespace {$namespace};
				
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

			// Create using helper method
			if ($this->create($filename, $content, $targetDir)) {
				$this->success("✅ Mail class '{$namespace}\\{$className}' has been successfully created.");
				return;
			}

			$this->error("❌ Failed to create the file '{$filename}'.");
		}
	}