<?php

	namespace App\Utilities\Blueprints;

	use App\Utilities\Stream;

	class StreamResponse {

		public function redirect(string $url, int $code = 200, array $headers = []): void {
			redirect($url, $code, array_merge([
				'Content-Type' => 'application/json',
				'X-AJAX-REDIRECT' => '1',
			], $headers));
		}

		public function perform(array $action = []): void {
			$isIndexedArray = array_keys($action) === range(0, count($action) - 1);
			$perform = function($action) {
				$class = $action[0] ?? '';
				$method = $action[1] ?? '';
				$args = $action[2] ?? [];

				if ($class && class_exists($class)) {
					$response = Stream::capture($class, $method, $args);

					$target = $response['target'] ?? '';
					$content = $response['content'] ?? '';
					$code = $response['code'] ?? 200;
					$message = $response['message'] ?? '';

					if ($code !== 200) {
						throw new \Exception("Stream Response: {$message}");
					}

					return [
						'content' => $content,
						'target' => $target
					];
				}

				throw new \Exception("Stream Response: Class {$class} does not exist.");
			};

			$result = [];
			if ($isIndexedArray) {
				foreach ($action as $action_r) {
					$result[] = $perform($action_r);
				}
			} else {
				$result[] = $perform($action);
			}

			exit(response($result)->json());
		}
	}