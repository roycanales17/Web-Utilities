<?php

	namespace App\Utilities\Handler;

	use DateTime;
	use DateTimeZone;

	/**
	 * Class BaseCarbon
	 *
	 * Abstract base class providing a lightweight, chainable wrapper around PHP's DateTime.
	 * Provides methods for date manipulation, comparison, and formatting.
	 *
	 * Example usage:
	 *   $date = new MyCarbon('2025-11-19', 'Asia/Manila');
	 *   $date->addDays(5)->startOfDay()->format(); // '2025-11-24 00:00:00'
	 */
	abstract class BaseCarbon
	{
		/**
		 * The underlying DateTime object.
		 *
		 * @var DateTime
		 */
		protected DateTime $date;

		/**
		 * Timezone for the DateTime object.
		 *
		 * @var string
		 */
		protected string $timezone;

		/**
		 * BaseCarbon constructor.
		 *
		 * @param string $time      The initial time (default 'now').
		 * @param string $timezone  The timezone (default 'UTC').
		 */
		public function __construct(string $time = 'now', string $timezone = 'UTC')
		{
			$this->registerDate($time, $timezone);
		}

		/**
		 * Add days to the current date.
		 *
		 * @param int $days
		 * @return $this
		 */
		public function addDays(int $days): self
		{
			$this->getDateTime()->modify("+{$days} days");
			return $this;
		}

		/**
		 * Add months to the current date.
		 *
		 * @param int $months
		 * @return static
		 */
		public function addMonths(int $months): static
		{
			$this->getDateTime()->modify("+{$months} months");
			return $this;
		}

		/**
		 * Add years to the current date.
		 *
		 * @param int $years
		 * @return static
		 */
		public function addYears(int $years): static
		{
			$this->getDateTime()->modify("+{$years} years");
			return $this;
		}

		/**
		 * Subtract days from the current date.
		 *
		 * @param int $days
		 * @return $this
		 */
		public function subDays(int $days): self
		{
			$this->getDateTime()->modify("-{$days} days");
			return $this;
		}

		/**
		 * Subtract months from the current date.
		 *
		 * @param int $months
		 * @return static
		 */
		public function subMonths(int $months): static
		{
			$this->getDateTime()->modify("-{$months} months");
			return $this;
		}

		/**
		 * Subtract years from the current date.
		 *
		 * @param int $years
		 * @return static
		 */
		public function subYears(int $years): static
		{
			$this->getDateTime()->modify("-{$years} years");
			return $this;
		}

		/**
		 * Add seconds to the current date.
		 *
		 * @param int $seconds
		 * @return $this
		 */
		public function addSeconds(int $seconds): self
		{
			$this->getDateTime()->modify("+{$seconds} seconds");
			return $this;
		}

		/**
		 * Subtract seconds from the current date.
		 *
		 * @param int $seconds
		 * @return $this
		 */
		public function subSeconds(int $seconds): self
		{
			$this->getDateTime()->modify("-{$seconds} seconds");
			return $this;
		}

		/**
		 * Format the date using a PHP date format string.
		 *
		 * @param string $format
		 * @return string
		 */
		public function format(string $format = 'Y-m-d H:i:s'): string
		{
			return $this->getDateTime()->format($format);
		}

		/**
		 * Get the date as a 'Y-m-d' string.
		 *
		 * @return string
		 */
		public function toDateString(): string
		{
			return $this->getDateTime()->format('Y-m-d');
		}

		/**
		 * Get the time as an 'H:i:s' string.
		 *
		 * @return string
		 */
		public function toTimeString(): string
		{
			return $this->getDateTime()->format('H:i:s');
		}

		/**
		 * Get the difference in days between this and another BaseCarbon instance.
		 *
		 * @param self $other
		 * @return int
		 */
		public function diffInDays(self $other): int
		{
			$interval = $this->getDateTime()->diff($other->getDateTime());
			return abs($interval->days);
		}

		/**
		 * Get the difference in months between this and another BaseCarbon instance.
		 *
		 * @param self $other
		 * @return int
		 */
		public function diffInMonths(self $other): int
		{
			$interval = $this->getDateTime()->diff($other->getDateTime());
			return abs($interval->m + ($interval->y * 12));
		}

		/**
		 * Check if the date is in the future compared to now.
		 *
		 * @return bool
		 */
		public function isFuture(): bool
		{
			return $this->getDateTime() > new DateTime('now', new DateTimeZone($this->timezone));
		}

		/**
		 * Check if the date is in the past compared to now.
		 *
		 * @return bool
		 */
		public function isPast(): bool
		{
			return $this->getDateTime() < new DateTime('now', new DateTimeZone($this->timezone));
		}

		/**
		 * Set the time to the start of the day (00:00:00).
		 *
		 * @return static
		 */
		public function startOfDay(): static
		{
			$this->getDateTime()->setTime(0, 0, 0);
			return $this;
		}

		/**
		 * Set the time to the end of the day (23:59:59).
		 *
		 * @return static
		 */
		public function endOfDay(): static
		{
			$this->getDateTime()->setTime(23, 59, 59);
			return $this;
		}

		/**
		 * Get the underlying DateTime object.
		 *
		 * @return DateTime
		 */
		public function getDateTime(): DateTime
		{
			return $this->date;
		}

		/**
		 * Initialize the DateTime object and timezone.
		 *
		 * @param string $time
		 * @param string $timezone
		 * @return void
		 */
		private function registerDate(string $time, string $timezone): void
		{
			$this->date = new DateTime($time, new DateTimeZone($timezone));
			$this->timezone = $timezone;
		}
	}