<?php

	namespace Commands;

	use App\Console\Command;

	class MigrationMake extends Command
	{
		protected string $signature = 'make:migration';
		protected string $description = 'Create a new migration file';

		public function handle(string $migrationName = ''): void
		{
			if (empty($migrationName)) {
				$this->error("Migration name is required.");
				return;
			}

			// Normalize migration name to class name (PascalCase)
			$classInfo = $this->extractClassInfo($migrationName, 'migrations');
			$className = $classInfo['class'];

			// File name with timestamp
			$timestamp = date('Y_m_d_His');
			$filename = "{$timestamp}_{$className}.php";
			$basePath = base_path($classInfo['directory']);

			$content = <<<PHP
			<?php

				use App\Databases\Schema;
				use App\Databases\Handler\Blueprints\Table;

				final class {$className}
				{
					/**
					 * Apply the migration
					 */
					public function up(): void
					{
						// TODO: Implement table creation or alteration here
					}

					/**
					 * Reverse the migration
					 */
					public function down(): void
					{
						// TODO: Implement rollback logic here
					}
				}
			PHP;

			$this->create($filename, $content, $basePath);
		}
	}