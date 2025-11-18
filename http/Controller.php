<?php

	namespace App\Http;

	use App\Headers\Redirect;
	use App\Headers\Response;
	use App\Utilities\Session;
	use App\Utilities\Handler\LazyData;

	/**
	 * Base Controller
	 *
	 * Provides a set of helper methods for redirecting, rendering views,
	 * handling JSON responses, and managing flash session data.
	 * All controllers should extend this abstract class.
	 */
	abstract class Controller
	{
		/**
		 * Create a basic redirect response.
		 *
		 * @param string $url     Target URL for the redirect.
		 * @param int    $status  HTTP status code (default: 302).
		 * @param array  $headers Optional response headers.
		 *
		 * @return Redirect
		 */
		protected function redirect(string $url, int $status = 302, array $headers = []): Redirect
		{
			return redirect('/' . ltrim($url, '/'), $status, $headers);
		}

		/**
		 * Redirect back with validation error messages and optional input data.
		 *
		 * @param string $url     Target URL for the redirect.
		 * @param array  $errors  Array of validation errors [key => message].
		 * @param array  $inputs  Optional input data to flash back to the session.
		 *
		 * @return Redirect
		 */
		protected function redirectErrors(string $url, array $errors, array $inputs = []): Redirect
		{
			$redirect = $this->redirect($url, 400);
			foreach ($errors as $key => $msg) {
				$redirect->with("error:$key", $msg);
			}

			foreach ($inputs as $key => $value) {
				$redirect->with("input:$key", $value);
			}

			return $redirect;
		}

		/**
		 * Redirect with a success message and optional input data.
		 *
		 * @param string $url     Target URL for the redirect.
		 * @param string $message Success message to flash.
		 * @param array  $inputs  Optional input data to flash.
		 *
		 * @return Redirect
		 */
		protected function redirectSuccess(string $url, string $message, array $inputs = []): Redirect
		{
			$redirect = $this->redirect($url);
			$redirect->with("message:success", $message);

			foreach ($inputs as $key => $value) {
				$redirect->with("input:$key", $value);
			}

			return $redirect;
		}

		/**
		 * Redirect with a failure (error) message and optional input data.
		 *
		 * @param string $url     Target URL for the redirect.
		 * @param string $message Error message to flash.
		 * @param array  $inputs  Optional input data to flash.
		 *
		 * @return Redirect
		 */
		protected function redirectFail(string $url, string $message, array $inputs = []): Redirect
		{
			$redirect = $this->redirect($url, 400);
			$redirect->with("message:error", $message);

			foreach ($inputs as $key => $value) {
				$redirect->with("input:$key", $value);
			}

			return $redirect;
		}

		/**
		 * Redirect with a warning message and optional input data.
		 *
		 * @param string $url     Target URL for the redirect.
		 * @param string $message Warning message to flash.
		 * @param array  $inputs  Optional input data to flash.
		 *
		 * @return Redirect
		 */
		protected function redirectWarning(string $url, string $message, array $inputs = []): Redirect
		{
			$redirect = $this->redirect($url);
			$redirect->with("message:warning", $message);

			foreach ($inputs as $key => $value) {
				$redirect->with("input:$key", $value);
			}

			return $redirect;
		}

		/**
		 * Redirect with flashed input data.
		 *
		 * @param string $url    Target URL for the redirect.
		 * @param array  $inputs Array of input data to flash [key => value].
		 *
		 * @return Redirect
		 */
		protected function redirectInputs(string $url, array $inputs): Redirect
		{
			$redirect = $this->redirect($url, 200);
			foreach ($inputs as $key => $msg) {
				$redirect->with("input:$key", $msg);
			}

			return $redirect;
		}

		/**
		 * Render a Blade view.
		 *
		 * @param string $name  View name or path.
		 * @param array  $data  Optional data to pass to the view.
		 *
		 * @return string Rendered HTML.
		 */
		protected function view(string $name, array $data = []): string
		{
			return view($name, $data);
		}

		/**
		 * Create a JSON response.
		 *
		 * @param array $data     Data to return as JSON.
		 * @param int   $status   HTTP status code (default: 200).
		 * @param array $headers  Optional response headers.
		 *
		 * @return string
		 */
		protected function json(array $data, int $status = 200, array $headers = []): string
		{
			return response($data, $status, $headers)->json();
		}

		/**
		 * Create a JSON error response.
		 *
		 * @param string $message Error message to return.
		 * @param int    $status  HTTP status code (default: 400).
		 *
		 * @return string
		 */
		protected function jsonFail(string $message, int $status = 400): string
		{
			return $this->json(['error' => $message], $status);
		}

		/**
		 * Flash data to the session.
		 *
		 * @param string $key   The session key to store.
		 * @param mixed  $value The value to flash.
		 *
		 * @return void
		 */
		protected function flash(string $key, mixed $value): void
		{
			Session::flash($key, $value);
		}

		/**
		 * Dynamically call a controller method with parameters.
		 *
		 * @param string $method  Method name to call.
		 * @param array $params  Parameters to pass to the method.
		 *
		 * @return mixed
		 */
		protected function call(string $method, array $params = []): mixed
		{
			return call_user_func_array([$this, $method], $params);
		}

		/**
		 * Return a 204 No Content response.
		 *
		 * @return Response
		 */
		protected function noContent(): Response
		{
			return response('', 204);
		}

		/**
		 * Create a lazy data container.
		 *
		 * @param callable $callback Callback that returns the data when needed.
		 * @return LazyData
		 */
		protected function lazyData(callable $callback): LazyData
		{
			return new LazyData($callback);
		}
	}