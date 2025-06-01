<?php

	namespace App\Utilities\Blueprints;

	use App\Utilities\Stream;

	class StreamResponse {

		public function redirect(string $url, int $code = 302, array $headers = []): void {
			redirect($url, $code, array_merge([
				'Content-Type' => 'application/json',
				'X-AJAX-REDIRECT' => '1',
			], $headers));
		}

		public function perform(array $action = [], array $args = [], array $models = []): void {
			$class = $action[0] ?? '';
			$method = $action[1] ?? '';

			if ($class && class_exists($class)) {
				$response = Stream::capture($class, $method, $args, $models);
				$content = $response['content'] ?? '';
				$code = $response['code'] ?? 200;

				exit(response([
					'content' => $content
				], $code)->json());
			}

			throw new \Exception("Stream Response: Class {$class} does not exist.");
		}
	}