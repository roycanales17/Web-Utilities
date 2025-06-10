<?php

	namespace App\utilities\Handler;

	use App\Utilities\Component;
	use App\Utilities\Stream;
	use App\View\Compilers\Blade;
	use App\View\Compilers\scheme\CompilerException;
	use Exception;

	final class StreamHandler
	{
		private bool $renderOnly = false;
		private string $path = '';
		private array $extract = [];
		private bool $asynchronous = false;

		function __construct(string $path = '', array $data = [], bool $asynchronous = false) {
			if (trim($path)) {
				$this->renderOnly = true;
				$this->path = $path;
				$this->extract = $data;
				$this->asynchronous = $asynchronous;
			}
		}

		/**
		 * This performs the other component request.
		 * NOTE: The returned string should be output raw in Blade with {!! !!} to avoid HTML escaping.
		 *
		 * Example usage:
		 * <button wire:click='{!! target([MyClass::class, "method"], $arg) !!}'></button>
		 *
		 * @param array $action [className, methodName]
		 * @param mixed ...$argv Method arguments
		 * @return string generated wire attributes
		 * @throws Exception
		 */
		public function target(array $action, ...$argv): string
		{
			$class = $action[0] ?? null;
			$method = $action[1] ?? null;
			$isTarget = $action[2] ?? true;

			if (!class_exists($class)) {
				throw new Exception('Class not found: ' . $class);
			}

			if (!is_string($method) || !method_exists($class, $method)) {
				throw new \InvalidArgumentException("Invalid target action method '{$method}'.");
			}

			$encodedArgs = [];
			foreach ($argv as $arg) {
				if (is_array($arg)) {
					$json = json_encode($arg);
					$json = str_replace('"', "'", $json);
					$jsonArg = trim($json, '"');
				} else {
					$jsonArg = json_encode($arg, JSON_UNESCAPED_SLASHES);
					if (is_string($arg)) {
						$jsonArg = "'" . trim($jsonArg, '"') . "'";
					}
				}
				$encodedArgs[] = $jsonArg;
			}
			$argsString = implode(', ', $encodedArgs);

			/** @var Component $class */
			if ($isTarget) {
				if (method_exists($class, 'getIdentifier')) {
					$identifier = $class::getIdentifier();
					$wireTarget = 'wire:target="' . $identifier;
				} else {
					throw new Exception("`getIdentifier` method is required for target action '" . json_encode($action) . "'.");
				}
			}

			return $method . '(' . $argsString . ')" ' . ($wireTarget ?? '');
		}

		/**
		 * This performs the component function request itself.
		 * NOTE: The returned string should be output raw in Blade with {!! !!} to avoid HTML escaping.
		 *
		 * Example usage:
		 * <button wire:click='{!! execute([MyClass::class, "method"], $arg) !!}'></button>
		 *
		 * @param array $action [className, methodName]
		 * @param mixed ...$argv Method arguments
		 * @return string generated wire attributes
		 * @throws Exception
		 */
		public function execute(array $action, ...$argv): string
		{
			$action[2] = false;
			return $this->target($action, ...$argv);
		}

		/**
		 * This returns the component interface.
		 *
		 * @throws CompilerException
		 * @throws Exception
		 */
		public function __toString(): string {
			if ($this->renderOnly) {
				return Blade::compile(Stream::render($this->path, $this->extract, $this->asynchronous));
			}

			throw new Exception("Cannot convert StreamHandler to string: no path specified during initialization.");
		}
	}