<?php

	namespace Http\Model;

	use App\Databases\Facade\Model;

	class Users extends Model
	{
		public string $primary_key = 'id';
		public string $table = 'users';
		public array $fillable = [];
	}
