<?php

	namespace App\Utilities\Handler;

	use App\Databases\Database;
	use App\Headers\Request;
	use App\Utilities\Session;
	use Http\Model\Users;

	abstract class Authentication
	{
		/**
		 * Cached authenticated user.
		 *
		 * @var array|null
		 */
		private static ?array $user = null;

		/**
		 * Constructor automatically initializes authentication resolution.
		 */
		protected function __construct()
		{
			self::boot();
		}

		/**
		 * Resolves authentication using:
		 * 1. Session
		 * 2. Remember token cookie
		 * 3. Bearer API Token
		 *
		 * Boot runs only once per request due to caching.
		 *
		 * @return array|null
		 */
		private static function boot(): ?array
		{
			// Already authenticated / cached
			if (self::$user) {
				return self::$user;
			}

			$data = null;

			/** 1. Session user */
			if (Session::has('user_id')) {
				$data = Users::_(Session::get('user_id'))->data() ?: null;
			}

			/** 2. Remember-me cookie */
			elseif ($token = fetchCookie('remember_token')) {
				$data = Users::where('remember_token', $token)->row() ?: null;
			}

			/** 3. API Bearer token */
			else {
				$auth = self::currentApiToken();
				if ($auth) {
					$data = self::validateApiToken($auth);
				}
			}

			/** Validate and store authenticated state */
			if ($data && self::authorize($data)) {
				return self::$user;
			}

			return null;
		}

		/**
		 * Retrieves authenticated user data.
		 *
		 * @return array|null
		 */
		protected static function user(): ?array
		{
			return self::boot();
		}

		/**
		 * Checks whether a user is logged in.
		 *
		 * @return bool
		 */
		protected static function check(): bool
		{
			return self::boot() !== null;
		}

		/**
		 * Returns logged-in user's ID.
		 *
		 * @return int|null
		 */
		protected static function id(): ?int
		{
			return self::boot()['id'] ?? null;
		}

		/**
		 * Returns logged-in user's role string.
		 *
		 * @return string|null
		 */
		protected static function role(): ?string
		{
			return self::boot()['role'] ?? null;
		}

		/**
		 * Checks if user has a specific permission
		 * based on CSV roles.
		 *
		 * @param string $permission
		 * @return bool
		 */
		protected static function can(string $permission): bool
		{
			$role = self::role();
			return $role && in_array($permission, explode(',', $role));
		}

		/**
		 * Validates user object and stores session & cache.
		 *
		 * @param array|null $user
		 * @return bool
		 */
		protected static function authorize(?array $user): bool
		{
			if (empty($user['id'])) {
				return false;
			}

			self::$user = $user;

			// Set session
			Session::set('user_id', $user['id']);

			return true;
		}

		/**
		 * Register a new user.
		 *
		 * @param string $name
		 * @param string $email
		 * @param string $password
		 * @param string $role
		 * @return array|null
		 */
		protected static function register(string $name, string $email, string $password, string $role = ''): ?array
		{
			if (!$name || !$email || !$password) {
				return null;
			}

			$hash = password_hash($password, env('PASSWORD_ALGO', PASSWORD_BCRYPT));
			$userId = Users::create(array_merge([
				'name'      => $name,
				'email'     => $email,
				'password'  => $hash
			], $role ? ['role' => $role] : []));

			return Users::find($userId);
		}

		/**
		 * Login using email + password.
		 *
		 * @param string $email
		 * @param string $password
		 * @param bool   $remember
		 * @return bool
		 */
		protected static function login(string $email, string $password, bool $remember = false): bool
		{
			if (!$email || !$password) {
				return false;
			}

			$user = Users::where('email', $email)->row();

			if (!$user || !password_verify($password, $user['password'])) {
				return false;
			}

			self::authorize($user);

			// Remember token
			if ($remember) {
				$token = bin2hex(random_bytes(32));

				createCookie('remember_token', $token, time() + (86400 * 30));

				Users::where('id', self::id())
					->set('remember_token', $token)
					->update();
			}

			// Track last login
			Users::where('id', self::id())
				->set('last_login', date('Y-m-d H:i:s'))
				->update();

			return true;
		}

		/**
		 * Logs out the user.
		 *
		 * @param bool $regenerate_session
		 * @param bool $allSessions
		 * @return void
		 */
		protected static function logout(bool $regenerate_session = false, bool $allSessions = false): void
		{
			$currentId = self::id();

			if ($currentId) {
				// Clear tokens for the user
				Users::where('id', $currentId)
					->set('remember_token', null)
					->set('api_token', null)
					->set('api_token_expires', null)
					->update();

				// Remove sessions
				if ($allSessions) {
					Database::table('sessions')->where('user_id', $currentId)->delete();
				} else {
					// Only remove current session row if session exists
					if (Session::started() && session_id()) {
						Database::table('sessions')->where('id', session_id())->delete();
					}
				}
			}

			// Clear cached user
			self::$user = null;

			// Remove user from PHP session
			Session::remove('user_id');

			// Delete remember-me cookie
			deleteCookie('remember_token');

			// Regenerate session ID for security
			if ($regenerate_session) {
				Session::regenerate(true); // true to delete old session data
			}
		}

		/**
		 * Reset password using a reset token.
		 *
		 * @param string $token
		 * @param string $newPassword
		 * @return bool
		 */
		protected static function resetPassword(string $token, string $newPassword): bool
		{
			if (!$token || !$newPassword) {
				return false;
			}

			$userId = Users::select('id')
				->where('reset_token', $token)
				->whereRaw('reset_expires > NOW()')
				->field();

			if (!$userId) {
				return false;
			}

			$hash = password_hash($newPassword, env('PASSWORD_ALGO', PASSWORD_BCRYPT));
			Users::where('id', $userId)
				->set('password', $hash)
				->set('reset_token', null)
				->set('reset_expires', null)
				->update();

			return true;
		}

		/**
		 * Issue a new secure API token.
		 *
		 * @param int $userId
		 * @param int $expiresInSeconds 0 = no expiry
		 * @return string
		 */
		protected static function issueApiToken(int $userId, int $expiresInSeconds = 0): string
		{
			$token = bin2hex(random_bytes(40));
			$table = Users::where('id', $userId)->set('api_token', $token);

			if ($expiresInSeconds > 0) {
				$table->set('api_token_expires', date('Y-m-d H:i:s', time() + $expiresInSeconds));
			}

			$table->update();
			return $token;
		}

		/**
		 * Deletes a user's API token.
		 *
		 * @param int $userId
		 * @return void
		 */
		protected static function revokeApiToken(int $userId): void
		{
			Users::where('id', $userId)
				->set('api_token', null)
				->set('api_token_expires', null)
				->update();
		}

		/**
		 * Regenerates a fresh API token.
		 *
		 * @param int $userId
		 * @param int $expiresInSeconds
		 * @return string
		 */
		protected static function rotateApiToken(int $userId, int $expiresInSeconds = 0): string
		{
			self::revokeApiToken($userId);
			return self::issueApiToken($userId, $expiresInSeconds);
		}

		/**
		 * Validates API token & returns user if valid.
		 *
		 * @param string $token
		 * @return array|null
		 */
		protected static function validateApiToken(string $token): ?array
		{
			if (!$token) {
				return null;
			}

			$user = Users::where('api_token', $token)->row();

			if (!$user) {
				return null;
			}

			// Check expiration
			if (!empty($user['api_token_expires']) && strtotime($user['api_token_expires']) < time()) {

				// Auto-revoke expired token
				Users::where('id', $user['id'])
					->set('api_token', null)
					->set('api_token_expires', null)
					->update();

				return null;
			}

			return $user;
		}

		/**
		 * Returns API token from current request (if present).
		 *
		 * @return string|null
		 */
		protected static function currentApiToken(): ?string
		{
			$auth = Request::header('Authorization')
				?? Request::header('HTTP_AUTHORIZATION');

			if (!$auth) {
				return null;
			}

			return trim(str_replace('Bearer', '', $auth));
		}
	}