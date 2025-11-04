<?php
    namespace Handler\Mails\Auth;

    use App\Utilities\Handler\Mailable;
	use App\Utilities\Server;

	class ResetPassword extends Mailable {

        public string $name;
		public string $email;
        public string $token;

		public function __construct(array $data)
		{
            $this->name = $data['name'];
			$this->email = $data['email'];
            $this->token = $data['token'];
		}

		public function send(): bool
		{
            if (!$this->email || !$this->token) {
                return false;
            }

			return $this->view('auth.emails.reset-password', [
                'name' => ucfirst(strtolower(strtok(trim($this->name), ' '))),
                'resetUrl' => Server::makeURL(route('recover-account', ['token' => $this->token]))
            ])
            ->contentType('text/html')
            ->to($this->email)
            ->build();
		}
	}
