<?php

	namespace App\Utilities\Handler;

	use App\Bootstrap\Exceptions\StreamException;
	use App\View\Compilers\Blade;
	use App\Utilities\Config;
	use ReflectionProperty;
	use ReflectionClass;
	use Exception;

	abstract class Component
	{
		private static array $registered = [];
		private static array $propertyNamesCache = [];
		private static array $reflectionCache = [];
		private array $extender = [];
		private string $componentIdentifier = '';
		private float $startedTime = 0;
		private bool $skipCompile = false;

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
				self::$registered,
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
				throw new Exception('Loader function is required.');

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
                        window.location.href = data.redirect;
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

			if (!isset(self::$propertyNamesCache[$className])) {
				$reflection = new ReflectionClass($this);
				$publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

				self::$propertyNamesCache[$className] = array_map(
					fn($prop) => $prop->getName(),
					$publicProperties
				);
			}

			$properties = [];
			foreach (self::$propertyNamesCache[$className] as $propertyName) {
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
			$this->startedTime = hrtime(true);
			$this->componentIdentifier = $this->generateComponentIdentifier($component);
			self::$registered[] = $this->componentIdentifier;

			// Call the init method if it exists.
			if (!empty($params) && method_exists($this, 'init')) {
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
			if (!isset(self::$reflectionCache[$class])) {
				$ref = new \ReflectionClass($this);
				$props = [];

				foreach ($ref->getProperties() as $prop) {
					$props[$prop->getName()] = $prop;
				}

				self::$reflectionCache[$class] = $props;
			}

			$properties = self::$reflectionCache[$class];
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
		 * @param string $url
		 * @param int $code
		 * @param array $headers
		 * @return void
		 */
		public function redirect(string $url, int $code = 200, array $headers = []): void {
			redirect($url, $code, array_merge([
				'Content-Type' => 'application/json',
				'X-AJAX-REDIRECT' => '1',
			], $headers));
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
				throw new Exception("Render function is required.");

			// Prepare data attributes for the component.
			$component = $identifier ?: base64_encode($this->componentIdentifier);
			$startedTime = ($identifier ? $startedTime : $this->startedTime);

			// For development
			$dev = get_constant('DEVELOPMENT', true);

			if ($preloader)
				return $this->preloader($component, $startedTime);

			if ($this->skipCompile) {
				$render = [
					'content' => '',
					'extender' => $this->prepareExtender()
				];
			} else {
				if (!method_exists($this, 'render'))
					throw new Exception("Render function is required.");

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
		 * This allows us to perform other component.
		 *
		 * @param array $action
		 * @param mixed ...$args
		 * @return void
		 * @throws StreamException
		 */
		protected function extender(array $action, ...$args): void
		{
			[$class, $method] = $action + [null, null];

			if (!$class || !$method)
				throw new StreamException("Both class and method must be provided.");

			if (!class_exists($class))
				throw new StreamException("Class {$class} does not exist.");

			if (!method_exists($class, $method))
				throw new StreamException("Method {$method} does not exist.");

			$action[2] = $action[2] ?? $args;
			$this->extender[] = $action;
		}

		/**
		 * Smart action to run extender and exit at one action.
		 *
		 * @throws StreamException
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
			if (!$this->skipCompile) {
				$this->skipCompile = true;
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
			$loadBaseComponent = function() use ($data) {
				$path = base_path("/views/". str_replace(['.', '\\'], '/', get_called_class()) . ".blade.php");
				return Blade::load($path, $data);
			};

			$baseComponent = '';
			if (!$this->skipCompile) {
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
			if (is_object($callback)) {
				ob_start();
				echo $callback();
				return ob_get_clean();
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
			if ($this->extender) {
				$isSingleAction = isset($this->extender[0]) && is_string($this->extender[1] ?? null) && is_array($this->extender[2] ?? null);

				$prepare = function($action) {
					$class = $action[0] ?? '';
					$method = $action[1] ?? '';
					$args = $action[2] ?? [];

					if ($class && class_exists($class)) {

						if (self::class === $class)
							throw new Exception("Class `{$class}` is not allowed from extender.");

						if (!method_exists($class, $method))
							throw new Exception("Class `{$method}` is not allowed from extender.");

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

					throw new Exception("Stream Response: Class {$class} does not exist.");
				};

				if (!$isSingleAction) {
					foreach ($this->extender as $action_r) {
						$prepared = $prepare($action_r);
						if ($prepared) {
							$extender[] = $prepared;
						}
					}
				} else {
					$prepared = $prepare($this->extender);
					if ($prepared) {
						$extender[] = $prepared;
					}
				}
			}

			return $extender;
		}

		private function replaceHTML(string $html, string $component): string
		{
			$html = str_replace(['<>', '</>'], '', $html);
			return str_replace('StreamListener()', "StreamListener('$component')", $html);
		}
	}
