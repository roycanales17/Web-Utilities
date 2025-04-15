<?php

	namespace App\Utilities;

	use ReflectionClass;
	use ReflectionProperty;

	abstract class Component
	{
		private string $componentIdentifier = '';
		private float $startedTime = 0;
		private static array $registered = [];

		public function initialize(string $component, array $params = []): void
		{
			$this->startedTime = hrtime(true);
			$this->componentIdentifier = $this->generateComponentIdentifier($component);
			self::$registered[] = $this->componentIdentifier;

			if (!empty($params) && method_exists($this, 'init')) {
				$this->init(...$params);
			}
		}

		private function generateComponentIdentifier(string $component): string
		{
			$timeIdentifier = substr(hrtime(true), -5);
			$baseId = encrypt("COMPONENT_" . $component) . "-{" . $timeIdentifier . "}";
			$existing = array_filter(
				self::$registered,
				fn($id) => str_starts_with($id, $baseId . "-[")
			);

			if (empty($existing)) {
				return $baseId . "-[1]";
			}

			preg_match('/\[(\d+)\]$/', end($existing), $matches);
			$count = isset($matches[1]) ? ((int)$matches[1] + 1) : 2;

			return preg_replace('/\[\d+\]$/', "[$count]", end($existing)) ?: $baseId . "-[$count]";
		}

		public function models(array $models): void
		{
			foreach ($models as $key => $value) {
				if (property_exists($this, $key)) {
					$this->$key = $value;
				}
			}
		}

		public function preloader(): string
		{
			return 'Ajax requests';
		}

		public function parse(string $identifier = '', float $startedTime = 0): string
		{
			$html = str_replace(['<>', '</>'], '', $this->render());

			$duration = hrtime(true) - ($identifier ? $startedTime : $this->startedTime);
			$durationMs = $duration / 1_000_000;

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

		protected function print(mixed $callback): mixed
		{
			if (is_object($callback)) {
				ob_start();
				echo $callback();
				return ob_get_clean();
			}

			return $callback;
		}

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
	}
