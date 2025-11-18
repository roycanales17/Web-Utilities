<?php

	namespace App\Utilities;

	use App\Utilities\Handler\BaseRequestHeaders;
	use App\Utilities\Handler\BaseRequestInputs;
	use App\Utilities\Handler\BaseRequestValidator;

	class Request
	{
		use BaseRequestInputs;
		use BaseRequestHeaders;
		use BaseRequestValidator;

		protected array $data = [];
		protected array $files = [];

		// Static cache
		protected static ?array $cachedData = null;
		protected static ?array $cachedFiles = null;

		public function __construct() {
			if (self::$cachedData !== null) {
				$this->data = self::$cachedData;
				$this->files = self::$cachedFiles;
				return;
			}

			$this->populateJson();
			$this->data = array_merge($_GET, $_POST, $this->data);
			$this->files = $_FILES ?? [];

			// Store in static cache
			self::$cachedData = $this->data;
			self::$cachedFiles = $this->files;
		}

		public function response(mixed $content, int $code = 200, array $headers = []): Response
		{
			return new Response($content, $code, $headers);
		}
	}