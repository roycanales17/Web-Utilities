<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Bootstrap\Exceptions\AppException;
	use App\Utilities\Handler\Bootloader;
	use App\Utilities\Config;
	use App\Routes\Route;
	use Exception;

	final class Routes extends Bootloader
	{
		/**
		 * @throws AppException
		 * @throws Exception
		 */
		public function handler(): void {
			$routes = Config::get('Routes');

			// Run first the default routes
			if (!$this->isCli()) {
				$routeObject = Route::configure(
					root: $this->getRealPath('/resources/routes'),
					routes: ['DefaultRoutes.php'],
					validate: true
				);

				$resolved = $routeObject->captured(fn($content) => print($content));
				if ($resolved) {
					console_log("Route resolved!");
					return;
				}
			}

			// Then the application routes
			foreach ([false, true] as $validate) {
				foreach ($routes as $route) {
					if ($this->isCli() && $validate) {
						continue;
					}

					$routeFiles = $route['routes'] ?? ['web.php'];
					$routeObject = Route::configure(
						root: base_path('/routes'),
						routes: $routeFiles,
						prefix: $route['prefix'] ?? '',
						domain: $route['domain'] ?? '',
						middleware: $route['middleware'] ?? [],
						validate: $validate
					);

					if (!$this->isCli()) {
						$resolved = $routeObject->captured(fn($content) => print($content));
						if ($resolved) {
							console_log("Route resolved!");
							break 2;
						}
					}
				}
			}

			// If no routes found
			if (!$this->isCli() && !($resolved ?? false)) {
				if (file_exists(base_path($emptyPagePath = "/views/errors/404.blade.php"))) {
					echo view('errors/404');
				} else {
					throw new AppException("Missing 404 page. Please create the file at: {$emptyPagePath}");
				}

				console_log("Route not found");
				ob_end_flush();
			}
		}

		protected function getRealPath(string $path): string {
			$path = trim($path, '/');
			return realpath(__DIR__ . "/../$path");
		}
	}