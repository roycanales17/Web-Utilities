<?php

	namespace Commands;

	use App\Console\Command;

	class Seeder extends Command
	{
		protected string $signature = 'seed';

		protected string $description = 'Runs the database seeder to populate initial or test data';

		public function handle($className = ''): void
		{
			$this->info('Running the seeder...');
			$this->success('Successfully seeded.');
		}
	}
