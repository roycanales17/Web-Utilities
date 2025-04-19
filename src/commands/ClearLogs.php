<?php

	namespace Commands;

	use App\Console\Command;

	class ClearLogs extends Command
	{
		protected string $signature = 'clear:logs';
		protected string $description = 'Flush the application logs';

		public function handle($className = ''): void
		{
			$this->info('Clearing application logs...');

			$path = dirname('./') . '/logs';

			if (is_dir($path)) {
				rmdir($path);
				$this->success('Application logs cleared successfully.');
			} else {
				$this->warn('No log directory found to clear.');
			}
		}
	}
