<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Utilities\Storage as BaseStorage;
	use App\Utilities\Handler\Bootloader;

	final class Storage extends Bootloader
	{
		public function handler(): void {
			BaseStorage::configure(base_path('/storage'));
		}
	}