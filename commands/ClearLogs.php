<?php

	namespace Commands;

	use App\Console\Command;

	class ClearLogs extends Command
	{
		protected string $signature = 'clear:logs';
		protected string $description = 'Flush the application logs.';

		public function handle(bool $force = false): void
		{
			if (!$force) {
				$confirm = $this->confirm("Are you sure you want to clear logs?");
				if (!$confirm) {
					$this->info("Clearing logs is cancelled \n");
					return;
				}
			}

			$this->info('Clearing application logs...');
			$this->deleteDirectory(base_path('/storage/private/logs'));
			$this->info('Cleared application logs...');
		}
	}
