<?php

	namespace Commands;

	use App\Console\Command;

	class Serve extends Command
	{
		protected string $signature = 'serve';
		protected string $description = 'Serve the application out of maintenance mode';

		public function handle($className = ''): void
		{
			$this->info('Running the application...');
			$this->success('Successfully live.');
		}
	}
