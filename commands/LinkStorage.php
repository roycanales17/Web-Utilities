<?php

	namespace Commands;

	use App\Console\Command;
	use App\Utilities\Server;

	class LinkStorage extends Command
	{
		protected string $signature = 'storage:link';
		protected string $description = 'Create a symbolic link from storage/public to public/storage.';

		public function handle(): void
		{
			$storagePublicPath = base_path('storage/public');
			$publicStorageLink = base_path('public/storage');

			// 1. Ensure storage/public exists
			if (!$this->createDirectory($storagePublicPath)) {
				return;
			}

			// 2. Create symbolic link if it doesn’t exist
			if (!$this->createSymlink($storagePublicPath, $publicStorageLink)) {
				return;
			}

			// 3. Optional message for clarity
			$this->info('');
			$this->info('You can now access public storage files via:');
			$this->info('→ ' . Server::makeURL('storage'));
		}
	}