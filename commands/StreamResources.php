<?php

	namespace Commands;

	use App\Console\Command;

	class StreamResources extends Command
	{
		protected string $signature = 'public:stream';
		protected string $description = 'Setup stream wire resources. {path=libraries}';

		protected array $resources = [
			'scripts' => [
				// morphdom
				'morphdom/morphdom.js'          => '/morphdom/morphdom.js',
				'morphdom/morphdom-esm.js'      => '/morphdom/morphdom-esm.js',
				'morphdom/morphdom-factory.js'  => '/morphdom/morphdom-factory.js',
				'morphdom/morphdom-umd.js'      => '/morphdom/morphdom-umd.js',
				'morphdom/morphdom-umd.min.js'  => '/morphdom/morphdom-umd.min.js',

				// stream wire js
				'streamdom/stream-listener.js.php'  => '/streamdom/stream-listener.js',
				'streamdom/stream-wire.js.php'      => '/streamdom/stream-wire.js',
				'streamdom/wire-directives.js.php'  => '/streamdom/wire-directives.js',
				'streamdom/stream.js.php'           => '/streamdom/stream.js',
			],
			'styles' => [
				// stream wire css
				'streamdom/stream.css' => '/streamdom/stream.css',
			]
		];

		public function handle(string $path = 'libraries'): void
		{
			$basePath   = $this->public_path(trim($path, '/'));
			$vendorPath = $this->base_path('resources');

			foreach ($this->resources as $type => $files) {
				foreach ($files as $vendorFile => $publicFile) {
					$source = $vendorPath . DIRECTORY_SEPARATOR . $vendorFile;
					$target = $basePath . $publicFile;

					// normalize: .js.php → output as .js
					if (str_ends_with($vendorFile, '.js.php')) {
						$target = preg_replace('/\.js$/', '.js', $target);
					}

					$this->ensureDirectory(dirname($target));

					if (file_exists($source)) {
						if (str_ends_with($vendorFile, '.js.php')) {
							// Evaluate PHP-wrapped JS with $public_path available
							$jsContent = $this->renderPhpFile($source, [
								'public_path' => $path
							]);
							file_put_contents($target, $jsContent);
						} else {
							copy($source, $target);
						}

						$this->info("✔ Deployed: {$source} → {$target}");
					} else {
						$this->error("⚠ Missing: {$source}");
					}
				}
			}

			$this->success("✅ All resources streamed successfully.");
		}

		protected function public_path(string $path = ''): string
		{
			$base = "./public/";
			return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
		}

		protected function base_path(string $path = ''): string
		{
			$base = realpath(dirname(__DIR__));
			return rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR);
		}

		protected function ensureDirectory(string $path): void
		{
			if (!is_dir($path)) {
				mkdir($path, 0777, true);
			}
		}

		protected function renderPhpFile(string $file, array $extract): string
		{
			extract($extract, EXTR_SKIP);
			ob_start();
			include $file;
			return ob_get_clean();
		}
	}