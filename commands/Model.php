<?php

	namespace Commands;

	use App\Console\Command;

	class Model extends Command
	{
		protected string $signature = 'make:model';
		protected string $description = 'Generates a new model class';

		public function handle(string $className = ''): void
		{
			if (!$className) {
				$this->error('Component class name is required.');
				return;
			}

			$this->info('⏳ Initializing model class file generation...');

			$className = preg_replace('/[^A-Za-z0-9_]/', '', $className);
			$className = ucfirst($className);

			$filename = $className . '.php';
			$content = <<<HTML
			<?php

				namespace Http\Models;
				
				use Illuminate\Databases\Model;
				
				class {$className} extends Model {
				
					public string \$primary_key = 'id';
					public string \$table = '{$className}';
					public array \$fillable = [];
				}
			HTML;

			if ($this->create($filename, $content, dirname('./'). '/Http/Models')) {
				$this->success("✅ Model class file '{$filename}' has been successfully created and is ready for use.");
				return;
			}

			$this->error("❌ Failed to create the file '{$filename}'.");
		}
	}
