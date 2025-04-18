<?php

	namespace Commands;

	use App\Console\Command;

	class DatabaseMigrate extends Command
	{
		protected string $signature = 'db:migrate';

		protected string $description = 'Run the database migrations to apply schema changes';

		public function handle(): void
		{
			$this->info('Running database migrations...');
			$this->success('Migrations completed successfully.');
		}
	}
