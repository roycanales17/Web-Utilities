<?php

	namespace App\Bootstrap\Bootstrapper;

	use App\Headers\Request;
	use App\Utilities\Handler\Bootloader;

	final class Requests extends Bootloader
	{
		public function handler(): void {
			Request::capture();
		}
	}