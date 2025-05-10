<?php

	namespace App\Utilities;

	use App\Content\Blade;
	use Exception;
	use ReflectionClass;
	use ReflectionProperty;

	abstract class Component
	{
		private string $componentIdentifier = '';  // Stores a unique identifier for the component.
		private float $startedTime = 0;  // Tracks the time when the component was initialized.
		private static array $registered = [];  // Holds a list of all registered component identifiers.

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
		private function preloader(): string
		{
			if (!method_exists($this, 'loader'))
				throw new Exception('Loader function is required.');

			$dataAttributes = '';
			$componentId = $this->componentIdentifier;

			foreach (['component' => $componentId] as $key => $value) {
				$dataAttributes .= " data-" . htmlspecialchars($key) . "='" . htmlspecialchars($value, ENT_QUOTES) . "'";
			}

			$html = $this->loader();

			return <<<HTML
			<fragment class='component-container' {$dataAttributes}>
				{$html}
				<script>
					(function() {
						const token = document.querySelector('meta[name="csrf-token"]').getAttribute("content");
						const component = document.querySelector("[data-component='{$componentId}']");
						if (!component) return;
			
						const form = new FormData();
						form.append('_component', '$componentId');
			
						fetch("/api/stream-wire/{$componentId}", {
							method: "POST",
							headers: {
								"X-STREAM-WIRE": "true",
								"X-CSRF-TOKEN": token
							},
							body: form
						})
						.then(response => {
							if (!response.ok) {
								console.error(
									`%c❌ HTTP ERROR! %cStatus: \${response.status} 🚫`,
									'color: red; font-weight: bold;',
									'color: orange;'
								);
								if (response.status === 500) {
									response.text().then(errorHtml => {
										component.innerHTML += errorHtml;
									});
								}
								return null;
							}
				
							return response.text();
						})
						.then(html => {
							const wrapper = document.createElement('div');
							wrapper.innerHTML = html;
							const newFragment = wrapper.querySelector("[data-component='{$componentId}']");
							if (newFragment) {
								component.replaceWith(newFragment);
								newFragment.querySelectorAll('script').forEach(oldScript => {
									const newScript = document.createElement('script');
									if (oldScript.src) {
										newScript.src = oldScript.src;
									} else {
										newScript.textContent = oldScript.textContent;
									}
									document.head.appendChild(newScript).remove();
								});
							}
						})
						.catch(error => {
							console.error("Fetch error:", error);
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
			$reflection = new ReflectionClass($this);
			$publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

			$properties = [];
			foreach ($publicProperties as $property) {
				$properties[$property->getName()] = $property->getValue($this);
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
			foreach ($models as $key => $value) {
				if (property_exists($this, $key)) {
					$this->$key = $value;
				}
			}
		}


		/**
		 * Parse the component's render output and return a formatted HTML string with data attributes.
		 *
		 * @deprecated Do not use this function.
		 * @param string $identifier Optional identifier for the component.
		 * @param float $startedTime Time when the component started.
		 * @return string The rendered component wrapped in a <fragment> element with data attributes.
		 * @throws Exception
		 */
		public function parse(string $identifier = '', float $startedTime = 0, bool $preloader = false): string
		{
			if ($preloader)
				return $this->preloader();

			if (!method_exists($this, 'render'))
				throw new Exception("Render function is required.");

			// Calculate the duration of the component rendering.
			$duration = hrtime(true) - ($identifier ? $startedTime : $this->startedTime);
			$durationMs = $duration / 1_000_000;

			// Prepare data attributes for the component.
			$component = $identifier ?: base64_encode($this->componentIdentifier);
			$properties = $this->fetchProperties();
			$dataAttributes = '';

			foreach ([
			 	'component' => $component,
			 	'duration' => sprintf('%.2f', $durationMs),
			 	'properties' => base64_encode(encrypt(json_encode($properties)))
			] as $key => $value) {
				$dataAttributes .= " data-" . htmlspecialchars($key) . "='" . htmlspecialchars($value, ENT_QUOTES) . "'";
			}

			if (property_exists($this, 'target')) {
				$dataAttributes .= " data-id='" . $this->target . "'";
			}

			$html = str_replace(['<>', '</>'], '', $this->render());
			return <<<HTML
			<fragment class='component-container'{$dataAttributes}>
				{$html}
				<script type="module">
					import {init} from '../resources/libraries/streamdom/stream-wire.js';
					init("{$component}");
				</script>
			</fragment>

			HTML;
		}

		/**
		 * Compiles and returns the content of the view associated with the component.
		 * This function looks for the `index` file in the same directory as the class and renders
		 * the first file it finds with the extensions `.blade.php`, `.php`, or `.html`.
		 * It is useful for components where the view files are stored within the same directory.
		 *
		 * @param array $data Data to be passed to the view for rendering.
		 * @param string $blade Use to render the interface within the component directory.
		 * @return string The rendered HTML content from the matched view file.
		 */
		protected function compile(array $data = [], string $blade = 'index'): string
		{
			ob_start();

			// Set the root directory and determine the path of the class file
			$root = "../";
			$path = str_replace(['.', '\\'], '/', get_called_class());

			// Normalize path
			$bladePath = str_replace('.php', '.blade.php', "/{$blade}");

			// The index file is expected to be in the same directory as the class file
			$index = dirname($path) . "/$bladePath";

			// Define the possible file extensions for the view
			$extensions = ['.blade.php', '.php', '.html'];

			// Check each extension to see if the file exists in the directory
			foreach ($extensions as $ext) {
				if (file_exists($root . $index . $ext)) {
					// If a matching file is found, set it as the skeleton to render
					$skeleton = $index . $ext;
					break;
				}
			}

			// Render the matched skeleton (view) file, passing the extracted data
			if (isset($skeleton)) {
				Blade::render($skeleton, extract: $data, onError: function ($trace) {
					throw new Exception("{$trace['message']} in `{$trace['path']}`, line: `{$trace['line']}`", $trace['code']);
				});
			}

			return ob_get_clean();
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
	}
