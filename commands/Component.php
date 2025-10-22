<?php

	namespace Commands;

	use App\Console\Command;

	class Component extends Command
	{
		protected string $signature = 'make:component';
		protected string $description = 'Creates a Stream-Wire component.';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Component class name is required.');
				return;
			}

			$this->info('⏳ Initializing component class file generation...');

			// Normalize and sanitize input
			$className = preg_replace('/[^A-Za-z0-9_\/]/', '', "components/$className");
			$directories = explode('/', $className);
			$className = ucfirst(array_pop($directories));

			// Define relative paths
			$relativeComponentDir = implode('/', $directories);
			$relativeViewDir = './views/' . implode('/', $directories);

			// Use base_path() to resolve absolute directories
			$componentDir = base_path($relativeComponentDir);
			$viewDir = base_path($relativeViewDir);

			// Namespace generation
			$namespaceParts = array_map('ucfirst', $directories);
			$namespace = !empty($namespaceParts)
				? 'namespace ' . implode('\\', $namespaceParts) . ';'
				: '';

			// File names
			$componentFilename = $className . '.php';
			$bladeFilename = strtolower($className) . '.blade.php';

			// Component class file content
			$componentContent = <<<PHP
            <?php

                {$namespace}

                use App\Utilities\Handler\Component;

                class {$className} extends Component
                {
                    /** @see {$relativeViewDir}/{$bladeFilename} */
                    public function render(): array
                    {
                        return \$this->compile();
                    }
                }
            PHP;

			// Blade template content
			$componentNamespace = implode('\\', $namespaceParts);
			$indexContent = <<<BLADE
            @php
                use {$componentNamespace}\\{$className};
                /**
                 * This file is rendered via the following component class:
                 * @see {$componentNamespace}\\{$className}::render()
                 */
            @endphp
            BLADE;

			// Create files using your existing `create()` helper
			$classCreated = $this->create($componentFilename, $componentContent, $componentDir);
			$viewCreated = $this->create($bladeFilename, $indexContent, $viewDir);

			// Output results
			if ($classCreated && $viewCreated) {
				$this->success("✅ Component '{$className}' successfully created!");
				$this->info("📄 Class: {$componentDir}/{$componentFilename}");
				$this->info("📄 View:  {$viewDir}/{$bladeFilename}");
			} else {
				$this->error("❌ Failed to create component files for '{$className}'.");
			}
		}
	}
