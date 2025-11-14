<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Handler\Bootloader;

	final class OBStart extends Bootloader
	{
		public function handler(): void {
			if (!$this->isCli()) {
				while (ob_get_level() > 0) {
					ob_end_clean();
				}
				ob_start();
			}
		}
	}