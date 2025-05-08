<?php

	namespace App\Utilities;

	use App\Database\DB;
	use App\Headers\Request;
	use Closure;
	use Error;
	use Exception;

	class Application
	{
		private mixed $exception = null;
		private string $preferredIDE = 'phpstorm';
		private float $startTime;
		private float $endTime;
		private int $startMemory;
		private int $endMemory;

		public static function run(Closure $callback, string $summary = 'app_summary', string $configPath = '../app/Config.php', string $envPath = '../.env'): self {
			return new self($callback, $summary, $configPath, $envPath);
		}

		function __construct(Closure $callback, string $summary, string $configPath, string $envPath) {
			try {
				$this->startTracking();
				if (!file_exists($configPath))
					throw new Exception("Configuration file is required. ". empty($configPath) ? '' : "path: `$configPath`");

				Request::capture();
				Config::load($envPath);

				$conf = require($configPath);
				foreach ($conf['defines'] ?? [] as $key => $value) {
					if (!defined($key))
						define($key, $value);
				}

				$databases = $conf['database'] ?? [];
				if ($databases) {
					$default = $databases['default'] ?? '';
					$connections = $databases['connections'] ?? [];

					if ($connections[$default] ?? false)
						DB::configure($connections[$default]);
				}

				Stream::load($conf['stream'] ?? '');
				Session::configure($conf['session'] ?? []);
				Session::start();

				define('CSRF_TOKEN', csrf_token());
				foreach ($conf['preload_files'] ?? [] as $path) {
					if (file_exists($path)) {
						require_once $path;
					} else {
						$path = trim($path, '/');
						$path = config('APP_ROOT') . "/$path";
						if (file_exists($path))
							require_once $path;
					}
				}

				validate_token();
				$callback($conf);
			} catch (Exception|Error $e) {
				$this->exception = $e;
				$this->throw();
			} finally {
				$this->endTracking();
				if (is_null($this->exception) && $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET[$summary])) {
					$this->summary();
				}
			}
		}

		public function failed(Closure $callback): void {
			http_response_code(500);
			if ($this->exception !== null) {
				$callback($this->exception);
			}
		}

		private function startTracking(): void {
			$this->startTime = microtime(true);
			$this->startMemory = memory_get_usage();
		}

		private function endTracking(): void {
			$this->endTime = microtime(true);
			$this->endMemory = memory_get_usage();
		}

		private function throw(): void {

			$exception = $this->exception;
			$file = urlencode($exception->getFile());
			$line = $exception->getLine();

			// Define editor URL schemes
			$editorUrls = [
				'phpstorm' => "phpstorm://open?file=$file&line=$line",
				'vscode' => "vscode://file/$file:$line",
				'sublime' => "subl://open?file=$file&line=$line"
			];

			$selectedUrl = $editorUrls[$this->preferredIDE ?? 'phpstorm'] ?? $editorUrls['vscode'];

			echo '<div style="font-family: Arial, sans-serif; background-color: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;">';
			echo '<h2 style="color: #721c24;">Exception Details</h2>';
			echo '<hr style="border-color: #f5c6cb;">';
			echo '<table style="width: 100%; border-collapse: collapse;">';

			$sanitizedMessage = preg_replace(
				'/ in .*? on line \d+/',
				'',
				$exception->getMessage()
			);

			$table = [
				'Exception Type:' => strtoupper(get_class($this)),
				'Message:' => $exception->getMessage(),
				'File:' => $exception->getFile(),
				'Line:' => $exception->getLine(),
				'Error Code:' => $exception->getCode(),
				'Previous Exception:' => ($exception->getPrevious() ? $exception->getPrevious()->getMessage() : 'None'),
			];

			foreach ($table as $label => $value) {
				echo '<tr>';
				echo '<td style="padding: 8px; border: 1px solid #f5c6cb; background-color: #f8d7da;"><strong>' . $label . '</strong></td>';
				echo '<td style="padding: 8px; border: 1px solid #f5c6cb; background-color: #f8d7da;">' . $value . '</td>';
				echo '</tr>';
			}

			echo '</table>';
			echo '<h3 style="color: #721c24;">Stack Trace</h3>';
			echo '<pre style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 5px; color: #333;">' . $exception->getTraceAsString() . '</pre>';

			echo '<h3 style="color: black;">Possible Related Issues</h3>';
			echo '<div style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 5px; color: #333;">';
			echo '<div id="error-loader_" style="width: 100%;text-align: center;">Loading...</div>';
			echo '<ul id="error-solution-links" style="padding-left: 15px;font-size: 13px;margin: 0"></ul>';
			echo '</div>';

			echo '<a href="' . $selectedUrl . '" style="display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #721c24; color: #fff; text-decoration: none; border-radius: 5px;">Navigate Error</a>';
			echo '</div>';

			?>
			<script>
				async function searchStackOverflow(query) {
					const loader = document.getElementById('error-loader_');
					const listElement = document.getElementById('error-solution-links');
					const apiUrl = 'https://api.stackexchange.com/2.3/search/advanced';
					const params = new URLSearchParams({
						order: 'desc',
						sort: 'relevance',
						q: query,
						site: 'stackoverflow',
						pagesize: 10
					});

					try {
						const response = await fetch(`${apiUrl}?${params.toString()}`);

						if (!response.ok) {
							loader.style.display = 'No Entry Found.';
							throw new Error('Failed to fetch Stack Overflow data.');
						}

						loader.style.display = 'none';
						const data = await response.json();

						if (!listElement) {
							console.error('Element with id "error-solution-links" not found.');
							return;
						}

						listElement.innerHTML = '';

						if (data.items && data.items.length > 0) {
							data.items.forEach(item => {
								const listItem = document.createElement('li');
								const link = document.createElement('a');
								link.href = item.link;
								link.textContent = item.title;
								link.target = '_blank';
								link.setAttribute('style', 'text-decoration: none; color: #333;');
								listItem.style.marginTop = '5px';
								listItem.appendChild(link);
								listElement.appendChild(listItem);
							});
						} else {
							listElement.innerHTML = '<li>No results found.</li>';
						}
					} catch (error) {
						loader.style.display = 'none';
						listElement.innerHTML = '<li>Something went wrong.</li>';
						console.error('Error:', error.message);
					}
				}

				searchStackOverflow(<?= json_encode($sanitizedMessage) ?>);
			</script>
			<?php
		}

		private function summary(): void {
			$executionTime = $this->endTime - $this->startTime;
			$memoryUsage = $this->endMemory - $this->startMemory;
			$peakMemory = memory_get_peak_usage();

			echo '<div style="font-family: Arial, sans-serif; background-color: #d4edda; color: #155724; padding: 20px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px;">';
			echo '<h2 style="color: #155724;">Application Performance Summary</h2>';
			echo '<hr style="border-color: #c3e6cb;">';
			echo '<table style="width: 100%; border-collapse: collapse;">';

			$performanceData = [
				'Execution Time:' => number_format($executionTime, 6) . ' seconds',
				'Memory Usage:' => number_format($memoryUsage / 1024, 2) . ' KB',
				'Peak Memory Usage:' => number_format($peakMemory / 1024, 2) . ' KB',
			];

			foreach ($performanceData as $label => $value) {
				echo '<tr>';
				echo '<td style="padding: 8px; border: 1px solid #c3e6cb; background-color: #d4edda;"><strong>' . $label . '</strong></td>';
				echo '<td style="padding: 8px; border: 1px solid #c3e6cb; background-color: #d4edda;">' . $value . '</td>';
				echo '</tr>';
			}

			echo '</table>';
			echo '</div>';
		}
	}