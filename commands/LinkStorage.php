<?php

	namespace Commands;

	use App\Console\Command;

	class LinkStorage extends Command
	{
		protected string $signature = 'storage:link';
		protected string $description = 'Create symbolic link from storage/public to public/build/storage and prepare storage folders';

		public function handle(): void
		{
			$projectRoot = $this->findProjectRoot();
			$publicBuildPath = $projectRoot . '/public/build';
			$storageBasePath = $projectRoot . '/storage';
			$storageLink = $publicBuildPath . '/storage';

			// 1. Create storage/{public,protect,private} and add .htaccess
			$folders = [
				'public' => <<<HTACCESS
Options +Indexes
HTACCESS,
				'protect' => <<<HTACCESS
Options -Indexes

<FilesMatch ".*">
  Require all granted
</FilesMatch>
HTACCESS,
				'private' => <<<HTACCESS
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

<IfModule !mod_authz_core.c>
  Order allow,deny
  Deny from all
</IfModule>
HTACCESS,
			];

			foreach ($folders as $folder => $htaccessContent) {
				$folderPath = "$storageBasePath/$folder";

				// Create directory if not exist
				if (!is_dir($folderPath)) {
					mkdir($folderPath, 0777, true);
					$this->info("Created directory: $folderPath");
				}

				// Create or update .htaccess
				$htaccessPath = "$folderPath/.htaccess";
				if (!file_exists($htaccessPath) || file_get_contents($htaccessPath) !== $htaccessContent) {
					file_put_contents($htaccessPath, $htaccessContent);
					$this->info("Written .htaccess in: $folderPath");
				} else {
					$this->info(".htaccess already up to date: $folderPath");
				}
			}

			// 2. Ensure public/build exists
			if (!is_dir($publicBuildPath)) {
				mkdir($publicBuildPath, 0777, true);
				$this->info("Created directory: $publicBuildPath");
			}

			// 3. Create symbolic link from storage/public → public/build/storage
			if (!is_link($storageLink)) {
				$storagePublicPath = $storageBasePath . '/public';

				if (!is_dir($storagePublicPath)) {
					$this->error("Source directory does not exist: $storagePublicPath");
				} else {
					if (symlink($storagePublicPath, $storageLink)) {
						$this->success("Symbolic link created: $storageLink → $storagePublicPath");
					} else {
						$this->error("Failed to create symbolic link.");
					}
				}
			} else {
				$this->warn("Symbolic link already exists: $storageLink");
			}
		}

		private function findProjectRoot(): string
		{
			$dir = __DIR__;
			while (!file_exists($dir . '/vendor') && dirname($dir) !== $dir) {
				$dir = dirname($dir);
			}
			return $dir;
		}
	}