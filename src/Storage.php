<?php

	namespace App\Utilities;

	use App\Utilities\Blueprints\BaseFileUpload;

	class Storage
	{
		public static function configure(string $defaultPath = 'storage'): void
		{
			BaseFileUpload::setStoragePath($defaultPath);
		}

		public static function disk(string $disk = 'local'): BaseFileUpload
		{
			return new BaseFileUpload($disk);
		}

		public static function put(string $path, string $contents, string $disk = 'local'): bool
		{
			return self::disk($disk)->put($path, $contents);
		}

		public static function get(string $path, string $disk = 'local'): ?string
		{
			return self::disk($disk)->get($path);
		}

		public static function exists(string $path, string $disk = 'local'): bool
		{
			return self::disk($disk)->exists($path);
		}

		public static function delete(string $path, string $disk = 'local'): bool
		{
			return self::disk($disk)->delete($path);
		}

		public static function copy(string $from, string $to, string $disk = 'local'): bool
		{
			return self::disk($disk)->copy($from, $to);
		}

		public static function move(string $from, string $to, string $disk = 'local'): bool
		{
			return self::disk($disk)->move($from, $to);
		}

		public static function size(string $path, string $disk = 'local'): int
		{
			return self::disk($disk)->size($path);
		}

		public static function lastModified(string $path, string $disk = 'local'): int
		{
			return self::disk($disk)->lastModified($path);
		}

		public static function url(string $path, string $disk = 'local'): string
		{
			return self::disk($disk)->url($path);
		}

		public static function temporaryUrl(string $path, \DateTimeInterface $expiration, string $disk = 'local'): string
		{
			return self::disk($disk)->temporaryUrl($path, $expiration);
		}

		public static function allFiles(string $directory, string $disk = 'local'): array
		{
			return self::disk($disk)->allFiles($directory);
		}

		public static function allDirectories(string $directory, string $disk = 'local'): array
		{
			return self::disk($disk)->allDirectories($directory);
		}

		public static function makeDirectory(string $directory, string $disk = 'local'): bool
		{
			return self::disk($disk)->makeDirectory($directory);
		}

		public static function deleteDirectory(string $directory, string $disk = 'local'): bool
		{
			return self::disk($disk)->deleteDirectory($directory);
		}
	}
