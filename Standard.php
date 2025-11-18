<?php

	use App\Utilities\Redirect;
	use App\Utilities\Response;
	use App\Utilities\Url;
	use App\Utilities\Auth;
	use App\Utilities\Request;
	use App\Utilities\Config;
	use App\Utilities\Server;
	use App\Console\Terminal;
	use App\Utilities\Session;
	use App\View\Compilers\Blade;
	use App\Utilities\Environment;
	use App\View\Compilers\Component;
	use App\Utilities\Handler\StreamHandler;
	use App\Bootstrap\Exceptions\StreamException;

	/**
	 * Generates a URI for a named route with optional parameter replacements.
	 *
	 * Replaces placeholders (e.g., `{id}`) in the route URI with values from $params.
	 * If the named route is not found, returns `'/'` as fallback.
	 *
	 * @param string $name           The name of the route.
	 * @param array<string, mixed> $params Associative array of parameters to substitute in the URI.
	 * @return string                The generated URI or `'/'` if the route was not found.
	 */
	function route(string $name, array $params = []): string
	{
		return App\Routes\Route::link($name, $params);
	}

	/**
	 * Retrieves a session value by key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	function session(string $key): mixed
	{
		return Session::get($key, Session::flash($key));
	}

	/**
	 * Retrieves a configuration value by key.
	 *
	 * @param string $key The configuration key or constant name.
	 * @return mixed
	 */
	function config(string $key): mixed
	{
		return Config::get($key);
	}

	/**
	 * Returns a CSRF token stored in the session.
	 *
	 * @return string
	 * @throws Exception
	 */
	function csrf_token(): string
	{
		Session::start();

		if (!Session::has('csrf_token'))
			Session::set('csrf_token', bin2hex(random_bytes(32)));

		return Session::get('csrf_token');
	}

	/**
	 * Encrypts a string by encoding each character with a prefix.
	 *
	 * @param string $string The plain text to encrypt.
	 * @return string The encrypted string format.
	 */
	function encrypt(string $string): string
	{
		$numbers = [];

		foreach (mb_str_split($string) as $char) {
			$ord = ord($char);
			if (ctype_lower($char)) {
				$numbers[] = 'L' . ($ord - ord('a') + 5);
			} elseif (ctype_upper($char)) {
				$numbers[] = 'U' . ($ord - ord('A') + 5);
			} elseif (ctype_digit($char)) {
				$numbers[] = 'D' . $char;
			} else {
				// Escape non-alphanumerics using hex
				$numbers[] = 'X' . bin2hex($char);
			}
		}

		return implode('-', $numbers);
	}

	/**
	 * Decrypts a string that was encrypted with `encrypt()`.
	 *
	 * @param string $encoded The encoded string to decode.
	 * @return string The original plain text.
	 */
	function decrypt(string $encoded): string
	{
		$parts = explode('-', $encoded);
		$decoded = '';

		foreach ($parts as $part) {
			$prefix = $part[0];
			$data = substr($part, 1);

			if ($prefix === 'L') {
				$decoded .= chr((int)$data + ord('a') - 5);
			} elseif ($prefix === 'U') {
				$decoded .= chr((int)$data + ord('A') - 5);
			} elseif ($prefix === 'D') {
				$decoded .= $data;
			} elseif ($prefix === 'X') {
				$decoded .= hex2bin($data);
			}
		}

		return $decoded;
	}

	/**
	 * Launches a CLI session using the internal Terminal class.
	 *
	 * @param array  $args The CLI arguments passed in (e.g., from $_SERVER['argv']).
	 * @param string $path Optional command config path.
	 * @param string $root Optional root directory.
	 * @return void
	 */
	function launch_cli_session(array $args, string $path = '', string $root = ''): void
	{
		if ($path)
			Terminal::config($path, $root);

		Terminal::config('commands', __DIR__);
		Terminal::capture($args);
	}

	/**
	 * Renders a PHP or Blade view file and returns the rendered content.
	 *
	 * @param string $path View path using dot notation (e.g., 'users.profile').
	 * @param array $data Data to be extracted and passed into the view.
	 * @param string $directory Directory for
	 * @return string Rendered HTML content.
	 */
	function view(string $path, array $data = [], string $directory = 'views'): string
	{
		$compiled = function() use ($path, $data, $directory) {
			$normalizedPath = preg_replace('/\.php$/', '', trim(str_replace('.', '/', $path), '/'));
			$bladePath = base_path("/$directory/{$normalizedPath}.blade.php");
			return Blade::load($bladePath, $data);
		};

		return Component::renderComponents($compiled());
	}

	/**
	 * Outputs variable contents (formatted), only in development mode.
	 * Optionally halts script execution.
	 *
	 * @param mixed $data Data to display (array, object, string, etc.).
	 * @param bool  $exit Whether to call `exit()` after dumping.
	 * @return void
	 */
	function dump(mixed $data, bool $exit = false): void
	{
		if (get_constant('DEVELOPMENT', true)) {
			$printed = print_r($data, true);
			echo <<<HTML
				<pre> 
					$printed
				</pre>
			HTML;
		}

		if ($exit) exit;
	}

	/**
	 * Validates CSRF token for requests other than GET, HEAD, or OPTIONS.
	 *
	 * Responds with HTTP 400 and JSON message on failure.
	 *
	 * @return void
	 */
	function validate_token(): void
	{
		$request = new Request();
		$method = $request->method();

		if (!in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
			$token = Session::get('csrf_token');
			$requestToken = $request->header('X-CSRF-TOKEN') ?? $request->input('csrf-token');

			if (!$requestToken) {
				$requestToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
			}

			if ($requestToken !== $token) {
				$message = get_constant('DEVELOPMENT', true) ? 'Invalid token' : 'Bad Request';

				console_log("CSRF token: %s", [$message]);
				exit(response(['message' => $message], 400)->json());
			}
		}
	}

	/**
	 * Limit a string to a given length, appending an ending if truncated.
	 *
	 * @param string $value The input string
	 * @param int $limit Maximum length allowed
	 * @param string $end String to append if truncated (default '...')
	 * @return string The limited string
	 */
	function str_limit(string $value, int $limit = 100, string $end = '...'): string
	{
		if (mb_strlen($value) <= $limit) {
			return $value;
		}

		return rtrim(mb_substr($value, 0, $limit)) . $end;
	}

	/**
	 * Used to render both normal views and "stream" components.
	 *
	 * @param string|null $class Class to invoke
	 * @param array $constructParams Parameters to pass to the class constructor (if needed).
	 * @param bool $asynchronous Whether the stream should run asynchronously.
	 * @return StreamHandler            Rendered HTML output or Stream actions methods for blade content.
	 * @throws StreamException
	 */
	function stream(null|string $class = null, array $constructParams = [], bool $asynchronous = false): StreamHandler
	{
		$stream = new StreamHandler($class, $constructParams, $asynchronous);

		if (get_constant('DEVELOPMENT', true) && get_constant('STREAM_DEBUG', true)) {
			$trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			$context = [];
			$resolvedPath = Session::flash('__resolved_path__');
			$context = array_merge($context, [
				'file' => $resolvedPath ?: $trace[0]['file'],
				'line' => $trace[0]['line'],
			]);
			$context['params'] = json_encode($constructParams);
			$context['asynchronous'] = $asynchronous;
			$stream->trace(context: $context);
		}

		return $stream;
	}

	/**
	 * Create a cookie with a default 'custom:' prefix in its name.
	 *
	 * @param string $name Cookie base name
	 * @param mixed $value Value to set in cookie
	 * @param int $expire Expiration time in seconds
	 * @return mixed Result of cookie() function
	 */
	function createCookie(string $name, mixed $value = null, int $expire = 3600): mixed
	{
		return cookie(env('COOKIE_PREFIX', 'custom').":$name", $value, $expire);
	}

	/**
	 * Fetch a cookie with a default 'custom:' prefix in its name.
	 * Returns $default if cookie is not found.
	 *
	 * @param string $name Cookie base name
	 * @param mixed $default Default value if cookie not found
	 * @return mixed Cookie value or default
	 */
	function fetchCookie(string $name, mixed $default = false): mixed
	{
		return cookie(env('COOKIE_PREFIX', 'custom').":$name") ?? $default;
	}

	/**
	 * Delete a cookie with a default 'custom:' prefix in its name.
	 *
	 * @param string $name Cookie base name
	 * @return mixed Result of cookie() function
	 */
	function deleteCookie(string $name): mixed
	{
		return cookie(env('COOKIE_PREFIX', 'custom').":$name", null, -1);
	}

	/**
	 * Set, get, or delete encrypted cookies.
	 *
	 * - If $value is null and $expire is 0, returns the decrypted cookie value.
	 * - If $value is null and $expire is negative, deletes the cookie.
	 * - Otherwise, sets an encrypted cookie with the given value and expiration.
	 *
	 * Encryption uses AES-256-CBC with a key from config('APP_COOKIE_PASSWORD').
	 *
	 * @param string $name Cookie name
	 * @param mixed $value Value to set, or null to get/delete
	 * @param int $expire Expiration time in seconds; 0 for session, negative to delete
	 * @param string $path Cookie path
	 * @param string $domain Cookie domain
	 * @param bool $secure Secure flag (HTTPS only)
	 * @param bool $httponly HttpOnly flag
	 * @return mixed Decrypted cookie value on get, true on set/delete, or null if not found
	 */
	function cookie(string $name, $value = null, $expire = 0, $path = '/', $domain = '', $secure = false, $httponly = true): mixed
	{
		$key = env('APP_COOKIE_PASSWORD', 'f2dg23asd3141saf');
		$cipher = 'AES-256-CBC';

		$encrypt = function ($data) use ($cipher, $key) {
			$iv = random_bytes(openssl_cipher_iv_length($cipher));
			$json = json_encode($data); // Safely handle arrays, ints, strings, etc.
			$encrypted = openssl_encrypt($json, $cipher, $key, 0, $iv);
			return base64_encode($iv . $encrypted);
		};

		$decrypt = function ($data) use ($cipher, $key) {
			$decoded = base64_decode($data);
			if (!$decoded) return null;

			$ivlen = openssl_cipher_iv_length($cipher);
			$iv = substr($decoded, 0, $ivlen);
			$encrypted = substr($decoded, $ivlen);
			$decrypted = openssl_decrypt($encrypted, $cipher, $key, 0, $iv);
			return json_decode($decrypted, true); // Decoded back to array/int/etc.
		};

		// GET
		if ($value === null && $expire === 0) {
			return isset($_COOKIE[$name]) ? $decrypt($_COOKIE[$name]) : null;
		}

		// REMOVE
		if ($value === null && $expire < 0) {
			setcookie($name, '', time() - 3600, $path, $domain, $secure, $httponly);
			unset($_COOKIE[$name]);
			return true;
		}

		// SET
		$encrypted = $encrypt($value);
		setcookie($name, $encrypted, $expire > 0 ? time() + $expire : 0, $path, $domain, $secure, $httponly);
		$_COOKIE[$name] = $encrypted;

		return true;
	}

	/**
	 * Get the base path of the project.
	 *
	 * @param string $path Optional subpath to append.
	 * @return string
	 */
	function base_path(string $path = ''): string
	{
		$base = dirname(realpath(__DIR__ . '/../../'));
		return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, DIRECTORY_SEPARATOR) : $base;
	}

	/**
	 * Retrieve an old input value from the session flash data.
	 *
	 * This is typically used to repopulate form inputs after validation
	 * errors or failed submissions.
	 *
	 * Example usage in Blade:
	 * ```blade
	 * <input type="email" name="email" value="{{ input('email') }}">
	 * ```
	 *
	 * @param string $name  The input field name.
	 * @return mixed|string The flashed input value or an empty string if not set.
	 */
	function old(string $name): mixed
	{
		return Session::flash("input:$name") ?: '';
	}

	/**
	 * Retrieve a specific error message from the session flash data.
	 *
	 * Supports both field-specific and general error messages.
	 * If `$name` is `"message"`, it retrieves the general `message:error` key.
	 *
	 * Example usage in Blade:
	 * ```blade
	 * <span class="text-red-500 text-sm">{{ error('email') }}</span>
	 * <div>{{ error('message') }}</div>
	 * ```
	 *
	 * @param string $name  The error key or `"message"` for general errors.
	 * @return mixed|string The flashed error message or an empty string if not set.
	 */
	function error(string $name): mixed
	{
		if ($name == 'message') {
			$message = Session::flash("message:error");
			if ($message) {
				return $message;
			}
		}

		return Session::flash("error:$name") ?: '';
	}

	/**
	 * Retrieve a success message from the session flash data.
	 *
	 * Supports both field-specific and general success messages.
	 * If `$name` is `"message"`, it retrieves the general `message:success` key.
	 *
	 * Example usage in Blade:
	 * ```blade
	 * <div class="text-green-500">{{ success('message') }}</div>
	 * ```
	 *
	 * @param string $name  The message key or `"message"` for general success messages.
	 * @return mixed|string The flashed success message or an empty string if not set.
	 */
	function success(string $name): mixed
	{
		if ($name == 'message') {
			$message = Session::flash("message:success");
			if ($message) {
				return $message;
			}
		}

		return Session::flash("success:$name") ?: '';
	}


	/**
	 * Generate a full URL for a given asset using the Server::makeURL helper.
	 *
	 * @param string $path  The relative path to the asset.
	 * @return string       The fully-qualified asset URL.
	 */
	function asset(string $path): string
	{
		static $cache = [];

		$path = ltrim($path, '/');
		$file = base_path("public/$path");

		if (!isset($cache[$path])) {
			if (file_exists($file)) {
				$cache[$path] = filemtime($file);
			} else {
				$cache[$path] = env('APP_VERSION', '1.0');
			}
		}

		return Server::makeURL($path, ['v' => $cache[$path]]);
	}

	/**
	 * Retrieve a value from the .env file.
	 *
	 * Automatically loads the .env file
	 * through Config if not already loaded.
	 *
	 * Example:
	 * ```php
	 * $appName = env('APP_NAME', 'MyApp');
	 * $debug   = env('APP_DEBUG', false);
	 * ```
	 *
	 * @param string $key      The environment variable name.
	 * @param mixed  $default  The default value if not found.
	 * @return mixed
	 */
	function env(string $key, mixed $default = null): mixed
	{
		if (Environment::isEmpty()) {
			Environment::load('.env');
		}

		$value = Environment::get($key, $default);

		// Return default if not found
		if ($value === null) {
			return $default;
		}

		// Normalize common literal values
		return $value;
	}

	/**
	 * Retrieves the value of a defined PHP constant by name.
	 *
	 * This helper checks whether a constant with the given key is defined.
	 * If the constant exists, its value is returned. Otherwise, the specified
	 * default value is returned instead.
	 *
	 * Example:
	 * ```php
	 * define('APP_VERSION', '1.0.0');
	 * echo get_constant('APP_VERSION'); // Outputs: 1.0.0
	 * echo get_constant('NON_EXISTENT', 'N/A'); // Outputs: N/A
	 * ```
	 *
	 * @param string $key      The name of the constant to retrieve.
	 * @param mixed  $default  The fallback value to return if the constant is not defined.
	 *
	 * @return mixed The value of the defined constant, or the default if not defined.
	 */
	function get_constant(string $key, mixed $default = null): mixed {
		if (defined($key)) {
			return constant($key);
		}

		return $default;
	}

	/**
	 * Helper function to quickly get an instance of Url.
	 *
	 * Usage:
	 *   url()->to('dashboard');
	 *   url()->route('home', ['id' => 1]);
	 *
	 * @return Url
	 */
	function url(): Url {
		return new Url();
	}

	/**
	 * Writes a formatted message to STDOUT with optional sprintf-style replacements.
	 *
	 * Features:
	 * - Accepts mixed message types (string, array, object, etc.)
	 * - Converts arrays and objects to readable strings
	 * - Supports vsprintf() formatting when $values are provided
	 * - Supports bold text using Markdown syntax: **text**
	 * - Automatically timestamps each log entry
	 * - Writes output to php://stdout for CLI and Docker compatibility
	 *
	 * Bold Syntax:
	 *     **this will be bold**
	 *
	 * Example:
	 *     console_log("Hello **%s**!", ["World"]);
	 *     // Output: Hello (bold)World(bold)
	 *
	 * @param mixed $message  The message to log. Strings can include **bold** markers.
	 * @param array $values   Optional values for vsprintf formatting.
	 *
	 * @return void
	 */
	function console_log(mixed $message, array $values = []): void
	{
		if (php_sapi_name() === 'cli') {
			return;
		}

		$datetime = date('Y-m-d H:i:s');
		$bold = "\033[1m";
		$reset = "\033[0m";

		if (is_array($message) || is_object($message)) {
			$message = print_r($message, true);
		}

		if (is_string($message) && !empty($values)) {
			$message = vsprintf($message, $values);
		}

		if (is_string($message)) {
			$message = preg_replace(
				'/\*\*(.*?)\*\*/',
				$bold . '$1' . $reset,
				$message
			);
		}

		$log = "ℹ️ [{$datetime}] {$message}";
		file_put_contents('php://stdout', $log . PHP_EOL);
	}

	/**
	 * Check if a user is currently authenticated.
	 * This is a simple wrapper for `App\Http\Auth::check()`.
	 *
	 * @return bool True if the user is logged in, false otherwise.
	 */
	function auth(): bool {
		return Auth::check();
	}

	function response(mixed $content = '', int $status = 200, array $headers = []): Response
	{
		return new Response($content, $status, $headers);
	}

	function redirect(string $url, int $status = 302, array $headers = []): Redirect
	{
		return response(status: $status, headers: $headers)->redirect($url);
	}