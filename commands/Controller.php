<?php

	namespace Commands;

	use App\Console\Command;

	class Controller extends Command
	{
		protected string $signature = 'make:controller';
		protected string $description = 'Generates a controller class.';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Controller class name is required.');
				return;
			}

			$this->info('⏳ Initializing controller class file generation...');

			// Use extractClassInfo helper
			$classInfo = $this->extractClassInfo($className, 'handler/Controller');

			// File name
			$controllerFilename = $classInfo['class'] . '.php';
			$basePath = base_path('/' . $classInfo['directory']);

			// Namespace declaration
			$namespaceDeclaration = $classInfo['namespace'] ? "namespace {$classInfo['namespace']};" : '';

			// Controller content
			$content = <<<PHP
			<?php

				{$namespaceDeclaration}

				use App\Http\Controller;

				final class {$classInfo['class']} extends Controller
				{
					public function index()
					{
						//
					}

					public function edit()
					{
						//
					}

					public function delete()
					{
						//
					}
				}
			PHP;

			// Create the file
			$this->create($controllerFilename, $content, $basePath);
		}
	}