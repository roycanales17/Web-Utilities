<?php

	namespace App\Utilities\Blueprints;

	use App\Utilities\Handler\BaseCarbon;

	/**
	 * Class BaseCarbon
	 *
	 * A blueprint wrapper around CarbonHandler providing convenient static methods
	 * for date manipulation and retrieval.
	 *
	 * @internal
	 * @package App\Utilities\Blueprints
	 */
	class BaseBaseCarbon extends BaseCarbon
	{
		/**
		 * Get the current date and time as a BaseCarbon instance.
		 *
		 * @return static
		 */
		public static function now(): self
		{
			return self::parse('now');
		}

		/**
		 * Parse a date string and return a BaseCarbon instance.
		 *
		 * @param string $date The date string to parse.
		 * @return static
		 */
		public static function parse(string $date): self
		{
			return new self($date);
		}

		/**
		 * Get today's date as a string (Y-m-d format).
		 *
		 * @return string
		 */
		public static function today(): string
		{
			return self::parse('today')->toDateString();
		}
	}
