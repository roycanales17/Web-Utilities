<?php

    namespace Commands;

    use App\Console\Command;
    use App\Databases\Database;
    use App\Databases\Schema;
    use Exception;

    class MigrationRollback extends Command
    {
        protected string $signature = 'migrate:rollback';
        protected string $description = 'Rollback all executed database migrations';

        public function handle(): void
        {
            $this->info("⏪ Executing MigrationRollback...");

            // Ensure migration table exists
            if (!Schema::hasTable('migration')) {
                $this->error("❌ No 'migration' table found. Nothing to rollback.");
                return;
            }

            // Fetch all applied migrations (latest first)
            $migrations = Database::table('migration')
                ->select('id', 'migration', 'created_at')
                ->orderBy('id', 'DESC')
                ->fetch();

            if (empty($migrations)) {
                $this->info("ℹ️ No migrations have been applied.");
                return;
            }

            foreach ($migrations as $record) {
                $file = base_path('database/' . $record['migration']);
                if (!file_exists($file)) {
                    $this->error("⚠️ Migration file missing: {$file}");
                    continue;
                }

                require_once $file;
                $className = $this->getMigrationClassName($file);

                if (!class_exists($className)) {
                    $this->error("❌ Class {$className} not found in {$file}");
                    continue;
                }

                $migration = new $className;

                if (!method_exists($migration, 'down')) {
                    $this->error("❌ Missing 'down()' method in {$className}");
                    continue;
                }

                try {
                    $migration->down();

                    // Remove the record from migration table
                    Database::table('migration')->where('id', $record['id'])->delete();

                    $this->success("✅ Rolled back: {$className}");
                } catch (Exception $e) {
                    $this->error("❌ Failed to rollback {$className}: " . $e->getMessage());
                }
            }

            $this->info("🎉 Rollback process completed.");
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
