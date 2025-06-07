<?php

	namespace App\Utilities;

	use App\Bootstrap\Exceptions\StreamException;
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

		public static function configure(string|array $root, array $authentication = []): void
		{
			self::$root = is_string($root) ? [$root] : $root;

			if ($authentication)
				self::$authentication = $authentication;
		}

		public static function render(string $path, array $data = [], $asynchronous = false): string
		{
			if (!$data && ($html = self::isCompiled($path)))
				return $html;

			ob_start();

			$component = null;
			if (class_exists($path)) {
				$component = new $path();
			} else {
				foreach (self::$root as $rootPath) {
					$path = preg_replace('/\.php$/', '', $path);
					$normalizedPath = str_replace('\\', '/', $path);

					foreach (['.blade.php', '.php'] as $extension) {
						$full_path = $rootPath . $normalizedPath . $extension;
						if (file_exists($full_path)) {
							$component = require $full_path;
							break 2;
						}
					}
				}
			}

			if ($component) {

				if (!method_exists($component, 'initialize')) {
					$className = is_object($component) ? get_class($component) : gettype($component);
					throw new StreamException("Component of type `$className` at path `$path` is invalid: missing required `initialize` method.");
				}

				if (!self::verifyComponent($component))
					return ob_get_clean();

				$component->initialize($path, $data);

				if ($asynchronous) {
					echo($component->parse(preloader: true));
				} else {
					echo($component->parse());
				}
			} else {
				throw new StreamException("Unable to locate compiled file '{$path}'.");
			}

			# Capture the content
			return ob_get_clean();
		}

		/**
		 * @throws StreamException
		 */
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
					'_models' => 'string'
				]);

				if ($validate->isSuccess()) {

					$component = $req->input('_component');
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

					$component = null;
					if (class_exists($path)) {
						$component = new $path();
					} else {
						foreach (self::$root as $rootPath) {
							$normalizedPath = ltrim($path, '/');

							foreach (['.blade.php', '.php'] as $extension) {
								$full_path = $rootPath . $normalizedPath . $extension;
								if (file_exists($full_path)) {
									$component = require $full_path;
									break 2;
								}
							}
						}
					}

					if ($component) {

						if ($orig_properties)
							$component->models($orig_properties );

						if (!self::verifyComponent($component)) {
							throw new StreamException('Unauthorized', 401);
						}

						if ($function != 'render') {
							try {
								self::perform([$component, $function], $args);
							} catch (Exception $e) {
								throw new StreamException($e->getMessage(), 401);
							}
						}

						return response($component->parse($identifier ?? '', $startedTime, directSkeleton: false))->json();
					}
				}
			}

			throw new StreamException('Invalid Request', 400);
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
					throw new StreamException("Component uses Authenticatable trait but no authentication callback is configured.");
				}

				[$class, $method, $authArgs] = self::$authentication + [null, null, []];

				if (!is_callable([$class, $method])) {
					throw new StreamException("Invalid authentication callback: {$class}::{$method} is not callable.");
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

		private static function perform(array $action, array $params): void
		{
			$class = $action[0] ?? null;
			$method = $action[1] ?? null;

			if (!$class || !$method) {
				throw new StreamException('Invalid Request', 400);
			}

			if (!method_exists($class, $method)) {
				throw new StreamException('Invalid Request', 400);
			}

			$paramsValue = [];
			$reflection = new ReflectionMethod($class, $method);

			foreach ($reflection->getParameters() as $index => $param) {

				$type = $param->getType();
				$typeName = $type?->getName();

				if ($typeName && class_exists($typeName)) {
					$paramsValue[] = new $typeName();
				} else {
					$paramsValue[] = $params[$index] ?? null;
				}
			}

			call_user_func_array([$class, $method], $paramsValue);
		}
	}