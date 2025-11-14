<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Handler\Bootloader;

	final class OBEnd extends Bootloader
	{
		public function handler(): void {
			if (!$this->isCli() && $this->isDevelopment()) {
				while (ob_get_level() > 0) {
					ob_end_clean();
				}
			}
		}
	}