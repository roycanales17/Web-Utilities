<?php

	namespace Commands;

	use App\Console\Command;

	class Migrate extends Command
	{
		protected string $signature = 'migrate';
		protected string $description = 'Run the database migrations';

		public function handle($className = ''): void
		{
			$this->info('Running the migrations...');
			$this->success('Successfully migrated.');
		}
	}
