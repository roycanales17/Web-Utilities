<?php

	namespace App\Bootstrap\Handler;

	use App\Bootstrap\Exceptions\AppException;

	final class StreamWireConfig
	{
		private array|string $path = '';
		private array $authentication = [];
		private ?object $onFailed = null;

		public function setAuthentication(array $authentication): self
		{
			$class = $authentication[0] ?? '';
			$method = $authentication[1] ?? '';

			if (!class_exists($class)) {
				throw new AppException('Class "' . $class . '" does not exist');
			}

			if (!method_exists($class, $method)) {
				throw new AppException('Method "' . $method . '" does not exist from class "' . $class . '"');
			}

			$this->authentication = $authentication;
			return $this;
		}

		public function setPath(string|array $path): self
		{
			if (!file_exists($path)) {
				throw new AppException('Path "' . $path . '" does not exist');
			}

			$this->path = $path;
			return $this;
		}

		public function onFailed(object $onFailed): self
		{
			$this->onFailed = $onFailed;
			return $this;
		}

		public function getPath(): string {
			return $this->path;
		}

		public function getAuthentication(): array {
			return $this->authentication;
		}

		public function getOnFailed(): ?object {
			return $this->onFailed;
		}
	}