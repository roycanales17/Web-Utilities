<?php

	namespace App\Utilities\Handler;

	use App\Bootstrap\Exceptions\AppException;
	use App\Databases\Database;
	use App\Utilities\Carbon;
	use App\Utilities\Request;
	use App\Utilities\Server;
	use App\Utilities\Session;
	use Handler\Model\Users;

	/**
	 * @internal
	 */
	abstract class BaseAuthentication
	{
		/**
		 * Cached authenticated user.
		 *
		 * @var AuthUser|null
		 */
		private static ?AuthUser $user = null;

		/**
		 * Constructor automatically initializes authentication resolution.
		 */
		protected function __construct()
		{
			self::boot();
		}

		/**
		 * Resolves authentication.
		 *
		 * @return AuthUser|null
		 * @throws AppException
		 */
		private static function boot(): ?AuthUser
		{
			// Already authenticated (cached)
			if (self::$user instanceof AuthUser) {
				return self::$user;
			}

			if (!class_exists(Users::class)) {
				throw new AppException("User class missing");
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

			/** 3. API Token */
			else {
				$auth = self::currentApiToken();
				if ($auth) {
					$data = self::validateApiToken($auth);
				}
			}

			/** Validate and authorize */
			if ($data && self::authorize($data)) {
				return self::$user;
			}

			return null;
		}

		/**
		 * @return AuthUser|null
		 */
		protected static function user(): ?AuthUser
		{
			return self::boot();
		}

		/**
		 * Checks if logged in.
		 */
		protected static function check(): bool
		{
			return self::boot() !== null;
		}

		/**
		 * Returns user ID.
		 */
		protected static function id(): ?int
		{
			return self::boot()?->id;
		}

		/**
		 * Returns user role string.
		 */
		protected static function role(): ?string
		{
			return self::boot()?->role;
		}

		/**
		 * Check if user has permission.
		 */
		protected static function can(string $permission): bool
		{
			$role = self::role();
			return $role && in_array($permission, explode(',', $role));
		}

		/**
		 * Validates a user array & stores session + cache.
		 */
		protected static function authorize(?array $user): bool
		{
			if (empty($user['id'])) {
				return false;
			}

			// Convert array → object
			self::$user = new AuthUser($user);

			Session::set('user_id', $user['id']);
			Session::regenerate(true);

			Session::set('user_agent', Server::userAgent());
			Session::set('ip_address', Server::IPAddress());
			Session::set('login_time', time());

			return true;
		}

		/**
		 * Register user.
		 */
		protected static function register(string $name, string $email, string $password, string $role = ''): ?array
		{
			if (!$name || !$email || !$password) {
				return null;
			}

			$hash = password_hash($password, env('PASSWORD_ALGO', PASSWORD_BCRYPT));
			$userId = Users::create(array_merge([
				'name'     => $name,
				'email'    => $email,
				'password' => $hash
			], $role ? ['role' => $role] : []));

			return Users::find($userId);
		}

		/**
		 * Login with email + password.
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
				createCookie('remember_token', $token, Carbon::now()->addDays(30)->getDateTime()->getTimestamp());

				Users::where('id', self::id())
					->set('remember_token', $token)
					->update();
			}

			// Update last login
			Users::where('id', self::id())
				->set('last_login', Carbon::now()->format())
				->update();

			return true;
		}

		/**
		 * Logout user.
		 */
		protected static function logout(bool $allSessions = false, bool $regenerate_session = true): void
		{
			$currentId = self::id();

			if ($currentId) {
				// Clear tokens
				Users::where('id', $currentId)
					->set('remember_token', null)
					->set('api_token', null)
					->set('api_token_expires', null)
					->update();

				// Remove sessions
				if ($allSessions) {
					Database::table('sessions')->where('user_id', $currentId)->delete();
				} else {
					if (Session::started() && session_id()) {
						Database::table('sessions')->where('id', session_id())->delete();
					}
				}
			}

			// Clear user
			self::$user = null;
			Session::remove('user_id');
			deleteCookie('remember_token');

			if ($regenerate_session) {
				Session::regenerate(true);
			}
		}

		/**
		 * Reset password.
		 */
		protected static function resetPassword(string $token, string $newPassword): bool
		{
			if (!$token || !$newPassword) {
				return false;
			}

			$hashed = hash('sha256', $token);
			$userId = Users::select('id')
				->where('reset_token', $hashed)
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
		 * Create reset token.
		 */
		protected static function createPasswordResetToken(string $email, int $expiration = 3600): string|false
		{
			if (!$email) {
				return false;
			}

			$user = Users::where('email', $email)->row();
			if (!$user) {
				return false;
			}

			// Clear old
			Users::where('id', $user['id'])
				->set('reset_token', null)
				->set('reset_expires', null)
				->update();

			// Generate raw token
			$rawToken = bin2hex(random_bytes(32));
			$hashed = hash('sha256', $rawToken);
			$expires = Carbon::now()->addSeconds($expiration)->format();

			Users::where('id', $user['id'])
				->set('reset_token', $hashed)
				->set('reset_expires', $expires)
				->update();

			return $rawToken;
		}

		/**
		 * Issue API token.
		 */
		protected static function issueApiToken(int $userId, int $expiresInSeconds = 0): string
		{
			$token = bin2hex(random_bytes(40));
			$table = Users::where('id', $userId)->set('api_token', $token);

			if ($expiresInSeconds > 0) {
				$table->set('api_token_expires', Carbon::now()->addSeconds($expiresInSeconds)->format());
			}

			$table->update();
			return $token;
		}

		/**
		 * Revoke API token.
		 */
		protected static function revokeApiToken(int $userId): void
		{
			Users::where('id', $userId)
				->set('api_token', null)
				->set('api_token_expires', null)
				->update();
		}

		/**
		 * Rotate API token.
		 */
		protected static function rotateApiToken(int $userId, int $expiresInSeconds = 0): string
		{
			self::revokeApiToken($userId);
			return self::issueApiToken($userId, $expiresInSeconds);
		}

		/**
		 * Validate token and return user.
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

			if (!empty($user['api_token_expires']) &&
				Carbon::parse($user['api_token_expires'])->isPast()) {

				Users::where('id', $user['id'])
					->set('api_token', null)
					->set('api_token_expires', null)
					->update();

				return null;
			}

			return $user;
		}

		/**
		 * Extract Bearer token.
		 */
		protected static function currentApiToken(): ?string
		{
			$req = new Request();
			$auth = $req->header('Authorization') ?? $req->header('HTTP_AUTHORIZATION');
			if (!$auth) {
				return null;
			}

			return trim(str_replace('Bearer', '', $auth));
		}
	}