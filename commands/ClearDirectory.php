<?php

	namespace Commands;
	
	use App\Console\Command;
	use FilesystemIterator;

	class ClearDirectory extends Command {
		
		protected string $signature = 'clear:directory';
		protected string $description = 'Clear the directory files and subdirectories.';
		
		public function handle(string $path = ''): void
		{
			if (!$path) {
				$this->error("Path is required.");
				return;
			}

			$targetPath = base_path("/$path");
			if (!is_dir($targetPath)) {
				$this->warn("Directory `$targetPath` does not exist or is not a directory.");
				return;
			}

			$this->deleteDirectory($targetPath);
			$this->info("Cleared contents of `$targetPath`.");
		}
	}