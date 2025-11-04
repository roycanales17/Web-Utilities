<?php

	namespace Commands;

	use App\Console\Command;

	class Mailable extends Command
	{
		protected string $signature = 'make:mail';
		protected string $description = 'Generate a new mailable class.';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Mail class name is required.');
				return;
			}

			$this->info('⏳ Initializing mail class file generation...');

			// Extract class info (directory, namespace, class name)
			$classInfo = $this->extractClassInfo($className, 'handler/Mails');
			$basePath = base_path($classInfo['directory']);

			// Class file content
			$content = <<<PHP
			<?php
			
				namespace {$classInfo['namespace']};
				
				use App\Utilities\Handler\Mailable;
				
				class {$classInfo['class']} extends Mailable
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

			// Create the PHP class file
			$this->create("{$classInfo['class']}.php", $content, $basePath);
		}
	}