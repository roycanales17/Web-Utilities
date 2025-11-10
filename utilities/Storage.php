<?php

	namespace App\Utilities;

	use App\Utilities\Blueprints\BaseFileUpload;
	use DateTimeInterface;

	/**
	 * Class Storage
	 *
	 * Provides a fluent interface for interacting with the filesystem.
	 *
	 * @package App\Utilities
	 */
	final class Storage
	{
		private static ?array $disk = null;

		/**
		 * Configure the default storage path.
		 *
		 * @param string $defaultPath
		 * @return void
		 */
		public static function configure(string $defaultPath = 'storage'): void
		{
			BaseFileUpload::setStoragePath($defaultPath);
		}

		/**
		 * Get a new instance of the storage handler.
		 *
		 * @param string $disk
		 * @return BaseFileUpload
		 */
		public static function disk(string $disk = 'local'): BaseFileUpload
		{
			if (!isset(self::$disk[$disk])) {
				self::$disk[$disk] = new BaseFileUpload($disk);
			}

			return self::$disk[$disk];
		}

		/**
		 * Store a file at the specified path.
		 */
		public static function put(string $path, string $contents, string $disk = 'local'): bool
		{
			return self::disk($disk)->put($path, $contents);
		}

		/**
		 * Retrieve the contents of a file.
		 */
		public static function get(string $path, string $disk = 'local'): ?string
		{
			return self::disk($disk)->get($path);
		}

		/**
		 * Check if a file exists.
		 */
		public static function exists(string $path, string $disk = 'local'): bool
		{
			return self::disk($disk)->exists($path);
		}

		/**
		 * Delete a file.
		 */
		public static function delete(string $path, string $disk = 'local'): bool
		{
			return self::disk($disk)->delete($path);
		}

		/**
		 * Copy a file to a new location.
		 */
		public static function copy(string $from, string $to, string $disk = 'local'): bool
		{
			return self::disk($disk)->copy($from, $to);
		}

		/**
		 * Move a file to a new location.
		 */
		public static function move(string $from, string $to, string $disk = 'local'): bool
		{
			return self::disk($disk)->move($from, $to);
		}

		/**
		 * Get the file size in bytes.
		 */
		public static function size(string $path, string $disk = 'local'): int
		{
			return self::disk($disk)->size($path);
		}

		/**
		 * Get the last modified time of a file.
		 */
		public static function lastModified(string $path, string $disk = 'local'): int
		{
			return self::disk($disk)->lastModified($path);
		}

		/**
		 * Get the public URL for a file.
		 */
		public static function url(string $path, string $disk = 'local'): string
		{
			return self::disk($disk)->url($path);
		}

		/**
		 * Get a temporary URL for a file with an expiration date.
		 */
		public static function temporaryUrl(string $path, DateTimeInterface $expiration, string $disk = 'local'): string
		{
			return self::disk($disk)->temporaryUrl($path, $expiration);
		}

		/**
		 * Validate a temporary URL.
		 *
		 * @param string $path
		 * @param int $expires
		 * @param string $signature
		 * @return bool
		 */
		public static function validateTemporaryUrl(string $path, int $expires, string $signature): bool
		{
			if ($expires < time()) {
				return false; // expired
			}

			$secretKey = env('APP_KEY', 'fallback-secret');
			$data = "{$path}|{$expires}";
			$expectedSignature = hash_hmac('sha256', $data, $secretKey);

			return hash_equals($expectedSignature, $signature);
		}

		/**
		 * Serve a temporary file if the signature and expiration are valid.
		 *
		 * @param string $path
		 * @param array $query
		 * @param string $disk
		 * @return mixed
		 */
		public static function serveTemporaryFile(string $path, array $query = [], string $disk = 'local')
		{
			$expires = isset($query['expires']) ? (int)$query['expires'] : 0;
			$signature = $query['signature'] ?? '';

			if (!self::validateTemporaryUrl($path, $expires, $signature)) {
				http_response_code(403);
				exit('This temporary link is invalid or has expired.');
			}

			$fileHandler = self::disk($disk);
			if (!$fileHandler->exists($path)) {
				http_response_code(404);
				exit('File not found.');
			}

			$contents = $fileHandler->get($path);

			// Guess MIME type (basic)
			$mime = function_exists('mime_content_type')
				? mime_content_type($fileHandler::$root . '/' . ltrim($path, '/'))
				: 'application/octet-stream';

			header("Content-Type: {$mime}");
			header('Content-Disposition: inline; filename="' . basename($path) . '"');
			echo $contents;
			exit;
		}

		/**
		 * Get all files in a directory.
		 */
		public static function allFiles(string $directory, string $disk = 'local'): array
		{
			return self::disk($disk)->allFiles($directory);
		}

		/**
		 * Get all directories within a directory.
		 */
		public static function allDirectories(string $directory, string $disk = 'local'): array
		{
			return self::disk($disk)->allDirectories($directory);
		}

		/**
		 * Create a new directory.
		 */
		public static function makeDirectory(string $directory, string $disk = 'local'): bool
		{
			return self::disk($disk)->makeDirectory($directory);
		}

		/**
		 * Delete a directory.
		 */
		public static function deleteDirectory(string $directory, string $disk = 'local'): bool
		{
			return self::disk($disk)->deleteDirectory($directory);
		}
	}