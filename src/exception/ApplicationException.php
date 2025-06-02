<?php

	namespace App\Utilities\Exception;

	use Exception;
	use Throwable;

	class ApplicationException extends Exception
	{
		protected string $context;
		protected array $meta;

		public function __construct(
			string $message = '',
			int $code = 0,
			string $context = 'application',
			array $meta = [],
			Throwable $previous = null
		) {
			$this->context = $context;
			$this->meta = $meta;

			parent::__construct($message, $code, $previous);
		}

		public function getContext(): string
		{
			return $this->context;
		}

		public function getMeta(): array
		{
			return $this->meta;
		}

		public function toArray(): array
		{
			return [
				'error'   => true,
				'type'    => static::class,
				'message' => $this->getMessage(),
				'code'    => $this->getCode(),
				'context' => $this->getContext(),
				'meta'    => $this->getMeta(),
			];
		}
	}
