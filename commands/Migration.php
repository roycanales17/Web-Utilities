<?php

	namespace Commands;

	use App\Databases\Handler\Blueprints\Table;
	use App\Databases\Database;
	use App\Databases\Schema;
	use App\Console\Command;
	use Exception;

	class Migration extends Command
	{
		protected string $signature = 'migrate';
		protected string $description = 'Run all pending database migrations';

		public function handle(string $fileName = ''): void
		{
			if (!Schema::hasTable('migration')) {
				Schema::create('migration', function (Table $table) {
					$table->id();
					$table->string('migration');
					$table->timestamps();
				});
				$this->info("🧱 Created 'migration' table.");
			}

			$migrationPath = base_path('migrations');

			if (!is_dir($migrationPath)) {
				$this->error("❌ Migration directory not found: {$migrationPath}");
				return;
			}

			if ($fileName !== '') {
				$targetFile = rtrim($migrationPath . '/' . $fileName, '.php') . '.php';

				if (!file_exists($targetFile)) {
					$this->error("❌ Specified migration file not found: {$fileName}");
					return;
				}

				$migrationFiles = [$targetFile];
				$this->info("📄 Running single migration: {$fileName}");
			} else {
				$migrationFiles = glob($migrationPath . '/*.php');
			}

			if (empty($migrationFiles)) {
				$this->info("ℹ️ No migrations found.");
				return;
			}

			// Get list of applied migrations
			$applied = Database::table('migration')->select('migration')->col();

			foreach ($migrationFiles as $file) {
				$className = $this->getMigrationClassName($file);
				$migrationName = basename($file);

				// Skip if already migrated
				if (in_array($migrationName, $applied)) {
					$this->info("⏭️ Skipping already migrated: {$migrationName}");
					continue;
				}

				try {
					require_once $file;

					if (!class_exists($className)) {
						$this->error("❌ Class {$className} not found in {$file}");
						continue;
					}

					$migration = new $className;

					if (!method_exists($migration, 'up')) {
						$this->error("❌ Missing 'up()' method in {$className}");
						continue;
					}

					$migration->up();

					// Record migration
					Database::table('migration')->create([
						'migration' => $migrationName,
						'created_at' => date('Y-m-d H:i:s'),
						'updated_at' => date('Y-m-d H:i:s'),
					]);

					$this->success("✅ Migrated: {$className}");
				} catch (Exception $e) {
					$this->error("❌ Failed: {$file}");
					$this->error("   → " . $e->getMessage());
				}
			}

			$this->info("🎉 Migration process complete.");
		}

		/**
		 * Extract migration class name from filename.
		 * Example: 2025_10_26_000001_create_users_table.php → CreateUsersTable
		 */
		protected function getMigrationClassName(string $file): string
		{
			$base = basename($file, '.php');
			$parts = explode('_', $base);
			$words = array_slice($parts, 4);
			return str_replace(' ', '', ucwords(implode(' ', $words)));
		}
	}
