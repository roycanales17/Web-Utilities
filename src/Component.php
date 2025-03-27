<?php

	namespace App\Utilities;

	use ReflectionClass;
	use ReflectionProperty;

	abstract class Component {

		private string $componentIdentifier = '';
		private float $startedTime = 0;
		private static array $registered = [];

		public function initialize(string $component, array $params = []): void  {

			$this->startedTime = hrtime(true);
			$timeIdentifier = substr($this->startedTime, -5);
			$identifier = encrypt("COMPONENT_" . $component) . "-{".$timeIdentifier."}";
			$existing = array_filter(self::$registered, fn($id) => str_starts_with($id, $identifier . "-["));

			if (empty($existing)) {
				$newIdentifier = $identifier . "-[1]";
			} else {
				preg_match('/\[(\d+)\]$/', end($existing), $matches);
				$newIdentifier = preg_replace('/\[\d+\]$/', "[" . (intval($matches[1]) + 1) . "]", end($existing)) ?? $identifier . "-[2]";
			}

			self::$registered[] = $this->componentIdentifier =  $newIdentifier;
			if ($params && method_exists($this, 'init'))
				$this->init(...$params);
		}

		public function models(array $models): void {
			foreach ($models as $key => $value) {
				if (property_exists($this, $key)) {
					$this->$key = $value;
				}
			}
		}

		public function preloader(): string {
			return 'Ajax requests';
		}

		public function parse(string $identifier = '', float $startedTime = 0): string
		{
			$dataAttributes = '';
			$html = str_replace(['<>','</>'], '', $this->render());
			$timeDuration = hrtime(true) - ($identifier ? $startedTime: $this->startedTime);
			$timeMilliseconds = $timeDuration / 1_000_000;
			$component = $identifier ?: base64_encode($this->componentIdentifier);
			$properties = base64_encode(encrypt(json_encode($this->fetchProperties())));

			foreach ([
						 'component' => $component,
						 'duration' => sprintf('%.2f', $timeMilliseconds),
						 'properties' => $properties,
						 'original' => json_encode($this->fetchProperties())
					 ] as $key => $value) {
				$dataAttributes .= " data-".htmlspecialchars($key)."='".htmlspecialchars($value, ENT_QUOTES)."'";
			}

			return <<<HTML
			    <fragment class='component-container' $dataAttributes>
			    	$html 
			    	<script>new stream("$component");</script>
			    </fragment>
			HTML;
		}

		protected function print(mixed $callback): mixed {
			if (is_object($callback)) {
				ob_start();
				echo($callback());
				return ob_get_clean();
			}

			return $callback;
		}

		private function fetchProperties(): array {
			$properties = [];
			$reflection = new ReflectionClass($this);
			$publicProperties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

			foreach ($publicProperties as $property) {
				$properties[$property->getName()] = $property->getValue($this);
			}

			return $properties;
		}
	}