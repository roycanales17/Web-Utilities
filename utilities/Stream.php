<?php

	namespace App\Utilities;

	use App\Utilities\Handler\Component;
	use App\Http\Authenticatable;
	use ReflectionException;
	use ReflectionMethod;
	use Exception;

	final class Stream
	{
		private static array $methodCache = [];
		private static array $compiled = [];
		private static array $authentication = [];

		/**
		 * This helps us to add authentication for each component by applying 'use Authenticatable'
		 *
		 * @param array $authentication
		 * @return void
		 * @throws Exception
		 */
		public static function authentication(array $authentication = []): void
		{
			if ($authentication) {
				$class = $authentication[0] ?? null;
				$method = $authentication[1] ?? null;

				if (!class_exists($class) || !method_exists($class, $method))
					throw new Exception("Invalid authentication method '{$class}::{$method}'.", 500);

				self::$authentication = $authentication;
			}
		}

		/**
		 * @throws Exception
		 */
		/**
		 * This capture the stream wire request.
		 *
		 * @param Request $req
		 * @return string
		 * @throws Exception
		 */
		public static function capture(Request $req): string
		{
			$request = new Request();
			$startedTime = hrtime(true);
			$isStreamWire = $request->header('X-STREAM-WIRE') || $request->server('HTTP_X_STREAM_WIRE');

			if ($isStreamWire) {
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
					$class = base64_decode($component);
					$class = str_replace('COMPONENT_', '', decrypt($class));

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

					if (class_exists($class)) {
						/** @var Component $component */
						$component = new $class();

						if ($orig_properties)
							$component->models($orig_properties);

						if (!self::verifyComponent($component)) {
							throw new Exception('Unauthorized', 401);
						}

						if ($function != 'render') {
							try {
								self::perform([$component, $function], $args);
							} catch (Exception $e) {
								throw new Exception($e->getMessage(), 401);
							}
						}

						// This performs the $this->render function...
						return response($component->parse($identifier ?? '', $startedTime, directSkeleton: false))->json();
					}
				}
			}

			throw new Exception('Invalid Request', 400);
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
			if (!preg_match('/^([\w]+)\((.*)\)$/s', $actionString, $matches))
				return null;

			$functionName = $matches[1];
			$argsString   = trim($matches[2]);

			// Split by commas, respecting quotes
			$parts = preg_split(
				"/,(?=(?:[^'\"\\\\]*(?:\\\\.|['\"][^'\"\\\\]*['\"]))*[^'\"\\\\]*$)/",
				$argsString
			);

			$args = [];
			foreach ($parts as $p) {
				$p = trim($p);

				// If quoted → strip quotes
				if (preg_match("/^'(.*)'$/s", $p, $m) || preg_match('/^"(.*)"$/s', $p, $m)) {
					$args[] = $m[1];
				}
				// If numeric → cast to number
				elseif (is_numeric($p)) {
					$args[] = $p + 0;
				}
				// If empty → skip
				elseif ($p === '') {
					continue;
				}
				else {
					$args[] = $p;
				}
			}

			return [
				'name' => $functionName,
				'args' => $args,
			];
		}

		private static function isCompiled(string $path): string|bool
		{
			$request = new Request();
			$compiledJson = $request->post('_compiled');
			if ($compiledJson) {

				$compiledArray = json_decode($compiledJson, true);
				if (!is_array($compiledArray))
					return false;

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

		/**
		 * @throws Exception
		 */
		public static function verifyComponent(Component $component): bool
		{
			if (!is_subclass_of($component, Component::class)) {
				throw new Exception("Component '".get_class($component)."' does not implement " . Component::class, 500);
			}

			// Check for Authenticatable trait
			if (in_array(Authenticatable::class, class_uses($component))) {
				if (empty(self::$authentication)) {
					throw new Exception("Component uses Authenticatable trait but no authentication callback is configured.", 500);
				}

				[$class, $method, $authArgs] = self::$authentication + [null, null, []];

				if (!is_callable([$class, $method])) {
					throw new Exception("Invalid authentication callback: {$class}::{$method} is not callable.", 500);
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

		/**
		 * @throws Exception
		 */
		private static function perform(array $action, array $params): void
		{
			$class = $action[0] ?? null;
			$method = $action[1] ?? null;

			if (!$class || !$method) {
				throw new Exception('Class and method must be provided', 500);
			}

			if (!method_exists($class, $method)) {
				throw new Exception("Invalid stream wire request '{$class}::{$method}'.", 500);
			}

			$paramsValue = [];
			$reflection = new ReflectionMethod($class, $method);

			foreach ($reflection->getParameters() as $index => $param) {
				$type = $param->getType();
				$typeName = $type?->getName();

				if ($typeName && class_exists($typeName)) {
					$paramsValue[] = new $typeName();
				} else {
					if (isset($params[$index])) {
						$paramsValue[] = $params[$index];
					}
				}
			}

			try {
				$class->{$method}(...$paramsValue);
			} catch (Exception $e) {
				throw new Exception($e->getMessage(), 500);
			}
		}
	}