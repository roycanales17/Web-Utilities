<?php

	namespace App\Utilities;

	use App\Utilities\Blueprints\BaseBaseCarbon;

	/**
	 * Utility wrapper around BaseCarbon with helper methods.
	 */
	final class Carbon
	{
		/**
		 * Get the current date and time.
		 *
		 * @return BaseBaseCarbon
		 */
		public static function now(): BaseBaseCarbon
		{
			return BaseBaseCarbon::now();
		}

		/**
		 * Get today's date as a formatted string.
		 *
		 * @return string
		 */
		public static function today(): string
		{
			return BaseBaseCarbon::now()->toDateString();
		}

		/**
		 * Add days to the current date.
		 *
		 * @param int $days
		 * @return BaseBaseCarbon
		 */
		public static function addDays(int $days): BaseBaseCarbon
		{
			return BaseBaseCarbon::now()->addDays($days);
		}

		/**
		 * Subtract days from the current date.
		 *
		 * @param int $days
		 * @return BaseBaseCarbon
		 */
		public static function subtractDays(int $days): BaseBaseCarbon
		{
			return BaseBaseCarbon::now()->subDays($days);
		}

		/**
		 * Get the current date and time in a custom format.
		 *
		 * @param string $format
		 * @return string
		 */
		public static function format(string $format = 'Y-m-d H:i:s'): string
		{
			return BaseBaseCarbon::now()->format($format);
		}

		/**
		 * Parse a date string into a BaseCarbon instance.
		 *
		 * @param string $date
		 * @return BaseBaseCarbon
		 */
		public static function parse(string $date): BaseBaseCarbon
		{
			return BaseBaseCarbon::parse($date);
		}

		/**
		 * Get the difference in days between two dates.
		 *
		 * @param string $date1
		 * @param string $date2
		 * @return int
		 */
		public static function diffInDays(string $date1, string $date2): int
		{
			$carbonDate1 = BaseBaseCarbon::parse($date1);
			$carbonDate2 = BaseBaseCarbon::parse($date2);

			return $carbonDate1->diffInDays($carbonDate2);
		}

		/**
		 * Check if a date is in the future.
		 *
		 * @param string $date
		 * @return bool
		 */
		public static function isFuture(string $date): bool
		{
			return BaseBaseCarbon::parse($date)->isFuture();
		}

		/**
		 * Check if a date is in the past.
		 *
		 * @param string $date
		 * @return bool
		 */
		public static function isPast(string $date): bool
		{
			return BaseBaseCarbon::parse($date)->isPast();
		}
	}
