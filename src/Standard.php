<?php

	use App\Utilities\Config;
	use App\Utilities\Session;

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
