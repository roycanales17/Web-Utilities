<?php

	use App\Headers\Request;
	use App\Console\Terminal;
	use App\Utilities\Config;
	use App\Utilities\Session;
	use App\View\Compilers\Blade;
	use App\utilities\Handler\StreamHandler;

	/**
	 * Retrieves a session value by key.
	 *
	 * @param string $key
	 * @return mixed
	 */
	function session(string $key): mixed
	{
		return Session::get($key);
	}

	/**
	 * Retrieves a configuration value by key.
	 *
	 * @param string $key     The configuration key or constant name.
	 * @param mixed|null $default The default value to return if the key is not found.
	 * @return mixed
	 */
	function config(string $key, mixed $default = null): mixed
	{
		if (Config::isEmpty()) {
			Config::load('.env');
		}

		return Config::get($key, $default);
	}

	/**
	 * Returns a CSRF token stored in the session.
	 *
	 * @return string
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
	function encrypt(string $string): string {
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
	function decrypt(string $encoded): string {
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
	 * @param array  $data Data to be extracted and passed into the view.
	 * @return string Rendered HTML content.
	 */
	function view(string $path, array $data = []): string
	{
		ob_start();

		$normalizedPath = preg_replace('/\.php$/', '', trim(str_replace('.', '/', $path), '/'));

		$mainPath = "../views/{$normalizedPath}.php";
		$bladePath = "../views/{$normalizedPath}.blade.php";

		if (file_exists($bladePath)) {
			$mainPath = $bladePath;
		}

		Blade::load($mainPath, $data);
		return ob_get_clean();
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
		if (config('DEVELOPMENT')) {
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
		if (php_sapi_name() === 'cli')
			return;

		if (!in_array(Request::method(), ['GET', 'HEAD', 'OPTIONS']) && request()->header('X-CSRF-TOKEN') !== Session::get('csrf_token'))
			exit(response(['message' => 'Bad Request'], 400)->json());
	}

	/**
	 * Limit a string to a given length, appending an ending if truncated.
	 *
	 * @param string $value The input string
	 * @param int $limit Maximum length allowed
	 * @param string $end String to append if truncated (default '...')
	 * @return string The limited string
	 */
	function str_limit(string $value, int $limit = 100, string $end = '...'): string {
		if (mb_strlen($value) <= $limit) {
			return $value;
		}

		return rtrim(mb_substr($value, 0, $limit)) . $end;
	}

	/**
	 * Compiles a Blade view and returns the output.
	 *
	 * Used to render both normal views and "stream" components.
	 *
	 * @param string $path Path to the blade view.
	 * @param array $data Variables passed to the view.
	 * @param bool $asynchronous Whether the stream is asynchronous.
	 * @return StreamHandler Rendered HTML output.
	 */
	function stream(string $path, array $data = [], bool $asynchronous = false): StreamHandler
	{
		return new StreamHandler($path, $data, $asynchronous);
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
		$prefix = config('COOKIE_PREFIX', 'custom');
		return cookie("$prefix:$name", $value, $expire);
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
		$prefix = config('COOKIE_PREFIX', 'custom');
		return cookie("$prefix:$name") ?? $default;
	}

	/**
	 * Delete a cookie with a default 'custom:' prefix in its name.
	 *
	 * @param string $name Cookie base name
	 * @return mixed Result of cookie() function
	 */
	function deleteCookie(string $name): mixed
	{
		$prefix = config('COOKIE_PREFIX', 'custom');
		return cookie("$prefix:$name", null, -1);
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
		$key = config('APP_COOKIE_PASSWORD', '123');
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