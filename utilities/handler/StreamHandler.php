<?php

	namespace App\Utilities\Handler;

	use App\Bootstrap\Exceptions\StreamException;
	use App\Utilities\Stream;
	use App\View\Compilers\Blade;
	use App\View\Compilers\scheme\CompilerException;

	final class StreamHandler
	{
		private array $action = [];
		private bool $renderOnly = false;
		private string $class = '';
		private array $extract = [];
		private bool $asynchronous = false;

		/**
		 * Class constructor.
		 *
		 * Initializes the stream with a given action and optional constructor parameters.
		 *
		 * @param string|array $action          The class and method to invoke, either as a string or [class, method, optional args].
		 * @param array        $constructParams Parameters to pass to the class constructor (if needed).
		 * @param bool         $asynchronous    Whether the stream should run asynchronously.
		 *
		 * @throws StreamException If the action is invalid or the target class/method does not exist.
		 */
		function __construct(string|array $action = '', array $constructParams = [], bool $asynchronous = false) {

			if (is_array($action)) {
				[$class, $method] = $action + [null, null];

				if (!$class || !$method) {
					throw new StreamException("Both class and method must be provided.");
				}

				if (!class_exists($class)) {
					throw new StreamException("Class {$class} does not exist.");
				}

				if (!method_exists($class, $method)) {
					throw new StreamException("Method {$method} does not exist.");
				}

				$this->renderOnly = true;
				$this->action = $action;

			} else {
				if (!empty($action)) {
					if (!class_exists($action)) {
						throw new StreamException("Class '$action' does not exist");
					}

					if (!is_subclass_of($action, Component::class)) {
						throw new StreamException("Class '$action' does not extend Component");
					}

					$this->renderOnly = true;
					$this->class = $action;
				}
			}

			$this->extract = $constructParams;
			$this->asynchronous = $asynchronous;
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
		 * @throws StreamException
		 */
		public function target(array $action, ...$argv): string
		{
			$class = $action[0] ?? null;
			$method = $action[1] ?? null;
			$isTarget = $action[2] ?? true;

			if (!class_exists($class)) {
				throw new StreamException('Class not found: ' . $class);
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
				if (method_exists($class, 'identifier')) {
					$identifier = $class::identifier();
					$wireTarget = 'wire:target="' . $identifier;
				} else {
					throw new StreamException("`identifier` method is required for target action '" . json_encode($action) . "'.");
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
		 * @throws StreamException
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
		 * @throws StreamException
		 */
		public function __toString(): string {
			if ($this->renderOnly) {
				return Blade::compile(Stream::render($this->class ?: $this->action, $this->extract, $this->asynchronous));
			}

			throw new StreamException("Cannot convert StreamHandler to string: no path specified during initialization.");
		}
	}