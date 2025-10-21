<?php

	namespace Commands;

	use App\Console\Command;

	class Component extends Command
	{
		protected string $signature = 'make:component';
		protected string $description = 'Creates stream wire component.';

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
			$fakePath = "/" . implode('/', $directories) . "/" . $className;
			$basePath = base_path($fakePath);
			$namespaceDeclaration = "namespace ". implode('\\', array_map('ucfirst', array_merge($directories, [ $className ]))) . ";";

			$content = <<<PHP
			<?php

				{$namespaceDeclaration}

				use App\Utilities\Handler\Component;

				class {$className} extends Component
				{
					 /** @see .$fakePath/index.blade.php */
					public function render(): array
					{
						return \$this->compile();
					}
				}
			PHP;

			$componentPath = implode('\\', array_map('ucfirst', array_merge($directories, [ $className ])));
			$indexContent = <<<PHP
			@php
				use {$componentPath}\\{$className};
				/**
				 * This file is rendered via the following component class:
				 *
				 * @see {$componentPath}\\{$className}::render()
				 */
			@endphp
			PHP;

			// Create the PHP file
			if ($this->create("$className.php", $content, $basePath) && $this->create('index.blade.php', $indexContent, $basePath)) {
				$this->success("✅ Component class file '{$className}.php' has been successfully created at '{$basePath}' and is ready for use.");
			} else {
				$this->error("❌ Failed to create the file '{$className}.php' at '{$basePath}'.");
			}
		}
	}
