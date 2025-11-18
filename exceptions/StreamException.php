<?php

	namespace App\Bootstrap\Exceptions;

	use Exception;
	use Throwable;

	/**
	 * @internal
	 *
	 * Represents a stream-related exception with additional context.
	 */
	class StreamException extends Exception
	{
		/**
		 * Additional context values useful for debugging.
		 *
		 * @var array
		 */
		protected array $context = [];

		/**
		 * @param string         $message
		 * @param int            $code
		 * @param array          $context   Additional details (debugging)
		 * @param Throwable|null $previous
		 */
		public function __construct(string $message = "", int $code = 0, array $context = [], Throwable $previous = null)
		{
			$this->context = $context;

			parent::__construct($message, $code, $previous);
		}

		/**
		 * Returns the debugging context stored with the exception.
		 *
		 * @return array
		 */
		public function getContext(): array
		{
			return $this->context;
		}
	}
