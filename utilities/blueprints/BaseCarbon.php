<?php

	namespace App\utilities\Blueprints;

	use App\utilities\Handler\CarbonHandler;

	class BaseCarbon extends CarbonHandler
	{
		public static function now(): self
		{
			return self::parse('now');
		}

		public static function parse(string $date): self
		{
			return new self($date);
		}

		public static function today(): string
		{
			return self::parse('today')->toDateString();
		}
	}