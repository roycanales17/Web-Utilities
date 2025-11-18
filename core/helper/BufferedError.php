<?php

	namespace App\Bootstrap\Helper;

	/**
	 * @internal
	 *
	 * Provides a simple buffered error system that allows a class
	 * to capture an error and check it later without immediately
	 * throwing an exception.
	 */
	trait BufferedError
	{
		/**
		 * Error message buffer.
		 *
		 * @var string
		 */
		private string $message = '';

		/**
		 * Error code buffer.
		 *
		 * @var int
		 */
		private int $code = 0;

		/**
		 * Buffer an error message and code.
		 *
		 * @param string $message
		 * @param int    $code
		 * @return void
		 */
		protected function throwError(string $message, int $code = 500): void
		{
			$this->message = $message;
			$this->code = $code;
		}

		/**
		 * Determine if an error is currently buffered.
		 *
		 * @return bool
		 */
		protected function isBufferedError(): bool
		{
			return $this->code !== 0 && $this->code !== 200;
		}

		/**
		 * Get the buffered error message.
		 *
		 * @return string
		 */
		protected function getErrorMessage(): string
		{
			return $this->message;
		}

		/**
		 * Get the buffered error code.
		 *
		 * @return int
		 */
		protected function getErrorCode(): int
		{
			return $this->code;
		}
	}