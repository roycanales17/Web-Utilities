<?php

	namespace App\Utilities;

	use Closure;

	/**
	 * Buffer Utility Class
	 *
	 * Static utility class for immediate output flushing to the browser.
	 * Useful for real-time progress updates, streaming data, or long-running scripts.
	 */
	class Buffer
	{
		private static bool $initialized = false;

		/**
		 * Configure automatic flushing
		 *
		 * @param bool $auto_flush Whether to enable implicit flushing (default: true)
		 * @return void
		 */
		public static function configure(bool $auto_flush = true): void {
			ob_implicit_flush($auto_flush);
		}

		/**
		 * Clear all existing output buffers
		 *
		 * @return int Number of buffers cleared
		 */
		public static function clear(): int {
			$count = 0;
			while (ob_get_level() > 0) {
				ob_end_flush();
				$count++;
			}
			return $count;
		}

		/**
		 * Flush all output buffers
		 *
		 * @return void
		 */
		public static function flush(): void {
			if (ob_get_level() > 0) {
				ob_flush();
			}
			flush();
		}

		/**
		 * Flash/clear all previously printed content
		 * Sends JavaScript to clear the page content (for web browsers)
		 * or ANSI escape codes (for CLI)
		 *
		 * @return void
		 */
		public static function flash(): void {
			// Clear PHP output buffers
			self::clear();

			// Detect if running in CLI or web context
			if (php_sapi_name() === 'cli') {
				// For CLI, use ANSI escape codes to clear the screen
				echo "\033[2J\033[H";
			} else {
				echo "<script>
				if (document.body) {
					document.body.innerHTML = '';
				} else if (document.documentElement) {
					// If body doesn't exist yet, clear the document element
					var html = document.documentElement;
					while (html.firstChild) {
						html.removeChild(html.firstChild);
					}
				}
			</script>";
			}

			self::flush();
		}

		/**
		 * Print content immediately with optional formatting
		 * Automatically initializes headers and clears buffers on first call
		 *
		 * @param string $content Content to print (supports sprintf formatting)
		 * @param mixed ...$args Optional arguments for sprintf formatting
		 * @return void
		 */
		public static function print(string $content, ...$args): void {
			// Auto-initialize on the first call
			if (!self::$initialized) {
				if (!headers_sent()) {
					header("X-Accel-Buffering: no");
				}
				self::clear();
				self::$initialized = true;
			}

			// Format content if arguments provided
			if (!empty($args)) {
				$content = sprintf($content, ...$args);
			}

			echo $content;
			self::flush();
		}

		/**
		 * Check if output buffering is active
		 *
		 * @return bool True if output buffering is active
		 */
		public static function is_buffering(): bool {
			return ob_get_level() > 0;
		}

		/**
		 * Get current output buffer level
		 *
		 * @return int Number of active output buffers
		 */
		public static function buffer_level(): int {
			return ob_get_level();
		}

		/**
		 * Capture the output of a callback function
		 *
		 * @param Closure $callback
		 * @return mixed
		 */
		public static function capture(Closure $callback): mixed {
			ob_start();
			echo $callback();
			return ob_get_clean();
		}
	}