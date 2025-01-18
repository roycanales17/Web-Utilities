<?php

	namespace App\Utilities;

	use App\Utilities\Blueprints\BaseCarbon;

	class Carbon
	{
		public static function now(): BaseCarbon
		{
			return BaseCarbon::now();
		}

		public static function today(): string
		{
			return BaseCarbon::now()->toDateString();
		}

		public static function addDays(int $days): BaseCarbon
		{
			return BaseCarbon::now()->addDays($days);
		}

		public static function subtractDays(int $days): BaseCarbon
		{
			return BaseCarbon::now()->subDays($days);
		}

		public static function format(string $format = 'Y-m-d H:i:s'): string
		{
			return BaseCarbon::now()->format($format);
		}

		public static function parse(string $date): BaseCarbon
		{
			return BaseCarbon::parse($date);
		}

		public static function diffInDays(string $date1, string $date2): int
		{
			$carbonDate1 = BaseCarbon::parse($date1);
			$carbonDate2 = BaseCarbon::parse($date2);

			return $carbonDate1->diffInDays($carbonDate2);
		}

		public static function isFuture(string $date): bool
		{
			return BaseCarbon::parse($date)->isFuture();
		}

		public static function isPast(string $date): bool
		{
			return BaseCarbon::parse($date)->isPast();
		}
	}