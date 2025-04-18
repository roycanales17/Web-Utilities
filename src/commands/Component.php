<?php

	namespace Commands;

	use App\Console\Command;

	class Component extends Command
	{
		protected string $signature = 'make:component';
		protected string $description = 'Creates stream wire component';

		public function handle($className = ''): void
		{
			if (!$className) {
				$this->error('Component class name is required.');
				return;
			}

			$this->info('⏳ Initializing component class file generation...');

			$className = preg_replace('/[^A-Za-z0-9_]/', '', $className);
			$className = ucfirst($className);

			$filename = $className . '.php';
			$content = <<<HTML
			<?php

				namespace Components;
				
				use App\Utilities\Component;
				
				class {$className} extends Component {
				
					public function render(): string {
						return view('index');
					}
				}
			HTML;

			if ($this->create($filename, $content, dirname('./'). '/components')) {
				$this->success("✅ Component class file '{$filename}' has been successfully created and is ready for use.");
				return;
			}

			$this->error("❌ Failed to create the file '{$filename}'.");
		}
	}
