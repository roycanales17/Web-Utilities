<?php

	namespace Commands;

	use App\Console\Command;
	use App\Headers\Request;

	class Middleware extends Command
	{
		protected string $signature = 'make:middleware';
		protected string $description = 'Generates a new middleware class.';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Middleware class name is required.');
				return;
			}

			$this->info('⏳ Initializing middleware class file generation...');

			// Extract class info (directory, namespace, class name)
			$classInfo = $this->extractClassInfo($className, 'handler/Middleware');
			$basePath = base_path($classInfo['directory']);

			// Class file content
			$content = <<<PHP
			<?php

				namespace {$classInfo['namespace']};
				
				use App\Headers\Request;
				
				class {$classInfo['class']}
				{
					/**
					 * Handle an incoming request.
					 *
					 * @param  Request \$request
					 * @return mixed
					 */
					public function handle(Request \$request)
					{
						// Example pre-processing: authentication/validation
						if (!\$this->authorize(\$request)) {
							return redirect('/unauthorized', 403);
						}
				
						\$response = 'ok!';
				
						// Example post-processing: logging, response modification
						\$this->log(\$request, \$response);
				
						// Return True if success
						return true;
					}
				
					/**
					 * Perform authorization/validation logic.
					 */
					protected function authorize(Request \$request): bool
					{
						// Default: allow all requests
						return true;
					}
				
					/**
					 * Example logging or post-processing.
					 */
					protected function log(Request \$request, mixed \$response): void
					{
						// You could log request details here
					}
				}
			PHP;

			// Create the PHP file
			$this->create("{$classInfo['class']}.php", $content, $basePath);
		}
	}