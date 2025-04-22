<?php

	use App\Content\Blade;
	use App\Headers\Request;
	use App\Utilities\Config;
	use App\Utilities\Session;
	use App\Console\Terminal;

	function config(string $key, $default = null): mixed
	{
		if (defined($key))
			return constant($key);

		return Config::get($key, $default);
	}

	function csrf_token(): string
	{
		Session::start();

		if (!Session::has('csrf_token'))
			Session::set('csrf_token', bin2hex(random_bytes(32)));

		return Session::get('csrf_token');
	}

	function encrypt($string): string {
		$numbers = [];

		foreach (str_split($string) as $char) {
			if (ctype_lower($char)) {
				$numbers[] = 'L' . (ord($char) - ord('a') + 5);
			} elseif (ctype_upper($char)) {
				$numbers[] = 'U' . (ord($char) - ord('A') + 5);
			} else {
				$numbers[] = 'X' . $char;
			}
		}

		return implode('-', $numbers);
	}

	function decrypt($encoded): string {
		$parts = explode('-', $encoded);
		$decoded = '';

		foreach ($parts as $part) {
			if (strpos($part, 'L') === 0) {
				$decoded .= chr((int)substr($part, 1) + ord('a') - 5);
			} elseif (strpos($part, 'U') === 0) {
				$decoded .= chr((int)substr($part, 1) + ord('A') - 5);
			} elseif (strpos($part, 'X') === 0) {
				$decoded .= substr($part, 1);
			}
		}

		return $decoded;
	}

	function render(string $path, array $data = [], $asynchronous = false): string
	{
		return App\Utilities\Stream::render($path, $data, $asynchronous);
	}

	function launch_cli_session(array $args, string $path = '', string $root = ''): void
	{
		if ($path)
			Terminal::config($path, $root);

		Terminal::config('commands', __DIR__);
		Terminal::capture($args);
	}

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

		Blade::render($mainPath, extract: $data);

		return ob_get_clean();
	}

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

	function validate_token(): void
	{
		if (!in_array(Request::method(), ['GET', 'HEAD', 'OPTIONS']) && request()->header('X-CSRF-TOKEN') !== Session::get('csrf_token'))
			exit(response(['message' => 'Bad Request'], 400)->json());
	}
