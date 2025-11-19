<?php

	namespace App\Utilities\Handler;

	/**
	 * Class AuthUser
	 *
	 * Lightweight user wrapper provides easy access to
	 * authenticated user properties via magic getters.
	 *
	 * This class is typically returned by the authentication handler
	 * and gives a clean way to access fields such as:
	 *
	 *   $user->id
	 *   $user->email
	 *   $user->role
	 *
	 * The original array can also be retrieved with toArray().
	 */
	final class AuthUser
	{
		/**
		 * The underlying user data.
		 *
		 * @var array
		 */
		private array $data;

		/**
		 * AuthUser constructor.
		 *
		 * @param array $data  The raw user data (usually from DB).
		 */
		public function __construct(array $data)
		{
			$this->data = $data;
		}

		/**
		 * Magic getter for user fields.
		 *
		 * Allows property-like access:
		 *   $user->name
		 *   $user->email
		 *
		 * @param string $key
		 * @return mixed|null  Returns the field value or null if not found.
		 */
		public function __get(string $key)
		{
			return $this->data[$key] ?? null;
		}

		/**
		 * Returns the full user data as an array.
		 *
		 * @return array
		 */
		public function toArray(): array
		{
			return $this->data;
		}
	}
