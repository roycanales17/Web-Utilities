<?php

	namespace Commands;

	use App\Console\Command;

	class Utilities extends Command
	{
		protected string $signature = 'make:utility';
		protected string $description = 'Generates a custom utility class.';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Utilities class name is required.');
				return;
			}

			$this->info('⏳ Initializing utility class file generation...');

			// Extract class info (handles namespace and directories)
			$classInfo = $this->extractClassInfo($className, 'handler/Utilities');
			$className = $classInfo['class'];
			$basePath = base_path($classInfo['directory']);
			$namespace = $classInfo['namespace'];

			$content = <<<PHP
			<?php

				namespace {$namespace};

				final class {$className}
				{
					/**
					 * Initialize your utility.
					 */
					public function __construct()
					{
						// TODO: Add initialization logic if needed
					}
				}
			PHP;

			$this->create("$className.php", $content, $basePath);
		}
	}
