<?php

	namespace Commands;

	use App\Console\Command;

	class StreamResources extends Command
	{
		protected string $signature = 'public:stream';
		protected string $description = 'Setup Stream-Wire resources. {path=libraries}';

		protected array $resources = [
			'scripts' => [
				// Morphdom
				'morphdom/morphdom.js'         => '/morphdom/morphdom.js',
				'morphdom/morphdom-esm.js'     => '/morphdom/morphdom-esm.js',
				'morphdom/morphdom-factory.js' => '/morphdom/morphdom-factory.js',
				'morphdom/morphdom-umd.js'     => '/morphdom/morphdom-umd.js',
				'morphdom/morphdom-umd.min.js' => '/morphdom/morphdom-umd.min.js',

				// Stream Wire JS
				'streamdom/stream-listener.js.php' => '/streamdom/stream-listener.js',
				'streamdom/stream-wire.js.php'     => '/streamdom/stream-wire.js',
				'streamdom/wire-directives.js.php' => '/streamdom/wire-directives.js',
				'streamdom/stream.js.php'          => '/streamdom/stream.js',
			],
			'styles' => [
				// Stream Wire CSS
				'streamdom/stream.css' => '/streamdom/stream.css',
			],
		];

		public function handle(string $path = 'libraries'): void
		{
			$basePath   = base_path("public/$path");
			$vendorPath = $this->getRealPath("resources");

			foreach ($this->resources as $type => $files) {
				foreach ($files as $vendorFile => $publicFile) {
					$source = $vendorPath . DIRECTORY_SEPARATOR . $vendorFile;
					$target = $basePath . $publicFile;

					// Normalize: .js.php → output as .js
					if (str_ends_with($vendorFile, '.js.php')) {
						$target = preg_replace('/\.js$/', '.js', $target);
					}

					$this->createDirectory(dirname($target));

					if (!file_exists($source)) {
						$this->error("⚠ Missing source file: {$source}");
						continue;
					}

					if (str_ends_with($vendorFile, '.js.php')) {
						// Evaluate PHP-wrapped JS
						$jsContent = $this->renderPhpFile($source, ['public_path' => $path]);
						if (file_put_contents($target, $jsContent) === false) {
							$this->error("❌ Failed to write JS file: {$target}");
							continue;
						}
					} else {
						if (!$this->moveFile($source, $target)) {
							$this->error("❌ Failed to copy file: {$source} → {$target}");
							continue;
						}
					}

					$this->info("✔ Deployed: {$source} → {$target}");
				}
			}

			$this->printNextSteps($path);
		}

		protected function renderPhpFile(string $file, array $extract): string
		{
			extract($extract, EXTR_SKIP);
			ob_start();
			include $file;
			return ob_get_clean();
		}

		protected function printNextSteps(string $path): void
		{
			echo PHP_EOL;
			echo "\033[32m✅ Resources streamed successfully! Next steps:\033[0m" . PHP_EOL . PHP_EOL;
			echo "  1. Add the following to your HTML:" . PHP_EOL;
			echo "       <script src=\"/$path/streamdom/stream.js\"></script>" . PHP_EOL;
			echo "       <link rel=\"stylesheet\" href=\"/$path/streamdom/stream.css\">" . PHP_EOL . PHP_EOL;
			echo "  2. Or in your CSS:" . PHP_EOL;
			echo "       @import \"/$path/streamdom/stream.css\";" . PHP_EOL . PHP_EOL;
			echo "  3. Register the route in your app:" . PHP_EOL;
			echo "       App\\Routes\\Route::post('/api/stream-wire/{identifier}', [App\\Utilities\\Stream::class, 'capture']);" . PHP_EOL . PHP_EOL;
			echo "\033[32m✔️ All set! You're ready to stream DOM resources! 🚀\033[0m" . PHP_EOL . PHP_EOL;
		}

		protected function getRealPath(string $path): string
		{
			$path = trim($path, '/');
			return realpath(__DIR__ . "/../$path");
		}
	}