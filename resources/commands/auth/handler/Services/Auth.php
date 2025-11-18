<?php

    namespace Http\Services;

    use App\Utilities\Request;
    use App\Utilities\Mail;
    use App\Utilities\Session;
    use Http\Model\Users;
    use Mails\Auth\ResetPassword;

    class Auth
    {
        protected static ?array $cachedUser = null;
        protected ?array $user = null;

        public function __construct()
        {
            if (self::$cachedUser === null) {
                self::$cachedUser = $this->boot();
            } else {
                $this->user = self::$cachedUser;
            }
        }

        public function user(): ?array
        {
            return $this->user;
        }

        public function check(): bool
        {
            return $this->user !== null;
        }

        public function id(): ?int
        {
            return $this->user['id'] ?? null;
        }

        public function role(): ?string
        {
            return $this->user['role'] ?? null;
        }

        public function can(string $permission): bool
        {
            // TODO: expand when permission table is added
            return $this->user && in_array($permission, explode(',', $this->user['role']));
        }

        protected function authorize(array $user): void
        {
            $this->user = $user;
            Session::set('user_id', $this->user['id']);
        }

        protected function boot(): ?array
        {
            $userId = null;
            switch (true)
            {
                case Session::has('user_id'):
                    $this->user = Users::_(Session::get('user_id'))->data();
                    break;

                case $token = fetchCookie('remember_token'):
                    $this->user = Users::where('remember_token', $token)->row();
                    if ($this->user) {
                        $userId = $this->user['id'];
                    }
                    break;

                case $auth = Request::header('HTTP_AUTHORIZATION'):
                    $token = trim(str_replace('Bearer', '', $auth));
                    $this->user = Users::where('api_token', $token)->row();
                    if ($this->user) {
                        $userId = $this->user['id'];
                    }
                    break;
            }

            if ($userId) {
                // Always put on the session
                Session::set('user_id', $userId);
            }

            return $this->user;
        }

        public function register(string $name, string $email, string $password, string $role = 'user'): ?array
        {
            if (!$name || !$email || !$password) {
                return null;
            }

            $hash = password_hash($password, PASSWORD_BCRYPT);
            $userId = Users::create([
                'name' => $name,
                'email' => $email,
                'password' => $hash,
                'role' => $role
            ]);

            return Users::find($userId);
        }

        public function login(string $email, string $password, bool $remember = false): bool
        {
            if (!$email || !$password) {
                return false;
            }

            $user = Users::where('email', $email)->row();

            if ($user && password_verify($password, $user['password'])) {
                $this->authorize($user);

                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    createCookie('remember_token', $token, time() + (86400 * 30));
                    Users::where('id', $this->user['id'])->set('remember_token', $token)->update();
                }

                // Track last login
                Users::where('id', $this->user['id'])
                    ->set('last_login', date('Y-m-d H:i:s'))
                    ->update();

                return true;
            }

            return false;
        }

        public function logout(): void
        {
            $this->user = null;
            Session::remove('user_id');
            deleteCookie('remember_token');

            // Todo: we can track when the last time the account logout
        }

        public function issueToken(int $userId): string
        {
            if (!$userId) return '';

            // TWO AUTHENTICATION FACTORIES (SESSION + TOKEN)
            $token = bin2hex(random_bytes(32));
            Users::where('id', $userId)->set('api_token', $token)->update();
            return $token;
        }

        public function authenticateWithToken(string $token): bool
        {
            if (!$token) return false;

            $user = Users::where('api_token', $token)->row();
            if ($user) {
                $this->authorize($user);
                return true;
            }
            return false;
        }

        public function sendPasswordReset(string $email): ?string
        {
            if (!$email) return null;

            $token = bin2hex(random_bytes(32));
            Users::where('email', $email)
                ->set('reset_token', $token)
                ->set('reset_expires', date('Y-m-d H:i:s', strtotime('+1 hour')))
                ->update();

            $name = Users::where('email', $email)
                ->select('name')
                ->field();

            Mail::mail(new ResetPassword([
                'name' => $name,
                'email' => $email,
                'token' => $token,
            ]));

            return $token;
        }

        public function userIdByResetToken(string $token)
        {
            $userId = Users::select('id')
                ->where('reset_token', $token)
                ->whereRaw('reset_expires > NOW()')
                ->field();

            return $userId;
        }

        public function resetPassword(string $token, string $newPassword): bool
        {
            if (!$token || !$newPassword) return false;

            $userId = $this->userIdByResetToken($token);

            if ($userId) {
                $hash = password_hash($newPassword, PASSWORD_BCRYPT);
                Users::where('id', $userId)
                    ->set('password', $hash)
                    ->set('reset_token', null)
                    ->set('reset_expires', null)
                    ->update();

                // Todo: Log here for the account activity
                return true;
            }

            return false;
        }
    }
