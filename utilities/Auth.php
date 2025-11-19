<?php

	namespace App\Utilities;

	use App\Utilities\Handler\AuthUser;
	use App\Bootstrap\Exceptions\AppException;
	use App\Utilities\Handler\BaseAuthentication;

	/**
	 * Class Auth
	 *
	 * Centralized authentication and authorization handler.
	 *
	 * Features:
	 * - Session-based authentication
	 * - Remember-me persistent login
	 * - Bearer API token authentication
	 * - Token issuing, rotation, expiration, and revocation
	 * - User registration
	 * - Password reset flow (token generation and reset)
	 * - Role and permission checking
	 *
	 * This class exposes static methods via PHPDoc @method,
	 * enabling IDE autocompletion while delegating internally
	 * to the Authentication parent class using __callStatic().
	 *
	 * @package App\Utilities\Handler
	 *
	 * ----------------------------------------------------------------------
	 * Authentication State
	 * ----------------------------------------------------------------------
	 * @method static array|null  user()                        Retrieve authenticated user data, or null
	 * @method static bool        check()                       Determine whether a user is authenticated
	 * @method static int|null    id()                          Get authenticated user's ID
	 * @method static string|null role()                        Get authenticated user's role string
	 * @method static bool        can(string $permission)       Check whether user has a specific permission
	 *
	 * ----------------------------------------------------------------------
	 * Core Login / Registration
	 * ----------------------------------------------------------------------
	 * @method static AuthUser|false authorize(array|null $user)  Validate user object and establish session
	 * @method static array|null  register(string $name, string $email, string $password, string $role = '')  Register and return new user
	 * @method static bool        login(string $email, string $password, bool $remember = false) Log in user
	 * @method static void        logout(bool $allSessions = false, bool $regenerate_session = true) Log out user (optionally all sessions)
	 *
	 * ----------------------------------------------------------------------
	 * Password Reset
	 * ----------------------------------------------------------------------
	 * @method static bool         resetPassword(string $token, string $newPassword)             Reset password using token
	 * @method static string|false createPasswordResetToken(string $email, int $expiration = 3600) Create + store a reset token
	 *
	 * ----------------------------------------------------------------------
	 * API Token Authentication
	 * ----------------------------------------------------------------------
	 * @method static string       issueApiToken(int $userId, int $expiresInSeconds = 0)         Issue new API token
	 * @method static void         revokeApiToken(int $userId)                                   Revoke/delete API token
	 * @method static string       rotateApiToken(int $userId, int $expiresInSeconds = 0)        Generate a new API token
	 * @method static array|null   validateApiToken(string $token)                               Validate token and return user
	 * @method static string|null  currentApiToken()                                              Get Bearer token from request
	 */
	final class Auth extends BaseAuthentication
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