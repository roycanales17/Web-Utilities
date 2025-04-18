<?php

	namespace Commands;

	use App\Console\Command;

	class Database extends Command
	{
		protected string $signature = 'db';
		protected string $description = 'Start a new database CLI session';

		public function handle($className = ''): void
		{
			$this->info('Running database CLI session...');
			$this->success('Successfully running database CLI session.');
		}
	}
