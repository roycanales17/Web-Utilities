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
				$this->deleteDirectory($path);
				$this->success('Application logs cleared successfully.');
			} else {
				$this->warn('No log directory found to clear.');
			}
		}

		private function deleteDirectory(string $dir): void
		{
			$items = array_diff(scandir($dir), ['.', '..']);
			foreach ($items as $item) {
				$itemPath = "$dir/$item";
				if (is_dir($itemPath)) {
					$this->deleteDirectory($itemPath);
				} else {
					if (unlink($itemPath)) {
						$this->info("Deleted file: $itemPath");
					} else {
						$this->warn("Failed to delete file: $itemPath");
					}
				}
			}

			if (rmdir($dir)) {
				$this->info("Deleted directory: $dir");
			} else {
				$this->warn("Failed to delete directory: $dir");
			}
		}
	}
