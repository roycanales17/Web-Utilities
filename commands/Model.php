<?php

	namespace Commands;

	use App\Console\Command;

	class Model extends Command
	{
		protected string $signature = 'make:model';
		protected string $description = 'Generates a model class.';

		public function handle(string $className = ''): void
		{
			if (empty($className)) {
				$this->error('Model class name is required.');
				return;
			}

			$this->info('⏳ Initializing model class file generation...');

			// Extract class info and handle directories/namespaces
			$classInfo = $this->extractClassInfo($className, 'handler/Model');
			$className = $classInfo['class'];
			$basePath = base_path($classInfo['directory']);
			$namespace = $classInfo['namespace'];

			$table = strtolower($className);

			$content = <<<PHP
			<?php

				namespace {$namespace};

				use App\Databases\Facade\Model;

				class {$className} extends Model
				{
					/** @var string Primary key of the table */
					public string \$primary_key = 'id';

					/** @var string Table name */
					public string \$table = '{$table}';

					/** @var array Fillable attributes */
					public array \$fillable = [];
				}
			PHP;

			$this->create("$className.php", $content, $basePath);
		}
	}