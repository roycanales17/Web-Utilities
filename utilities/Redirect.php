<?php

	namespace App\Utilities;

	use JetBrains\PhpStorm\NoReturn;

	final class Redirect
	{
		private string $url;
		private int $code;

		public function __construct(string $url, int $code = 302)
		{
			$this->url = $url;
			$this->code = $code;
		}

		/**
		 * Redirect to a specific URL.
		 */
		public static function to(string $url, int $code = 302): self
		{
			return new self($url, $code);
		}

		/**
		 * Redirect back to the previous page.
		 */
		public static function back(): self
		{
			$request = new Request();
			$referer = $request->header('HTTP_REFERER') ?? $request->server('HTTP_REFERER', '/');
			return new self($referer);
		}

		/**
		 * Refresh the current page.
		 */
		public static function refresh(): self
		{
			$request = new Request();
			return new self($request->server('REQUEST_URI', '/'));
		}

		/**
		 * Flash data to the session.
		 */
		public function with(string|array $key, mixed $value = null): self
		{
			if (is_array($key)) {
				foreach ($key as $k => $v) {
					Session::flash($k, $v);
				}
			} else {
				Session::flash($key, $value);
			}

			return $this;
		}

		/**
		 * Flash validation or system errors.
		 */
		public function withErrors(string|array $errors): self
		{
			Session::flash('errors', (array) $errors);
			return $this;
		}

		/**
		 * Flash a success message.
		 */
		public function withSuccess(string $message): self
		{
			Session::flash('success', $message);
			return $this;
		}

		/**
		 * Flash input data for old() retrieval.
		 */
		public function withInput(array $input = []): self
		{
			// If no input passed, take all request input
			if (empty($input)) {
				$input = (new Request)->all();
			}

			Session::flash('_old_input', $input);
			return $this;
		}

		/**
		 * Send the redirect response.
		 */
		#[NoReturn] public function __destruct()
		{
			$url = filter_var($this->url, FILTER_SANITIZE_URL);
			$code = in_array($this->code, [301, 302, 303, 307, 308]) ? $this->code : 302;

			// If AJAX, return JSON
			$isAjax = Server::isAjaxRequest();

			if ($isAjax) {
				$flashData = Session::pullFlashedData();
				exit(json_encode([
					'redirect' => $url,
					'flash' => $flashData,
				]));
			}

			header("Location: $url", true, $code);
			exit();
		}
	}