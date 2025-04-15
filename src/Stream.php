<?php

	namespace App\Utilities;

	use App\Headers\Request;
	use ReflectionException;
	use ReflectionMethod;

	class Stream
	{
		private static string $root = '';
		private static array $methodCache = [];
		private static array $compiled = [];

		public static function config(string $root): void
		{
			self::$root = $root;
		}

		public static function render(string $path, array $data = [], $asynchronous = false): string
		{
			if (!$data && ($html = self::isCompiled($path)))
				return $html;

			ob_start();

			$component = null;
			if (file_exists($full_path = self::$root. ltrim($path, '/') .".php"))
				$component = require($full_path);

			if ($component) {
				$component->initialize($path, $data);

				if ($asynchronous) {
					if (method_exists($component, 'loader'))
						echo($component->loader($data));

					echo($component->preloader());
				} else {
					echo($component->parse());
				}
			}

			# Capture the content
			return ob_get_clean();
		}

		public static function capture(): string
		{
			$req = new Request();
			if (Request::header('X-STREAM-WIRE')) {
				$validate = $req->validate([
					'_component' => 'required',
					'_method' => 'required'
				]);

				if ($validate->isSuccess()) {

					$component = $req->input('_component');
					$method = $req->input('_method');
					$properties = $req->input('_properties');
					$models = $req->input('_models');

					$startedTime = hrtime(true);
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
					if (file_exists($full_path = self::$root. ltrim($path, '/') .".php"))
						$component = require($full_path);

					if ($component) {

						if ($orig_properties)
							$component->models($orig_properties);

						if ($function != 'render' && self::validateMethod($component, $function, $args))
							call_user_func_array([$component, $function], $args);

						return response($component->parse($identifier, $startedTime))->html();
					}
				}
			}

			// Exit error stream wire message
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

		private static function parse(string $actionString): null|array
		{
			preg_match('/^([\w]+)\((.*)\)$/', $actionString, $matches);

			if (!$matches)
				return null;

			$functionName = $matches[1];
			$paramsString = $matches[2];
			$params = preg_split('/,(?=(?:[^\'"]*[\'"][^\'"]*[\'"])*[^\'"]*$)/', $paramsString);

			return [
				'name' => $functionName,
				'args' => array_map(fn($param) => trim($param, " '\""), $params)
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
	}