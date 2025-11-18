<?php

	namespace App\Http;

	use App\Utilities\Request;

	abstract class Requests extends Request
	{
		abstract public function authorize(): bool;

		abstract public function rules(): array;
	}