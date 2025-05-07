<?php

	namespace App\Utilities;

	use App\Content\Blade;
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
		 * Placeholder function, coming soon.
		 *
		 * @return string A message indicating the feature is under development.
		 */
		public function preloader(): string
		{
			return 'Ajax requests';
		}

		/**
		 * Parse the component's render output and return a formatted HTML string with data attributes.
		 *
		 * @deprecated Do not use this function.
		 * @param string $identifier Optional identifier for the component.
		 * @param float $startedTime Time when the component started.
		 * @return string The rendered component wrapped in a <fragment> element with data attributes.
		 */
		public function parse(string $identifier = '', float $startedTime = 0): string
		{
			$html = str_replace(['<>', '</>'], '', $this->render());

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
		 * This function looks for view files with different extensions and renders the first one found.
		 *
		 * @param array $data Data to be passed to the view for rendering.
		 * @param bool $asynchronous Whether the rendering should be asynchronous.
		 * @return string The rendered HTML content.
		 */
		protected function compile(array $data = [], bool $asynchronous = false): string
		{
			ob_start();

			// Normalize the component path.
			$root = "../";
			$path = str_replace(['.', '\\'], '/', get_called_class());
			$index = dirname($path) . '/index';
			$extensions = ['.blade.php', '.php', '.html'];

			// Find the first existing file with the appropriate extension.
			foreach ($extensions as $ext) {
				if (file_exists($root . $index . $ext)) {
					$skeleton = $index . $ext;
					break;
				}
			}

			// Render the found file if it exists.
			if (isset($skeleton)) {
				Blade::render($skeleton, extract: $data);
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
