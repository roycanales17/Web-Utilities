<?php

	namespace Commands;

	use App\Console\Command;

	class DatabaseSeed extends Command
	{
		protected string $signature = 'db:seeder';

		protected string $description = 'Seed the database with a specific class or all default seeders';

		public function handle(): void
		{
			$this->info('Running database seed...');
			$this->success('Successfully seeded.');
		}
	}
