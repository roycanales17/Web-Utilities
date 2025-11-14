<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Bootstrap\Exceptions\AppException;
	use App\Headers\Request;
	use App\Routes\Route;
	use App\Utilities\Config;
	use App\Utilities\Handler\Bootloader;
	use Exception;

	final class Routes extends Bootloader
	{
		public function handler(): void {
			$routes = Config::get('Routes');

			foreach ([false, true] as $validate) {
				foreach ($routes as $route) {
					if ($this->isCli() && $validate) {
						continue;
					}

					$routeFiles = $route['routes'] ?? ['web.php'];
//					$routeFilesStr = implode(', ', $routeFiles);
//
//					if ($validate) {
//						console_log("Validating route files: {$routeFilesStr}");
//					}

					$routeObject = Route::configure(
						root: base_path('/routes'),
						routes: $routeFiles,
						prefix: $route['prefix'] ?? '',
						domain: $route['domain'] ?? env('APP_URL', 'localhost'),
						middleware: $route['middleware'] ?? [],
						validate: $validate
					);

					if (!$this->isCli()) {
						$resolved = Request::header('X-STREAM-WIRE')
							? $routeObject->captured(fn($content) => print($content))
							: $routeObject->captured($route['captured'] ?? null);

						if ($resolved) {
							break 2;
						}
					}
				}
			}

			if (!$this->isCli() && !($resolved ?? false)) {
				if (file_exists(base_path($emptyPagePath = "/views/errors/404.blade.php"))) {
					echo view('errors/404');
				} else {
					throw new AppException("Missing 404 page. Please create the file at: {$emptyPagePath}");
				}
				ob_end_flush();
			}
		}
	}