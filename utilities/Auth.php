<?php

	namespace App\Utilities;

	use App\Bootstrap\Exceptions\AppException;
	use App\Utilities\Handler\Authentication;

	/**
	 * Class Auth
	 *
	 * Centralized authentication and authorization handler.
	 * Supports:
	 *  - Session-based login
	 *  - Remember-me token auto-login
	 *  - API Bearer token authentication
	 *  - Token issuing, rotation, expiration, revocation
	 *  - Registration, password reset
	 *  - Role + permission checking
	 *
	 * @package App\Utilities\Handler
	 *
	 * @method static array|null user() Retrieve authenticated user data
	 * @method static bool check() Check whether a user is logged in
	 * @method static int|null id() Get logged-in user's ID
	 * @method static string|null role() Get logged-in user's role
	 * @method static bool can(string $permission) Check if user has a specific permission
	 *
	 * @method static bool authorize(array|null $user) Validate user object and store session/cache
	 * @method static array|null register(string $name, string $email, string $password, string $role = '') Register a new user
	 * @method static bool login(string $email, string $password, bool $remember = false) Log in a user
	 * @method static void logout(bool $regenerate_session = false) Log out the user
	 * @method static bool resetPassword(string $token, string $newPassword) Reset password using a token
	 *
	 * @method static string issueApiToken(int $userId, int $expiresInSeconds = 0) Issue a new API token
	 * @method static void revokeApiToken(int $userId) Delete a user's API token
	 * @method static string rotateApiToken(int $userId, int $expiresInSeconds = 0) Regenerate a fresh API token
	 * @method static array|null validateApiToken(string $token) Validate API token & return user if valid
	 * @method static string|null currentApiToken() Get API token from current request (if present)
	 */
	final class Auth extends Authentication
	{
		/**
		 * Forward static calls to the parent Authentication class.
		 *
		 * @param string $method
		 * @param array $arguments
		 * @return mixed
		 *
		 * @throws AppException
		 */
		public static function __callStatic($method, $arguments)
		{
			// Check parent class for protected static methods
			if (method_exists(parent::class, $method)) {
				return call_user_func_array([parent::class, $method], $arguments);
			}

			throw new AppException("Method $method does not exist in Auth or Authentication", 500);
		}
	}