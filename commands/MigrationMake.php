<?php

	namespace Commands;

	use App\Console\Command;

	class MigrationMake extends Command
	{
		protected string $signature = 'make:migration';
		protected string $description = 'Create a new migration file';

		public function handle(string $className = ''): void
		{
			if (!$className) {
				$this->error("❌ Migration name is required.");
				return;
			}

			$timestamp = date('Y_m_d_His');
			$filename = "{$timestamp}_{$className}.php";
			$path = base_path('migrations/' . $filename);

			// Convert name to class name (snake_case → PascalCase)
			$className = str_replace(' ', '', ucwords(str_replace('_', ' ', $className)));

			$stub = <<<PHP
            <?php

                use App\Databases\Schema;
                use App\Databases\Handler\Blueprints\Table;

                class {$className}
                {
                    public function up(): void
                    {
                        // TODO: Implement table creation here
                    }

                    public function down(): void
                    {
                        // TODO: Implement rollback here
                    }
                }
            PHP;

			if (file_put_contents($path, $stub)) {
				$this->success("✅ Migration created: {$filename}");
			} else {
				$this->error("❌ Failed to create migration file.");
			}
		}
	}
