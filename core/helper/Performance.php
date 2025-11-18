<?php

	namespace App\Bootstrap\Helper;

	use Exception;

	/**
	 * @internal
	 *
	 * Simple utility to measure execution time and memory usage
	 * within specific sections of code. Useful for profiling and
	 * debugging internal processes during development.
	 *
	 * Usage:
	 *   $perf = new Performance(true); // auto-start
	 *   // ... run some logic
	 *   $perf->end();
	 *   $summary = $perf->generateSummary();
	 *
	 * Returned Summary:
	 *   [
	 *       'executionTime' => float,  // seconds
	 *       'memoryUsage'   => int,    // bytes
	 *       'peakMemory'    => int     // bytes
	 *   ]
	 */
	final class Performance
	{
		/**
		 * Start timestamp (microseconds).
		 *
		 * @var float
		 */
		private float $startTime = 0.0;

		/**
		 * Memory usage at start (bytes).
		 *
		 * @var int
		 */
		private int $startMemory = 0;

		/**
		 * End timestamp (microseconds).
		 *
		 * @var float
		 */
		private float $endTime = 0.0;

		/**
		 * Memory usage at end (bytes).
		 *
		 * @var int
		 */
		private int $endMemory = 0;

		/**
		 * @param bool $autoStart Whether to automatically call start()
		 */
		public function __construct(bool $autoStart = false)
		{
			if ($autoStart) {
				$this->start();
			}
		}

		/**
		 * Start performance measurement.
		 *
		 * @return void
		 */
		public function start(): void
		{
			$this->startTime = microtime(true);
			$this->startMemory = memory_get_usage();
		}

		/**
		 * End performance measurement.
		 *
		 * @return void
		 */
		public function end(): void
		{
			$this->endTime = microtime(true);
			$this->endMemory = memory_get_usage();
		}

		/**
		 * Generate a performance summary.
		 *
		 * @return array{
		 *     executionTime: float,
		 *     memoryUsage: int,
		 *     peakMemory: int
		 * }
		 * @throws Exception
		 */
		public function generateSummary(): array
		{
			if ($this->endTime === 0.0) {
				throw new Exception("You must call end() before generating the summary.");
			}

			return [
				'executionTime' => $this->getExecutionTime(),
				'memoryUsage'   => $this->getMemoryUsage(),
				'peakMemory'    => $this->getPeakMemory(),
			];
		}

		/**
		 * Get the measured execution time in seconds.
		 */
		public function getExecutionTime(): float
		{
			return $this->endTime - $this->startTime;
		}

		/**
		 * Get the memory used between start() and end(), in bytes.
		 */
		public function getMemoryUsage(): int
		{
			return $this->endMemory - $this->startMemory;
		}

		/**
		 * Get the peak memory usage during the entire request.
		 */
		public function getPeakMemory(): int
		{
			return memory_get_peak_usage();
		}
	}
