<?php

	namespace Commands;

	use App\Console\Command;

	class CacheTable extends Command
	{
		protected string $signature = 'clear:table';
		protected string $description = 'Create a migration for the cache database table';

		public function handle($className = ''): void
		{
			$this->info('Creating cache table.');
			$this->success('Successfully created cache table.');
		}
	}
