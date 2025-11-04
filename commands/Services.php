<?php

	namespace Commands;

	use App\Console\Command;

	class Services extends Command
	{
		protected string $signature = 'make:service';
		protected string $description = 'Generates a custom service class.';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Service class name is required.');
				return;
			}

			$this->info('⏳ Initializing service class file generation...');

			// Extract class info (handles namespace and directories)
			$classInfo = $this->extractClassInfo($className, 'handler/Services');
			$className = $classInfo['class'];
			$basePath = base_path($classInfo['directory']);
			$namespace = $classInfo['namespace'];

			$content = <<<PHP
			<?php

				namespace {$namespace};

				class {$className}
				{
					/**
					 * Initialize your service.
					 */
					public function __construct()
					{
						// TODO: Add initialization logic if needed
					}

					/**
					 * Example method to execute service logic.
					 */
					public function execute(...\$args)
					{
						// TODO: Implement service functionality
					}
				}
			PHP;

			$this->create("$className.php", $content, $basePath);
		}
	}
