<?php

	namespace App\Utilities\Blueprints;

	use App\Utilities\Config;
	use App\Utilities\Server;
	use Aws\S3\S3Client;

	/**
	 * Class BaseFileUpload
	 *
	 * Provides an abstraction for file storage operations, supporting both
	 * local filesystem and AWS S3 disks. Includes methods for uploading,
	 * retrieving, moving, deleting files and directories, generating URLs
	 * (including temporary signed URLs), and validating temporary URLs.
	 *
	 * @internal
	 * @package App\Utilities\Blueprints
	 */
	class BaseFileUpload
	{
		/** @var string The storage disk being used ('local' or 's3') */
		protected string $disk;

		/** @var S3Client|null AWS S3 client instance, if disk is S3 */
		protected ?S3Client $s3Client = null;

		/** @var string S3 bucket name */
		protected string $bucket;

		/** @var string Base path for file operations */
		protected string $basePath;

		/** @var string Root storage path for local disk */
		public static string $root = 'storage';

		/**
		 * BaseFileUpload constructor.
		 *
		 * @param string $disk Storage disk ('local' or 's3').
		 *
		 * @throws \RuntimeException If S3 bucket does not exist or is inaccessible.
		 */
		public function __construct(string $disk)
		{
			$this->disk = $disk;
			$this->basePath = $this->resolveBasePath($disk);

			if ($this->isS3Disk()) {
				$_ENV['AWS_DEFAULT_REGION']     = env('AWS_DEFAULT_REGION', 'us-east-1');
				$_ENV['AWS_ACCESS_KEY_ID']      = env('AWS_ACCESS_KEY_ID', 'test');
				$_ENV['AWS_SECRET_ACCESS_KEY']  = env('AWS_SECRET_ACCESS_KEY', 'test');
				$_ENV['AWS_BUCKET']             = env('AWS_BUCKET', 'my-bucket');
				$_ENV['AWS_ENDPOINT']           = env('AWS_ENDPOINT', null);

				$config = [
					'region'      => getenv('AWS_DEFAULT_REGION'),
					'version'     => 'latest',
					'credentials' => [
						'key'    => getenv('AWS_ACCESS_KEY_ID'),
						'secret' => getenv('AWS_SECRET_ACCESS_KEY'),
					],
				];

				if (getenv('AWS_ENDPOINT')) {
					$config['endpoint'] = getenv('AWS_ENDPOINT');
					$config['use_path_style_endpoint'] = true;
				}

				$this->s3Client = new S3Client($config);
				$this->bucket   = getenv('AWS_BUCKET');

				try {
					$this->s3Client->headBucket(['Bucket' => $this->bucket]);
				} catch (\Aws\S3\Exception\S3Exception $e) {
					throw new \RuntimeException("S3 bucket '{$this->bucket}' does not exist or is not accessible.");
				}
			}
		}

		/**
		 * Resolve the base path depending on the disk type.
		 */
		protected function resolveBasePath(string $disk): string
		{
			$paths = [
				'local' => rtrim(self::$root, '/') . '/',
				's3'    => 's3://',
			];

			if ($disk === 'local' && !file_exists($paths['local'])) {
				mkdir($paths['local'], 0777, true);
			}

			return $paths[$disk] ?? $paths['local'];
		}

		/**
		 * Get the public URL for a file.
		 */
		public function url(string $path): string
		{
			if ($this->isS3Disk()) {
				return $this->s3Client->getObjectUrl($this->bucket, $path);
			}

			$baseUrl = env('APP_URL', Server::HostName());
			return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
		}

		/**
		 * Generate a temporary URL with expiration.
		 */
		public function temporaryUrl(string $path, \DateTimeInterface $expiration): string
		{
			if (empty($path)) {
				throw new \InvalidArgumentException('Path cannot be empty when generating a temporary URL.');
			}

			if ($expiration->getTimestamp() <= time()) {
				throw new \InvalidArgumentException('Expiration time must be in the future.');
			}

			if ($this->isS3Disk()) {
				$cmd = $this->s3Client->getCommand('GetObject', [
					'Bucket' => $this->bucket,
					'Key'    => $path,
				]);

				return (string)$this->s3Client->createPresignedRequest($cmd, $expiration)->getUri();
			}

			$timestamp = $expiration->getTimestamp();
			$secretKey = env('APP_KEY', 'fallback-secret');
			$data = "{$path}|{$timestamp}";
			$signature = hash_hmac('sha256', $data, $secretKey);

			return sprintf('%s?expires=%d&signature=%s', $this->url($path), $timestamp, $signature);
		}

		/**
		 * Store a file with contents.
		 */
		public function put(string $path, string $contents): bool
		{
			if ($this->isS3Disk()) {
				$this->s3Client->putObject([
					'Bucket' => $this->bucket,
					'Key'    => $path,
					'Body'   => $contents,
				]);
				return true;
			}

			$fullPath = $this->basePath . ltrim($path, '/');
			$dir = dirname($fullPath);
			if (!is_dir($dir)) mkdir($dir, 0777, true);

			return file_put_contents($fullPath, $contents) !== false;
		}

		/**
		 * Retrieve file contents.
		 */
		public function get(string $path): ?string
		{
			if ($this->isS3Disk()) {
				$result = $this->s3Client->getObject(['Bucket' => $this->bucket, 'Key' => $path]);
				return (string)$result['Body'];
			}

			$fullPath = $this->basePath . ltrim($path, '/');
			return file_exists($fullPath) ? file_get_contents($fullPath) : null;
		}

		/**
		 * Check if a file exists.
		 */
		public function exists(string $path): bool
		{
			return $this->isS3Disk()
				? $this->s3Client->doesObjectExist($this->bucket, $path)
				: file_exists($this->basePath . ltrim($path, '/'));
		}

		/**
		 * Delete a file.
		 */
		public function delete(string $path): bool
		{
			if ($this->isS3Disk()) {
				$this->s3Client->deleteObject(['Bucket' => $this->bucket, 'Key' => $path]);
				return true;
			}

			$fullPath = $this->basePath . ltrim($path, '/');
			return file_exists($fullPath) && unlink($fullPath);
		}

		/**
		 * Copy a file.
		 */
		public function copy(string $from, string $to): bool
		{
			if ($this->isS3Disk()) {
				$this->s3Client->copyObject([
					'Bucket'     => $this->bucket,
					'Key'        => $to,
					'CopySource' => "{$this->bucket}/{$from}",
				]);
				return true;
			}

			return copy($this->basePath . ltrim($from, '/'), $this->basePath . ltrim($to, '/'));
		}

		/**
		 * Move a file.
		 */
		public function move(string $from, string $to): bool
		{
			if ($this->isS3Disk()) {
				$this->s3Client->copyObject([
					'Bucket'     => $this->bucket,
					'Key'        => $to,
					'CopySource' => "{$this->bucket}/{$from}",
				]);
				$this->delete($from);
				return true;
			}

			return rename($this->basePath . ltrim($from, '/'), $this->basePath . ltrim($to, '/'));
		}

		/**
		 * Get file size in bytes.
		 */
		public function size(string $path): int
		{
			if ($this->isS3Disk()) {
				$result = $this->s3Client->headObject(['Bucket' => $this->bucket, 'Key' => $path]);
				return $result['ContentLength'];
			}

			$fullPath = $this->basePath . ltrim($path, '/');
			return file_exists($fullPath) ? filesize($fullPath) : 0;
		}

		/**
		 * Get last modified timestamp.
		 */
		public function lastModified(string $path): int
		{
			if ($this->isS3Disk()) {
				$result = $this->s3Client->headObject(['Bucket' => $this->bucket, 'Key' => $path]);
				return strtotime($result['LastModified']);
			}

			$fullPath = $this->basePath . ltrim($path, '/');
			return file_exists($fullPath) ? filemtime($fullPath) : 0;
		}

		/**
		 * List all files in a directory.
		 */
		public function allFiles(string $directory = ''): array
		{
			if ($this->isS3Disk()) {
				$files = $this->s3Client->listObjectsV2([
					'Bucket'    => $this->bucket,
					'Prefix'    => $directory,
					'Delimiter' => '/',
				]);
				return array_map(fn($object) => $object['Key'], $files['Contents'] ?? []);
			}

			$fullDirectory = $this->basePath . ltrim($directory, '/');
			$files = glob($fullDirectory . '/*');
			return array_map(fn($f) => str_replace($this->basePath, '', $f), $files ?: []);
		}

		/**
		 * List all directories in a directory.
		 */
		public function allDirectories(string $directory = ''): array
		{
			if ($this->isS3Disk()) {
				$objects = $this->s3Client->listObjectsV2([
					'Bucket'    => $this->bucket,
					'Prefix'    => $directory,
					'Delimiter' => '/',
				]);
				return array_map(fn($prefix) => $prefix['Prefix'], $objects['CommonPrefixes'] ?? []);
			}

			$fullDirectory = $this->basePath . ltrim($directory, '/');
			$dirs = glob($fullDirectory . '/*', GLOB_ONLYDIR);
			return array_map(fn($d) => str_replace($this->basePath, '', $d), $dirs ?: []);
		}

		/**
		 * Create a directory.
		 */
		public function makeDirectory(string $directory): bool
		{
			if ($this->isS3Disk()) {
				$this->put($directory . '/.empty', '');
				return true;
			}

			return mkdir($this->basePath . ltrim($directory, '/'), 0777, true);
		}

		/**
		 * Delete a directory and all its contents.
		 */
		public function deleteDirectory(string $directory): bool
		{
			if ($this->isS3Disk()) {
				$objects = $this->s3Client->listObjectsV2([
					'Bucket' => $this->bucket,
					'Prefix' => $directory,
				]);
				foreach ($objects['Contents'] ?? [] as $object) $this->delete($object['Key']);
				return true;
			}

			$fullPath = $this->basePath . ltrim($directory, '/');
			if (!is_dir($fullPath)) return false;

			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($fullPath, \FilesystemIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::CHILD_FIRST
			);

			foreach ($iterator as $file) {
				$file->isDir() ? rmdir($file) : unlink($file);
			}

			return rmdir($fullPath);
		}

		/**
		 * Check if the disk is S3.
		 */
		protected function isS3Disk(): bool
		{
			return $this->disk === 's3' && class_exists('Aws\S3\S3Client');
		}

		/**
		 * Set storage root path for local files.
		 */
		public static function setStoragePath(string $path): void
		{
			self::$root = $path;
			if (!file_exists($path)) mkdir($path, 0777, true);
		}

		/**
		 * Validate a temporary URL signature.
		 */
		public function validateTemporaryUrl(string $path, int $expires, string $signature): bool
		{
			if ($expires < time()) return false;

			$secretKey = env('APP_KEY', 'fallback-secret');
			$data = "{$path}|{$expires}";
			$expectedSignature = hash_hmac('sha256', $data, $secretKey);

			return hash_equals($expectedSignature, $signature);
		}
	}
