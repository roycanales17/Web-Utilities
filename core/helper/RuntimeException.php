<?php

	namespace App\Bootstrap\Helper;

	use App\Utilities\Logger;
	use App\Utilities\Server;
	use App\Utilities\Mail;
	use App\Utilities\Request;
	use ReflectionException;
	use ReflectionFunction;
	use Throwable;
	use Closure;

	/**
	 * @internal
	 *
	 * Central exception handler for the framework.
	 * Supports:
	 *  - registering custom report callbacks
	 *  - skipping reporting for specific exception types
	 *  - duplicate-exception suppression
	 *  - automatic logging and error mailing
	 *  - pretty developer exception screen
	 *  - AJAX-safe structured JSON exception responses
	 *
	 * Not intended for public consumption by the project user.
	 */
	final class RuntimeException
	{
		/**
		 * @var array<string, Closure>
		 */
		protected array $reportCallbacks = [];

		/**
		 * @var array<int, string>  List of exception class names to skip.
		 */
		protected array $dontReport = [];

		/**
		 * @var bool
		 */
		protected bool $suppressDuplicates = false;

		/**
		 * @var array<string>
		 */
		protected static array $reportedHashes = [];

		/**
		 * Register a callback for a specific exception type.
		 *
		 * The closure must have a first parameter type-hinted with the exception.
		 *
		 * @throws ReflectionException
		 */
		public function report(Closure $closure): void
		{
			$ref = new ReflectionFunction($closure);
			$params = $ref->getParameters();

			if (isset($params[0]) && $type = $params[0]->getType()) {
				$this->reportCallbacks[$type->getName()] = $closure;
			}
		}

		/**
		 * Render a view file while passing the exception into it.
		 */
		public function view(string $path, array $extract = []): void
		{
			if (file_exists($path)) {
				view($path, array_merge($extract, ['exception' => $this]));
			}
		}

		/**
		 * Enable duplicate exception suppression.
		 */
		public function dontReportDuplicates(): void
		{
			$this->suppressDuplicates = true;
		}

		/**
		 * Append exceptions to skip from reporting.
		 *
		 * @param array<string> $exclude
		 */
		public function dontReport(array $exclude = []): void
		{
			$this->dontReport = array_merge($this->dontReport, $exclude);
		}

		/**
		 * Main exception processor.
		 */
		public function handle(Throwable $e): void
		{
			http_response_code($e->getCode() ?: 500);

			$class  = get_class($e);
			$ticker = strtoupper(substr(dechex(crc32(uniqid('', true))), 0, 8));

			//
			// 1. Skip if in don't-report list
			//
			foreach ($this->dontReport as $excluded) {
				if ($e instanceof $excluded) {
					return;
				}
			}

			//
			// 2. Handle duplicate suppression
			//
			if ($this->suppressDuplicates) {
				$hash = md5($class . $e->getMessage() . $e->getFile() . $e->getLine());

				if (in_array($hash, self::$reportedHashes, true)) {
					return;
				}

				self::$reportedHashes[] = $hash;
			}

			//
			// 3. Developer console output
			//
			console_log("\n=============\n> EXCEPTION <\n=============");
			console_log("Ticker: %s", [$ticker]);
			console_log("Message: %s", [strip_tags($e->getMessage())]);
			console_log("File: %s", [$e->getFile()]);
			console_log("Line: %s", [$e->getLine()]);

			//
			// 4. Dispatch to a custom registered handler
			//
			foreach ($this->reportCallbacks as $type => $callback) {
				if ($e instanceof $type) {
					echo ($callback($e));
					return;
				}
			}

			//
			// 5. Exception-defined report()
			//
			if (method_exists($e, 'report')) {
				echo ($e->report());
				exit();
			}

			//
			// 6. Default logging
			//
			$logger  = new Logger('error.log');
			$context = [];

			if (!get_constant('CLI_MODE', false)) {
				// Email error (if class exists)
				if (class_exists('\Handler\Mails\ErrorReportMail')) {
					Mail::mail(new \Handler\Mails\ErrorReportMail($e, $ticker));
				}

				$context = [
					'ticker'        => $ticker,
					'url'           => Server::requestURI(),
					'method'        => Server::requestMethod(),
					'ip'            => Server::IPAddress(),
					'host'          => Server::hostName(),
					'protocol'      => Server::protocol(),
					'secure'        => Server::isSecureConnection(),
					'is_ajax'       => Server::isAjaxRequest(),
					'request_id'    => Server::requestId(),
					'response_code' => http_response_code(),
					'request_time'  => sprintf("%s [%s]", Server::requestTime(), date('Y-m-d H:i:s', Server::requestTime())),
					'client_port'   => Server::clientPort(),
					'server_ip'     => Server::serverIPAddress(),
					'referer'       => Server::referer(),
					'content_type'  => Server::contentType(),
					'session_id'    => session_id() ?: null,
					'user_id'       => $_SESSION['user_id'] ?? null,
					'get'           => $_GET ?? [],
					'post'          => $_POST ?? [],
					'query'         => Server::queryString(),
					'raw_body'      => file_get_contents('php://input'),
					'accept'        => Server::accept(),
					'user_agent'    => Server::userAgent(),
				];
			}

			$logger->error("[TICKER: $ticker] " . strip_tags($e->getMessage()), [
				'exception' => strtoupper($class),
				'file'      => $e->getFile(),
				'line'      => $e->getLine(),
				'trace'     => $e->getTraceAsString(),
				'context'   => $context,
			]);

			//
			// 7. Development exception output
			//
			if (get_constant('DEVELOPMENT', true)) {
				$this->renderDeveloperException($e, $class, $ticker);
				return;
			}

			//
			// 8. Production error page
			//
			if (!file_exists(base_path($errorPath = "views/errors/exception.blade.php"))) {
				die("Missing exception view. Create: {$errorPath}");
			}

			echo view($errorPath, [
				'email'  => env('APP_EMAIL', 'support@test.com'),
				'ticker' => $ticker,
			]);
		}

		/**
		 * Render the full developer debug exception page.
		 */
		private function renderDeveloperException(Throwable $e, string $class, string $ticker): void
		{
			$file = urlencode($e->getFile());
			$line = $e->getLine();

			$editorUrls = [
				'phpstorm' => "phpstorm://open?file=$file&line=$line",
				'vscode'   => "vscode://file/$file:$line",
				'sublime'  => "subl://open?file=$file&line=$line",
			];

			$selectedUrl = $editorUrls[$e->preferredIDE ?? 'phpstorm'] ?? $editorUrls['vscode'];

			$table = [
				'Ticker:'             => $ticker,
				'Exception Type:'     => strtoupper($class),
				'Message:'            => $e->getMessage(),
				'File:'               => $e->getFile(),
				'Line:'               => $e->getLine(),
				'Error Code:'         => $e->getCode(),
				'Previous Exception:' => $e->getPrevious()?->getMessage() ?? 'None',
			];

			//
			// CLI output
			//
			if (get_constant('CLI_MODE', false)) {
				echo "\n\033[41;37m $class \033[0m\n\n";
				foreach ($table as $label => $value) {
					echo "\033[33m$label\033[0m $value\n";
				}
				echo "\n\033[31m--- Stack Trace ---\033[0m\n";
				echo $e->getTraceAsString() . "\n";
				return;
			}

			//
			// AJAX response
			//
			if (Server::isAjaxRequest()) {
				$this->renderJsonException($e, $ticker);
				return;
			}

			//
			// Browser output
			//
			$this->renderPrettyHtmlException($e, $selectedUrl, $table);
		}

		/**
		 * JSON error response for AJAX requests.
		 */
		private function renderJsonException(Throwable $e, string $ticker): void
		{
			$req = new Request();

			$trace = array_map(fn($t) => [
				'file'     => $t['file'] ?? '',
				'line'     => $t['line'] ?? '',
				'class'    => $t['class'] ?? '',
				'function' => $t['function'] ?? '',
			], $e->getTrace());

			$response = [
				'status' => 'error',
				'ticker' => $ticker,
				'error'  => [
					'type'    => strtoupper(get_class($e)),
					'message' => $e->getMessage(),
					'code'    => $e->getCode(),
					'file'    => $e->getFile(),
					'line'    => $e->getLine(),
					'trace'   => $trace,
				],
			];

			$headers = [
				'Cache-Control' => 'no-cache, no-store, must-revalidate',
				'Pragma'        => 'no-cache',
				'Expires'       => '0',
				'X-Request-ID'  => Server::RequestId() ?: uniqid('req_', true),
				'X-Ticker-ID'   => $ticker,
			];

			echo $req->response($response, 500, $headers)->json();
		}

		/**
		 * Pretty HTML exception renderer.
		 */
		private function renderPrettyHtmlException(Throwable $e, string $editorUrl, array $table): void
		{
			echo '<div style="font-family: Arial, sans-serif; background-color: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px;">';
			echo '<h2 style="color: #721c24;">Exception Details</h2>';
			echo '<hr style="border-color: #f5c6cb;">';
			echo '<table style="width: 100%; border-collapse: collapse;">';

			foreach ($table as $label => $value) {
				echo '<tr>';
				echo '<td style="padding: 8px; border: 1px solid #f5c6cb; background-color: #f8d7da;"><strong>' . $label . '</strong></td>';
				echo '<td style="padding: 8px; border: 1px solid #f5c6cb; background-color: #f8d7da;">' . $value . '</td>';
				echo '</tr>';
			}

			echo '</table>';
			echo '<h3 style="color: #721c24;">Stack Trace</h3>';
			echo '<pre style="background-color: #f5f5f5; padding: 10px; border: 1px solid #ddd; border-radius: 5px; color: #333;">' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
			echo '<a href="' . $editorUrl . '" style="display: inline-block; margin-top: 20px; padding: 10px 15px; background-color: #721c24; color: #fff; text-decoration: none; border-radius: 5px;">Navigate Error</a>';
			echo '</div>';
		}
	}