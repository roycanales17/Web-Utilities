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
			$classInfo = $this->extractClassInfo($className, 'components');

			// File names
			$componentFilename = $classInfo['class'] . '.php';
			$bladeFilename = lcfirst($classInfo['class']) . '.blade.php';

			// Hint path
			$relativeViewDir = $this->getDefaultDirectoryView() . strtolower($classInfo['directory']) . "/stream";
			$viewDir = base_path($relativeViewDir);

			// Component class file content
			$componentContent = <<<PHP
			<?php
			
				namespace {$classInfo['namespace']};
				
				use App\Utilities\Handler\Component;
				
				class {$classInfo['class']} extends Component
				{
					/** @see {$relativeViewDir}/{$bladeFilename} */
					public function render(): array
					{
						return \$this->compile();
					}
				}
			PHP;

			// Blade template content
			$indexContent = <<<BLADE
            @php
                use {$classInfo['buildNamespace']};
                /**
                 * This file is rendered via the following component class:
                 * @see {$classInfo['buildNamespace']}::render()
                 */
            @endphp
            
            BLADE;

			// Create files using your existing `create()` helper
			$this->create($componentFilename, $componentContent, $classInfo['directory']);
			$this->create($bladeFilename, $indexContent, $viewDir);
		}
	}
