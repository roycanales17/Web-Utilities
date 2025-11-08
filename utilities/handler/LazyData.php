<?php

	namespace App\Utilities\Handler;

	use IteratorAggregate;
	use Traversable;

	final class LazyData implements IteratorAggregate
	{
		protected $callback;
		protected $data;

		/**
		 * Constructor.
		 *
		 * @param callable $callback A function that returns the data when called.
		 */
		public function __construct(callable $callback)
		{
			$this->callback = $callback;
		}

		/**
		 * Get the data, evaluating the callback only once.
		 *
		 * @return mixed
		 */
		public function get(): mixed
		{
			if (!isset($this->data)) {
				$this->data = ($this->callback)();
			}

			return $this->data;
		}

		/**
		 * IteratorAggregate interface method
		 *
		 * Allows the object to be used directly in foreach loops.
		 *
		 * @return Traversable
		 */
		public function getIterator(): Traversable
		{
			$data = $this->get();

			// If it’s already traversable, yield from it
			if ($data instanceof Traversable) {
				yield from $data;
			} elseif (is_array($data)) {
				yield from $data;
			} else {
				// If it's a single value, wrap it in array
				yield $data;
			}
		}
	}
