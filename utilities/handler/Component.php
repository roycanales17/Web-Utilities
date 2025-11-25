<?php

	namespace App\Utilities\Handler;

	use App\View\Compilers\Blade;
	use App\Utilities\Redirect;
	use App\Utilities\Session;
	use App\Utilities\Buffer;
	use ReflectionProperty;
	use ReflectionClass;
	use Exception;

	abstract class Component
	{
		private array $__errors = [];
		private array $__success = [];
		private array $__extender = [];
		private float $__startedTime = 0;
		private bool $__skipCompile = false;
		private string $__componentIdentifier = '';
		private static array $__registered = [];
		private static array $__reflectionCache = [];
		private static array $__propertyNamesCache = [];

		/**
		 * Generates a unique identifier for the component using a time-based suffix.
		 *
		 * @param string $component The name of the component.
		 * @return string A unique identifier for the component.
		 */
		private function generateComponentIdentifier(string $component): string
		{
			$timeIdentifier = substr(hrtime(true), -5);
			$baseId = encrypt("COMPONENT_" . $component) . "-{" . $timeIdentifier . "}";
			$existing = array_filter(
				self::$__registered,
				fn($id) => str_starts_with($id, $baseId . "-[")
			);

			// Generate a unique identifier, incrementing the count if needed.
			if (empty($existing)) {
				return $baseId . "-[1]";
			}

			preg_match('/\[(\d+)\]$/', end($existing), $matches);
			$count = isset($matches[1]) ? ((int)$matches[1] + 1) : 2;

			return preg_replace('/\[\d+\]$/', "[$count]", end($existing)) ?: $baseId . "-[$count]";
		}

		/**
		 * Component skeleton loader
		 *
		 * @return string Component skeleton loader
		 * @throws Exception
		 */
		private function preloader($component, $startedTime): string
		{
			if (!method_exists($this, 'loader'))
				throw new Exception('Loader function is required.', 500);

			$html = $this->replaceHTML($this->loader(), $component);
			$dataAttributes = $this->getAttributes($component, $startedTime);

			return
				<<<HTML
<fragment class='component-container' {$dataAttributes}>
    {$html}
    <script>
        (function() {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
            const component = document.querySelector("[data-component='{$component}']");
            const properties = component.getAttribute('data-properties');

            if (!component) return;

            let isJson = false;
            const form = new FormData();
            form.append('_component', '$component');
            form.append('_method', 'render');
            form.append('_properties', properties);
            form.append('_models', '');

            fetch("/api/stream-wire/{$component}", {
                method: "POST",
                headers: {
                    "X-STREAM-WIRE": "true",
                    "X-CSRF-TOKEN": token
                },
                body: form
            })
            .then(response => {
                const contentType = response.headers.get("Content-Type") || "";

                if (!response.ok) {
                    console.error(
                        `%c❌ HTTP ERROR! %cStatus: \${response.status} 🚫`,
                        "color: red; font-weight: bold;",
                        "color: orange;"
                    );

                    if (response.status === 500) {
                        response.text().then(errorHtml => {
                            component.innerHTML += errorHtml;
                        });
                    }

                    return null;
                }

                if (contentType.includes("application/json")) {
                    isJson = true;
                    return response.json().catch(err => {
                        console.error("⚠️ Failed to parse JSON:", err);
                        return null;
                    });
                } else if (contentType.includes("text/html")) {
                    return response.text().catch(err => {
                        console.error("⚠️ Failed to read HTML:", err);
                        return null;
                    });
                } else {
                    console.warn("⚠️ Unknown content type:", contentType);
                    return null;
                }
            })
            .then(res => {
                var newContent = '';
                if (isJson) {
                    if (res.redirect !== undefined) {
                        window.location.href = res.redirect;
                        return;
                    } else {
                        newContent = res.content;
                    }
                } else {
                    newContent = res;
                }

                if (newContent) {
                    const tempDiv = document.createElement('div');
                    tempDiv.innerHTML = newContent.trim();

                    const newComponent = tempDiv.firstElementChild;
                    if (newComponent) {
                        component.replaceWith(newComponent);

                        newComponent.querySelectorAll('script').forEach(oldScript => {
                            const newScript = document.createElement('script');
                            if (oldScript.src) newScript.src = oldScript.src;
                            if (oldScript.type) newScript.type = oldScript.type;
                            if (oldScript.textContent && !oldScript.src)
                                newScript.textContent = oldScript.textContent;

                            document.head.appendChild(newScript).remove();
                        });
                    } else {
                        console.warn("⚠️ No valid component HTML returned for replacement.");
                    }
                }
            });
        })();
    </script>
</fragment>
HTML;
		}

		/**
		 * Fetches the public properties of the component as an associative array.
		 *
		 * @return array An array of public properties and their values.
		 */
		private function fetchProperties(): array
		{
			$className = get_class($this);

			if (!isset(self::$__propertyNamesCache[$className])) {
				$reflection = new ReflectionClass($this);
				$publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

				self::$__propertyNamesCache[$className] = array_map(
					fn($prop) => $prop->getName(),
					$publicProperties
				);
			}

			$properties = [];
			foreach (self::$__propertyNamesCache[$className] as $propertyName) {
				$properties[$propertyName] = $this->$propertyName;
			}

			return $properties;
		}

		/**
		 * Initializes the component by generating a unique identifier and calling the 'init' method if it exists.
		 *
		 * @deprecated Use the $this->init method instead, as it acts as the constructor.
		 * @param string $component The component name.
		 * @param array $params Parameters passed to the 'init' method.
		 * @return void
		 */
		public function initialize(string $component, array $params = []): void
		{
			$this->__startedTime = hrtime(true);
			$this->__componentIdentifier = $this->generateComponentIdentifier($component);
			self::$__registered[] = $this->__componentIdentifier;

			// Call the init method if it exists.
			if (method_exists($this, 'init')) {
				$this->init(...$params);
			}
		}

		/**
		 * Populates the component's properties with the provided values.
		 *
		 * @deprecated Do not use this method.
		 * @param array $models Key-value pairs of properties and their values.
		 * @return void
		 */
		public function models(array $models): void
		{
			$class = static::class;
			if (!isset(self::$__reflectionCache[$class])) {
				$ref = new \ReflectionClass($this);
				$props = [];

				foreach ($ref->getProperties() as $prop) {
					$props[$prop->getName()] = $prop;
				}

				self::$__reflectionCache[$class] = $props;
			}

			$properties = self::$__reflectionCache[$class];
			$isMatched = function(string $type, mixed $value): bool {
				return match ($type) {
					'int'    => is_int($value),
					'float'  => is_float($value),
					'string' => is_string($value),
					'bool'   => is_bool($value),
					'array'  => is_array($value),
					'object' => is_object($value),
					default  => $value instanceof $type,
				};
			};

			foreach ($models as $key => $value) {
				if (!isset($properties[$key])) {
					continue;
				}

				$prop = $properties[$key];

				if ($prop->hasType()) {
					$type = $prop->getType()->getName();

					if (!$isMatched($type, $value)) {
						continue;
					}
				}

				$this->$key = $value;
			}
		}

		/**
		 * Performs an Ajax-based redirection.
		 *
		 * This helper wraps redirect() but forces an AJAX-compatible
		 * response by attaching headers such as `X-AJAX-REDIRECT`.
		 * If `$return` is true, the Redirect instance is returned so additional
		 * data (errors, messages, inputs) can be appended using `with()`.
		 *
		 * @param string $url          Destination URL.
		 * @param int    $code         HTTP status code for the redirect.
		 * @param array  $headers      Extra headers to append to the response.
		 * @param bool   $return       When true, returns the Redirect object.
		 *
		 * @return Redirect|null       Returns Redirect when `$return = true`, otherwise null.
		 */
		protected function redirect(string $url, int $code = 200, array $headers = [], bool $return = false): null|Redirect {
			$redirect = redirect($url, $code, array_merge([
				'Content-Type'    => 'application/json',
				'X-AJAX-REDIRECT' => '1',
			], $headers));

			if ($return) {
				return $redirect;
			}

			unset($redirect);
			return null;
		}

		/**
		 * Redirects with validation errors for AJAX responses.
		 *
		 * Attaches each error using the key format `error:{field}`.
		 * Also attaches old input values if provided.
		 *
		 * @param string $url          Destination URL.
		 * @param array  $errors       Associative array of validation errors.
		 * @param array  $inputs       Old input values to retain.
		 *
		 * @return void
		 */
		protected function redirectErrors(string $url, array $errors, array $inputs = []): void {
			$redirect = $this->redirect($url, 400, return: true);

			foreach ($errors as $key => $msg) {
				$redirect->with("error:$key", $msg);
			}

			foreach ($inputs as $key => $value) {
				$redirect->with("input:$key", $value);
			}

			unset($redirect);
		}

		/**
		 * Redirects with a success message for AJAX responses.
		 *
		 * Attaches the success message using the key `message:success`.
		 * Also forwards old input values if provided.
		 *
		 * @param string $url          Destination URL.
		 * @param string $message      Success message to store in session.
		 * @param array  $inputs       Old input values to retain.
		 *
		 * @return void
		 */
		protected function redirectSuccess(string $url, string $message, array $inputs = []): void {
			$redirect = $this->redirect($url, 200, return: true);
			$redirect->with("message:success", $message);

			foreach ($inputs as $key => $value) {
				$redirect->with("input:$key", $value);
			}

			unset($redirect);
		}

		/**
		 * Redirects with a failure/error message for AJAX responses.
		 *
		 * Attaches the failure message using the key `message:error`.
		 * Also forwards input values if provided.
		 *
		 * @param string $url          Destination URL.
		 * @param string $message      Failure/error message to store in session.
		 * @param array  $inputs       Old input values to retain.
		 *
		 * @return void
		 */
		protected function redirectFail(string $url, string $message, array $inputs = []): void {
			$redirect = $this->redirect($url, 400, return: true);
			$redirect->with("message:error", $message);

			foreach ($inputs as $key => $value) {
				$redirect->with("input:$key", $value);
			}

			unset($redirect);
		}

		/**
		 * Redirects with a warning message for AJAX responses.
		 *
		 * Attaches the warning message using the key `message:warning`.
		 * Also forwards input values if provided.
		 *
		 * @param string $url          Destination URL.
		 * @param string $message      Warning message to store in session.
		 * @param array  $inputs       Old input values to retain.
		 *
		 * @return void
		 */
		protected function redirectWarning(string $url, string $message, array $inputs = []): void {
			$redirect = $this->redirect($url, 200, return: true);
			$redirect->with("message:warning", $message);

			foreach ($inputs as $key => $value) {
				$redirect->with("input:$key", $value);
			}

			unset($redirect);
		}

		/**
		 * Parse the component's render output and return a formatted HTML string with data attributes.
		 *
		 * @deprecated Do not use this function.
		 * @param string $identifier Optional identifier for the component.
		 * @param float $startedTime Time when the component started.
		 * @return array|string The rendered component wrapped in a <fragment> element with data attributes.
		 * @throws Exception
		 */
		public function parse(string $identifier = '', float $startedTime = 0, bool $preloader = false, bool $directSkeleton = true, array $trace = []): string|array
		{
			if (!$preloader && !method_exists($this, 'render'))
				throw new Exception("Render function is required.", 500);

			// Prepare data attributes for the component.
			$component = $identifier ?: base64_encode($this->__componentIdentifier);
			$startedTime = ($identifier ? $startedTime : $this->__startedTime);

			// For development
			$dev = get_constant('DEVELOPMENT', true);

			if ($preloader)
				return $this->preloader($component, $startedTime);

			if ($this->__skipCompile) {
				$render = [
					'content' => '',
					'extender' => $this->prepareExtender()
				];
			} else {
				if (!method_exists($this, 'render'))
					throw new Exception("Render function is required.", 500);

				$render = $this->render();
			}

			$html = $this->replaceHTML($render['content'] ?? '', $component);
			$duration = $this->calculateDuration($startedTime);

			$traceJson = json_encode(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), JSON_PRETTY_PRINT);
			$traceEscaped = json_encode($traceJson);

			$trace = json_encode($trace, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
			$trace = json_encode($trace);

			$compiled = <<<HTML
			<fragment class="component-container" {$this->getAttributes($component, $startedTime)}>
				{$html}

				<script id="__fragment__">
					(function () {
						if (typeof stream !== 'function') {
							console.error("Stream wire is not available");
							return;
						}

						stream("{$component}").finally(() => {
							{$this->print(function () use ($dev, $component, $duration, $traceEscaped, $trace) {
								if (!$dev && get_constant('STREAM_DEBUG', true)) {
									return;
								}
				
								$class = get_called_class();
								$escapedClass = addslashes($class);
								$escapedComponent = addslashes($component);
				
								echo <<<JS
									console.log("%c[Stream Completed]", "color: green; font-weight: bold;");

									// Simple log for Class (no grouping)
									console.log("Class: %c{$escapedClass}", "color: red;");
									
									// Collapsed group for Component details
									console.groupCollapsed("Details");
									console.log("Identifer: {$escapedComponent}");
									try {
									    const traceSingle = JSON.parse({$trace});
									    if (traceSingle && typeof traceSingle === 'object') {
									        Object.entries(traceSingle).forEach(([key, val]) => {
									            console.log(key+':', val);
									        });
									    } else {
									        console.log(traceSingle);
									    }
									} catch (err) {
									    console.warn("Failed to parse exception trace:", err);
									    console.log({$trace});
									}
									console.groupEnd();
									
									console.groupCollapsed("%cPHP Backtrace", "color: cyan; font-weight: bold;");
									console.table(JSON.parse({$traceEscaped}));
									console.groupEnd();
									
									console.log("Duration: %c{$duration} ms", "color: orange;");
									console.log(" ");
								JS;
							})}
						});
					})();
				</script>
			</fragment>
			HTML;

			if (!$directSkeleton) {
				$render['content'] = $compiled;
				return $render;
			}

			return $compiled;
		}

		/**
		 * Calculates the duration in milliseconds since the given start time.
		 *
		 * @param float $startedTime The start time in nanoseconds (from hrtime(true)).
		 * @return string The duration in milliseconds formatted as a string with 2 decimal places.
		 */
		private function calculateDuration(float $startedTime): string
		{
			$duration = hrtime(true) - $startedTime;
			$durationMs = $duration / 1_000_000;
			return sprintf('%.2f', $durationMs);
		}

		/**
		 * Generates HTML data attributes string for the component.
		 *
		 * Includes the component name, the duration since start time,
		 * base64-encoded encrypted JSON properties, and optionally class and target id attributes.
		 *
		 * @param string $component The name of the component.
		 * @param mixed $startedTime The start time used to calculate the duration.
		 * @return string A string of HTML data attributes for embedding in a tag.
		 */
		private function getAttributes(string $component, mixed $startedTime): string
		{
			$extra = [];
			$dataAttributes = '';

			$dev = get_constant('DEVELOPMENT', true);
			$properties = $this->fetchProperties();

			if ($dev) $extra['class'] = get_called_class();
			foreach (array_merge([
				'component' => $component,
				'duration' => $this->calculateDuration($startedTime),
				'properties' => base64_encode(encrypt(json_encode($properties)))
			], $extra) as $key => $value) {
				$dataAttributes .= " data-" . htmlspecialchars($key) . "='" . htmlspecialchars($value, ENT_QUOTES) . "'";
			}

			if (method_exists(static::class, 'identifier')) {
				$componentDNA = static::identifier();
				$dataAttributes .= " data-id='{$componentDNA}'";
			}

			return $dataAttributes;
		}

		/**
		 * This allows us to perform another component.
		 *
		 * @param array $action
		 * @param mixed ...$args
		 * @return void
		 */
		protected function extender(array $action, ...$args): void
		{
			[$class, $method] = $action + [null, null];

			if (!$class || !$method)
				throw new Exception("Both class and method must be provided.");

			if (!class_exists($class))
				throw new Exception("Class {$class} does not exist.");

			if (!method_exists($class, $method))
				throw new Exception("Method {$method} does not exist.");

			$action[2] = $action[2] ?? $args;
			$this->__extender[] = $action;
		}

		/**
		 * Smart action to run extender and exit at one action.
		 */
		protected function invokeAndExit(array $actions, ...$args): void
		{
			if (empty($actions)) {
				$this->exit();
			}

			$isMultiple = is_array($actions[0]);
			foreach ($isMultiple ? $actions : [$actions] as $action) {
				$passedArgs = $args ?: ($action[2] ?? []);
				$this->extender($action, ...$passedArgs);
			}

			$this->exit();
		}

		/**
		 * This skip the render function.
		 *
		 * @return void
		 */
		protected function exit(): void
		{
			if (!$this->__skipCompile) {
				$this->__skipCompile = true;
			}
		}

		/**
		 * Compiles and returns the content of the view associated with the component.
		 *
		 * @param array $data Data to be passed to the view for rendering.
		 * @return array The rendered HTML content from the matched view file.
		 */
		protected function compile(array $data = []): array
		{
			if ($this->__success) {
				foreach ($this->__success as $key => $msg) {
					Session::flash("success:$key", $msg);
				}
			}

			if ($this->__errors) {
				foreach ($this->__errors as $key => $msg) {
					Session::flash("error:$key", $msg);
				}
			}

			$publicProperties = get_object_vars($this);
			$data = array_merge($data, $publicProperties);

			$loadBaseComponent = function() use ($data) {
				$base = str_replace(['.', '\\'], '/', get_called_class());
				$path = base_path("/views/{$base}.blade.php");
				return \App\View\Compilers\Component::renderComponents(Blade::load($path, $data));
			};

			$baseComponent = '';
			if (!$this->__skipCompile) {
				$baseComponent = $loadBaseComponent();
			}

			return [
				'content' => $baseComponent,
				'extender' => $this->prepareExtender()
			];
		}

		/**
		 * Outputs the result of a callback or returns the value if it's not a callable.
		 *
		 * @param mixed $callback The callback function or value to print.
		 * @return mixed The result of the callback or the original value.
		 */
		protected function print(mixed $callback): mixed
		{
			if (is_callable($callback)) {
				return Buffer::capture($callback);
			}

			return $callback;
		}

		/**
		 * This prepares the extender.
		 *
		 * @return array
		 * @throws Exception
		 */
		private function prepareExtender(): array
		{
			$extender = [];
			if ($this->__extender) {
				$isSingleAction = isset($this->__extender[0]) && is_string($this->__extender[1] ?? null) && is_array($this->__extender[2] ?? null);

				$prepare = function($action) {
					$class = $action[0] ?? '';
					$method = $action[1] ?? '';
					$args = $action[2] ?? [];

					if ($class && class_exists($class)) {

						if (self::class === $class)
							throw new Exception("Class `{$class}` is not allowed from extender.", 500);

						if (!method_exists($class, $method))
							throw new Exception("Class `{$method}` is not allowed from extender.", 500);

						$componentDNA = '';
						if (method_exists($class, 'identifier')) {
							$componentDNA = $class::identifier();
						}

						if ($componentDNA) {
							return [
								'target' => $componentDNA,
								'method' => $method . '(' . implode(', ', array_map('json_encode', $args)) . ')'
							];
						}

						return [];
					}

					throw new Exception("Stream Response: Class {$class} does not exist.", 500);
				};

				if (!$isSingleAction) {
					foreach ($this->__extender as $action_r) {
						$prepared = $prepare($action_r);
						if ($prepared) {
							$extender[] = $prepared;
						}
					}
				} else {
					$prepared = $prepare($this->__extender);
					if ($prepared) {
						$extender[] = $prepared;
					}
				}
			}

			return $extender;
		}

		/**
		 * Set a success message for a specific key.
		 * Only sets the message if the key doesn't already exist.
		 *
		 * @param string $key     The unique identifier for the success message
		 * @param string $message The success message to store
		 * @return void
		 */
		protected function setSuccess(string $key, string $message): void
		{
			if (!isset($this->__success[$key])) {
				$this->__success[$key] = $message;
			}
		}

		/**
		 * Set an error message for a specific key.
		 * Only sets the message if the key doesn't already exist.
		 *
		 * @param string $key     The unique identifier for the error message
		 * @param string $message The error message to store
		 * @return void
		 */
		protected function setError(string $key, string $message): void
		{
			if (!isset($this->__errors[$key])) {
				$this->__errors[$key] = $message;
			}
		}

		/**
		 * Set multiple error messages at once.
		 * Iterates through the provided array and sets each error using setError().
		 *
		 * @param array<string, string> $errors Associative array of key-value pairs where
		 *                                       keys are error identifiers and values are error messages
		 * @return void
		 */
		protected function setErrors(array $errors): void
		{
			foreach ($errors as $key => $error) {
				$this->setError($key, $error);
			}
		}

		/**
		 * Replace HTML placeholders and inject component-specific StreamListener.
		 * Removes empty tag placeholders (<> and </>) and replaces generic StreamListener()
		 * with a component-specific StreamListener call.
		 *
		 * @param string $html      The HTML string to process
		 * @param string $component The component name to inject into StreamListener
		 * @return string The processed HTML with replacements applied
		 */
		private function replaceHTML(string $html, string $component): string
		{
			$html = str_replace(['<>', '</>'], '', $html);
			return str_replace('StreamListener()', "StreamListener('$component')", $html);
		}
	}
