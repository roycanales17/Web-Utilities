<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Config;
	use App\Utilities\Handler\Bootloader;
	use App\Utilities\Stream;

	final class StreamWireAuthentication extends Bootloader
	{
		public function handler(): void {
			$auth = Config::get('StreamWireAuthentication');
			if ($auth) {
				if ($this->is_associative($auth)) {
					foreach ($auth as $class => $method) {
						Stream::authentication([$class, $method]);
					}
				} else {
					foreach ($auth as $action) {
						Stream::authentication($action);
					}
				}
				console_log("Stream wire authentication: ready");
			}
		}

		private function is_associative(array $array): bool {
			return array_keys($array) === range(0, count($array) - 1);
		}
	}