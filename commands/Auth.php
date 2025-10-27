<?php

	namespace Commands;

	use App\Console\Command;

	class Auth extends Command
	{
		protected string $signature = 'make:auth';
		protected string $description = 'Scaffold authentication resources into the project.';

		public function handle(): void
		{
			$mainResources = realpath(__DIR__ . '/../resources/commands/auth');

			if (!$mainResources) {
				$this->error('Auth resources not found.');
				return;
			}

			// Project directories
			$targets = [
				'mails'      => base_path('/mails'),
				'views'      => base_path('/views'),
				'http'       => base_path('/http'),
				'migrations' => base_path('/migrations'),
				'routes'     => base_path('/routes'),
			];

			$this->info('Preparing to move Auth resources...');
			$conflicts = [];

			// Check for conflicts
			foreach ($targets as $dir => $targetPath) {
				$sourceDir = $mainResources . '/' . $dir;
				if (!is_dir($sourceDir)) continue;

				$iterator = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS)
				);

				foreach ($iterator as $file) {
					$relativePath = substr($file->getPathname(), strlen($sourceDir) + 1);
					$destinationPath = $targetPath . '/' . $relativePath;

					if (file_exists($destinationPath)) {
						$conflicts[] = $destinationPath;
					}
				}
			}

			if (!empty($conflicts)) {
				$this->info('The following files already exist:');
				foreach ($conflicts as $conflict) {
					$this->info(" - $conflict");
				}

				$confirm = $this->confirm('Some files already exist. Do you want to overwrite them?');
				if (!$confirm) {
					$this->info('Operation cancelled.');
					return;
				}
			} else {
				$this->info('No conflicts found.');
				$confirm = $this->confirm('Proceed with moving Auth resources?');
				if (!$confirm) {
					$this->info('Operation cancelled.');
					return;
				}
			}

			// Copy files
			foreach ($targets as $dir => $targetPath) {
				$sourceDir = $mainResources . '/' . $dir;
				if (!is_dir($sourceDir)) continue;

				$this->copyDirectory($sourceDir, $targetPath, $dir === 'migrations');
			}

			$this->success('Auth resources successfully copied to project directories.');

			// ✅ Prompt to setup Routes.php
			$this->ensureAuthRouteExists();
		}

		/**
		 * Ensure that app/Routes.php includes the Authentication Routes setup.
		 */
		protected function ensureAuthRouteExists(): void
		{
			$routesFile = base_path('/app/Routes.php');

			if (!file_exists($routesFile)) {
				$this->error('Routes.php file not found at app/Routes.php.');
				return;
			}

			$content = file_get_contents($routesFile);
			if (strpos($content, "'auth' => [") !== false) {
				$this->info('✅ Authentication route configuration already exists in app/Routes.php.');
				return;
			}

			$this->warn("\n⚠️  Your app/Routes.php file does not include the Authentication Routes setup.");
			$append = $this->confirm('Would you like to append the authentication routes configuration automatically?');

			if (!$append) {
				$this->info("Please manually add the following to your app/Routes.php:\n");
				$this->line($this->getAuthRoutesSnippet());
				return;
			}

			// Try to append safely before the closing bracket if possible
			$pattern = '/(\];\s*)$/';
			if (preg_match($pattern, $content)) {
				$newContent = preg_replace($pattern, $this->getAuthRoutesSnippet() . "\n];", $content);
				file_put_contents($routesFile, $newContent);
				$this->success('✅ Authentication routes successfully appended to app/Routes.php.');
			} else {
				// fallback: just append to the end
				file_put_contents($routesFile, "\n\n" . $this->getAuthRoutesSnippet(), FILE_APPEND);
				$this->success('✅ Authentication routes appended to the end of app/Routes.php.');
			}
		}

		/**
		 * Returns the authentication route configuration snippet.
		 */
		protected function getAuthRoutesSnippet(): string
		{
			return <<<PHP

    // Authentication Routes
    'auth' => [
        'routes' => ['auth.php'],
        'captured' => function (string \$content) {
            echo(view('auth.layout', [
                'g_page_lang' => config('APP_LANGUAGE'),
                'g_page_title' => config('APP_NAME'),
                'g_page_url' => config('APP_URL'),
                'g_page_description' => "Page description here",
                'g_page_content' => \$content
            ]));
        }
    ],
PHP;
		}

		/**
		 * Recursively copy a directory and its files.
		 */
		protected function copyDirectory(string $src, string $dest, bool $renameMigration = false): void
		{
			if (!is_dir($src)) return;
			if (!is_dir($dest)) mkdir($dest, 0777, true);

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::SELF_FIRST
			);

			$srcLen = strlen(rtrim($src, DIRECTORY_SEPARATOR)) + 1;
			$timestamp = date('Y_m_d_His');

			foreach ($iterator as $item) {
				$fullPath = $item->getPathname();
				$relativePath = substr($fullPath, $srcLen);
				$targetPath = $dest . DIRECTORY_SEPARATOR . $relativePath;

				if ($renameMigration && $item->isFile()) {
					$filename = basename($relativePath);
					if (preg_match('/^\d{4}_\d{2}_\d{2}_\d{6}_(.*)\.php$/', $filename, $matches)) {
						$targetPath = $dest . DIRECTORY_SEPARATOR . "{$timestamp}_{$matches[1]}.php";
					}
				}

				if ($item->isDir()) {
					if (!is_dir($targetPath)) mkdir($targetPath, 0777, true);
				} else {
					$parent = dirname($targetPath);
					if (!is_dir($parent)) mkdir($parent, 0777, true);
					copy($fullPath, $targetPath);
				}
			}
		}
	}