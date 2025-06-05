<?php

	namespace App\Bootstrap\Exceptions;

	use Exception;

	class CompilerException extends Exception
	{
		public function report(): string
		{
			return $this->getMessage();
		}
	}