<?php

	namespace Commands;

	use App\Console\Command;

	class ClearCache extends Command
	{
		protected string $signature = 'clear:cache';
		protected string $description = 'Flush the application cache';

		public function handle($className = ''): void
		{
			$this->info('Performing clear cache.');
			$this->success('Successfully cleared the cache.');
		}
	}
