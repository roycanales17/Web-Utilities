<?php

	namespace Commands;

	use App\Console\Command;

	class Component extends Command
	{
		protected string $signature = 'make:component';
		protected string $description = 'Creates stream wire component';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Component class name is required.');
				return;
			}

			$this->info('⏳ Initializing component class file generation...');

			$className = preg_replace('/[^A-Za-z0-9_\/]/', '', "components/$className");
			$directories = explode('/', $className);
			$className = ucfirst($directories[count($directories) - 1]);

			array_pop($directories);
			$basePath = dirname('./') . "/" . implode('/', $directories) . "/" . $className;
			$namespaceDeclaration = "namespace ". implode('\\', array_map('ucfirst', array_merge($directories, [ $className ]))) . ";";

			$content = <<<PHP
			<?php
			
				{$namespaceDeclaration}
				
				use App\Utilities\Component;
				
				class {$className} extends Component
				{
					public function render(): string
					{
						// Renders the component's interface located at "$basePath/index.blade.php".
						// Pass any necessary data inside the compile() method as an associative array.
						return \$this->compile();
					}
				}
			PHP;

			// Create the PHP file
			if ($this->create("$className.php", $content, $basePath) && $this->create('index.blade.php', '', $basePath)) {
				$this->success("✅ Component class file '{$className}.php' has been successfully created at '{$basePath}' and is ready for use.");
			} else {
				$this->error("❌ Failed to create the file '{$className}.php' at '{$basePath}'.");
			}
		}
	}
