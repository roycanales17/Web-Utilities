<?php

	use App\Content\Blade;
	use App\Headers\Request;
	use App\Utilities\Config;
	use App\Utilities\Session;
	use App\Console\Terminal;

	/**
	 * Retrieves a configuration value by key.
	 *
	 * @param string $key     The configuration key or constant name.
	 * @param mixed|null $default The default value to return if the key is not found.
	 * @return mixed
	 */
	function config(string $key, mixed $default = null): mixed
	{
		if (defined($key))
			return constant($key);

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
	 * Compiles a Blade view and returns the output.
	 *
	 * Used to render both normal views and "stream" components (like Livewire).
	 *
	 * @param string $path         Path to the blade view.
	 * @param array  $data         Variables passed to the view.
	 * @param bool $asynchronous Whether the stream is asynchronous.
	 * @return string Rendered HTML output.
	 */
	function stream(string $path, array $data = [], bool $asynchronous = false): string
	{
		return Blade::compile(App\Utilities\Stream::render($path, $data, $asynchronous));
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

		$root = config('APP_ROOT');
		$root = rtrim($root, '/');

		$path = str_replace('.', '/', $path);
		$normalizedPath = preg_replace('/\.php$/', '', trim($path, '/'));
		$bladePath = str_replace('.php', '.blade.php', $mainPath = "/views/{$normalizedPath}.php");

		if (file_exists($root . $bladePath))
			$mainPath = $bladePath;

		Blade::render($mainPath, extract: $data, onError: function ($trace) {
			throw new Exception("{$trace['message']} in `{$trace['path']}`, line: `{$trace['line']}`", $trace['code']);
		});

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