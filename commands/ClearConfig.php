<?php

	namespace Commands;
	
	use App\Console\Command;

	class ClearConfig extends Command {
		
		protected string $signature = 'clear:config';
		protected string $description = 'Reset the configuration file';
		
		public function handle(bool $force = false): void
		{
			if (!$force) {
				$confirm = $this->confirm("Are you sure you want to clear the config file?");
				if (!$confirm) {
					$this->info("Clearing config file is cancelled \n");
					return;
				}
			}

			$root = dirname('./') . "/app/";
			$filename = 'Config.php';

			if (file_exists($root.$filename)) {
				$this->info('Clearing configuration file...');
				$config = <<<PHP
				<?php

				return [
				
					/*
					|--------------------------------------------------------------------------
					| Stream Wire Path
					|--------------------------------------------------------------------------
					|
					| Specifies the path to locate view files used for Stream Wire components.
					| This allows rendering components via file path instead of class names.
					|
					*/
					'stream' => ['../views/'],
				
					/*
					|--------------------------------------------------------------------------
					| Caching Configuration
					|--------------------------------------------------------------------------
					|
					| Configuration options for connecting to a Memcache server.
					| Adjust the 'enabled' flag to toggle caching features.
					|
					*/
					'cache' => [
						'server' => config('MEMCACHE_SERVER_NAME', 'localhost'),
						'port' => config('MEMCACHE_PORT', '11211')
					],
				
					/*
					|--------------------------------------------------------------------------
					| Route Configuration
					|--------------------------------------------------------------------------
					|
					| Define route-specific settings and content capturing behaviors for both
					| web and API routes. These handlers can be used for templating or raw output.
					|
					*/
					'routes' => [
				
						/*
						|--------------------------------------------------------------------------
						| Default Web Routes Configuration
						|--------------------------------------------------------------------------
						|
						| Handles rendering of content responses. If the HTTP status code is 404,
						| the capture will be skipped. Otherwise, content will be injected into
						| the specified Blade template.
						|
						*/
						'web' => [
							'captured' => function (string \$content, int \$code) {
								if (\$code == 404) return;
				
								App\Content\Blade::render('public/index.html', extract: [
									'g_page_lang' => config('APP_LANGUAGE'),
									'g_page_title' => config('APP_NAME'),
									'g_page_url' => config('APP_URL'),
									'g_page_description' => "Page description here",
									'g_page_content' => \$content
								]);
							}
						],
				
						/*
						|--------------------------------------------------------------------------
						| API Routes Configuration
						|--------------------------------------------------------------------------
						|
						| Handles raw output of captured API content.
						|
						*/
						'api' => [
							'routes' => ['api.php'],
							'prefix' => 'api',
							'captured' => function (string \$content) {
								echo(\$content);
							}
						]
					]
				];
				PHP;

				unlink($root.$filename);
				$this->create($filename, $config, $root);
				$this->success("Successfully cleared configuration file.");
			} else {
				$this->warn("Couldn't find configuration file.");
			}
		}
	}