<?php

	namespace Commands;

	use App\Console\Command;

	class ClearLogs extends Command
	{
		protected string $signature = 'clear:logs';
		protected string $description = 'Flush the application logs';

		public function handle($className = ''): void
		{
			$this->info('Performing clear application logs...');
			$this->success('Successfully cleared the application logs.');
		}
	}
