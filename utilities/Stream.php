<?php

	namespace App\Utilities;

	use App\Http\Authenticatable;
	use Closure;
	use Exception;
	use App\Headers\Request;
	use ReflectionException;
	use ReflectionMethod;

	class Stream
	{
		private static array $root = [];
		private static array $methodCache = [];
		private static array $compiled = [];
		private static array $authentication = [];
		private static Closure|null $onFailed = null;

		public static function configure(string|array $root, array $authentication = [], Closure|null $onFailed = null): void
		{
			self::$root = is_string($root) ? [$root] : $root;

			if ($authentication)
				self::$authentication = $authentication;

			if ($onFailed)
				self::$onFailed = $onFailed;
		}

		public static function render(string $path, array $data = [], $asynchronous = false): string
		{
			if (!$data && ($html = self::isCompiled($path)))
				return $html;

			ob_start();
			if (class_exists($path)) {
				$component = new $path();
			} else {
				throw new Exception("Class {$path} does not exist.");
			}

			if (!method_exists($component, 'initialize')) {
				$className = is_object($component) ? get_class($component) : gettype($component);
				throw new Exception("Component of type `$className` at path `$path` is invalid: missing required `initialize` method.");
			}

			if (!self::verifyComponent($component))
				return ob_get_clean();

			$component->initialize($path, $data);

			if ($asynchronous) {
				echo($component->parse(preloader: true));
			} else {
				echo($component->parse());
			}

			# Capture the content
			return ob_get_clean();
		}

		public static function capture(): string
		{
			/** @var Component $component */

			$req = new Request();
			$startedTime = hrtime(true);

			if (Request::header('X-STREAM-WIRE')) {

				$validate = $req->validate([
					'_component' => 'required|string',
					'_method' => 'required|string',
					'_properties' => 'string',
					'_models' => 'string',
					'_target' => 'string'
				]);

				if ($validate->isSuccess()) {

					$component = $req->input('_component');
					$target = $req->input('_target');
					$method = $req->input('_method');
					$properties = $req->input('_properties');
					$models = $req->input('_models');
					$identifier = $component;

					$models = json_decode($models, true);
					$path = base64_decode($component);
					$path = str_replace('COMPONENT_', '', decrypt($path));

					$properties = base64_decode($properties);
					$properties = decrypt($properties);
					$properties = json_decode($properties, true);

					$orig_properties = [];
					foreach ($properties as $key_p => $value_p) {
						$found = false;

						foreach ($models as $key_m => $value_m) {
							if ($key_p == $key_m) {
								$orig_properties[$key_p] = $value_m;
								$found = true;
								break;
							}
						}

						if (!$found)
							$orig_properties[$key_p] = $value_p;
					}

					$parsed = self::parse($method);
					$function = $parsed['name'] ?? $method;
					$args = $parsed['args'] ?? [];

					if ($target) {
						$target = decryption($target, self::password());
						$target = explode('___', $target);
						$target = $target[0] ?? '';
						$identifier = $target[1] ?? '';

						if ($target) {
							$path = $target;
						}
					}

					if (class_exists($path)) {
						$component = new $path();
					} else {
						throw new Exception("Class {$path} does not exist.");
					}

					if ($orig_properties)
						$component->models($orig_properties );

					if (!self::verifyComponent($component)) {
						if (self::$onFailed)
							return call_user_func(self::$onFailed, 401);

						return response(['message' => 'Unauthorized'], 401)->json();
					}

					if ($function != 'render' && self::validateMethod($component, $function, $args)) {
						call_user_func_array([$component, $function], $args);
					}

					return response($component->parse($identifier ?? '', $startedTime, directSkeleton: false))->json();
				}
			}

			if (self::$onFailed)
				return call_user_func(self::$onFailed, 400);

			return response(['message' => 'Invalid Request'], 400)->json();
		}

		private static function validateMethod(object $class, string $method, array $args): bool
		{
			if (!method_exists($class, $method))
				return false;

			$className = get_class($class);
			$cacheKey = $className . '::' . $method;

			if (!isset(self::$methodCache[$cacheKey])) {
				try {
					self::$methodCache[$cacheKey] = new ReflectionMethod($class, $method);
				} catch (ReflectionException $e) {
					return false;
				}
			}

			$reflection = self::$methodCache[$cacheKey];
			$requiredParams = $reflection->getNumberOfRequiredParameters();
			$totalParams = $reflection->getNumberOfParameters();
			$providedParams = count($args);

			return $providedParams >= $requiredParams && $providedParams <= $totalParams;
		}

		private static function parse(string $actionString): ?array
		{
			$actionString = trim($actionString);

			if (!preg_match('/^([\w]+)\((.*)\)$/s', $actionString, $matches)) {
				return null;
			}

			$functionName = $matches[1];
			$argsString = trim($matches[2]);

			$jsonArgsString = preg_replace_callback(
				"/'(.*?)'/s",
				fn($m) => '"' . str_replace('"', '\\"', $m[1]) . '"',
				$argsString
			);

			$json = "[$jsonArgsString]";

			$args = json_decode($json, true);

			if (!is_array($args)) {
				return null;
			}

			return [
				'name' => $functionName,
				'args' => $args,
			];
		}

		private static function isCompiled(string $path): string|bool
		{
			$compiledJson = request()->post('_compiled');

			if ($compiledJson) {
				$compiledArray = json_decode($compiledJson, true);

				if (!is_array($compiledArray)) {
					return false;
				}

				$normalizedTargetPath = strtolower(preg_replace('/\.php$/i', '', $path));

				foreach ($compiledArray as $encodedPath => $html) {
					if (is_string($html) && is_string($encodedPath)) {
						$decoded = base64_decode($encodedPath);
						$compiledPath = str_replace('COMPONENT_', '', decrypt($decoded));
						$normalizedCompiledPath = strtolower(preg_replace('/\.php$/i', '', $compiledPath));

						// Group all compiled HTMLs by compiled path
						if (!isset(self::$compiled[$compiledPath])) {
							self::$compiled[$compiledPath] = 0;
						}

						self::$compiled[$compiledPath]++;

						// Return the latest HTML if paths match
						if ($normalizedTargetPath === $normalizedCompiledPath) {

							$index = 0;
							foreach ($compiledArray as $encodedPath2 => $html2) {
								if (is_string($html2) && is_string($encodedPath2)) {
									$decoded2 = base64_decode($encodedPath2);
									$compiledPath2 = str_replace('COMPONENT_', '', decrypt($decoded2));
									$normalizedCompiledPath2 = strtolower(preg_replace('/\.php$/i', '', $compiledPath2));

									if ($normalizedTargetPath === $normalizedCompiledPath2) {
										$index++;

										if ($index == self::$compiled[$compiledPath])
											return $html2;
									}
								}
							}
						}
					}
				}
			}

			return false;
		}

		private static function verifyComponent($component): bool
		{
			// Check for Authenticatable trait
			if (in_array(Authenticatable::class, class_uses($component))) {
				if (empty(self::$authentication)) {
					throw new Exception("Component uses Authenticatable trait but no authentication callback is configured.");
				}

				[$class, $method, $authArgs] = self::$authentication + [null, null, []];

				if (!is_callable([$class, $method])) {
					throw new Exception("Invalid authentication callback: {$class}::{$method} is not callable.");
				}

				if (!call_user_func_array([$class, $method], $authArgs)) {
					return false;
				}
			}

			// Check for a custom verify() method
			if (method_exists($component, 'verify') && !call_user_func([$component, 'verify'])) {
				return false;
			}

			return true;
		}

		public static function password(): string {
			return 'stream-wire';
		}
	}