<?php

	namespace App\Utilities;

	use Exception;
	use PHPMailer\PHPMailer\PHPMailer;

	class Mail
	{
		protected static ?Logger $logger = null;
		protected static ?PHPMailer $mailer = null;
		protected string $charset = 'UTF-8';
		protected string $contentType = 'text/html';
		protected string $from = 'no-reply@example.com';
		protected array $recipients;
		protected string $subject = '';
		protected string $body = '';
		protected array $headers = [];
		protected array $attachments = [];
		protected array $embeddedImages = [];
		protected array $replyTo = [];
		protected array $cc = [];
		protected array $bcc = [];

		public static function configure(string $host, int $port = 1025, array $credentials = []): bool
		{
			if (!$host)
				return false;

			try {
				$mail = new PHPMailer(true);
				$mail->isSMTP();
				$mail->Host = $host;
				$mail->Port = $port;
				$mail->SMTPAuth = false;

				if ($credentials) {
					$mail->SMTPAuth = true;
					$mail->Username = $credentials['username'] ?? '';
					$mail->Password = $credentials['password'] ?? '';
				}

				$mail->SMTPAutoTLS = false;
				$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

				self::$mailer = $mail;
				return true;
			} catch (Exception $e) {
				self::logError($e);
				return false;
			}
		}

		public static function to(string|array $emails): self
		{
			$instance = new self();
			if (is_string($emails))
				$emails = [$emails];

			$instance->recipients = array_filter($emails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
			return $instance;
		}

		public static function mail(object $className): bool
		{
			$obj = $className;
			if (method_exists($obj, 'send'))
				return $obj->send();

			return false;
		}

		public function from(string $email): self
		{
			if ($email)
				$this->from = $email;

			return $this;
		}

		public function subject(string $subject): self
		{
			if ($subject)
				$this->subject = $subject;

			return $this;
		}

		public function body(string $body): self
		{
			if ($body)
				$this->body = $body;

			return $this;
		}

		public function header(string $key, string $value): self
		{
			$this->headers[] = "$key: $value";
			return $this;
		}

		public function charset(string $charset): self
		{
			if ($charset)
				$this->charset = $charset;

			return $this;
		}

		public function contentType(string $contentType): self
		{
			if ($contentType)
				$this->contentType = $contentType;

			return $this;
		}

		public function cc(string $email): self
		{
			if ($email)
				$this->cc[] = $email;

			return $this;
		}

		public function bcc(string $email): self
		{
			if ($email)
				$this->bcc[] = $email;

			return $this;
		}

		public function replyTo(string $email, string $name = ''): self
		{
			if ($email)
				$this->replyTo[] = compact('email', 'name');

			return $this;
		}

		public function attach(string $content, string $filename, array $opt = []): self
		{
			if ($content && $filename) {
				$this->attachments[] = [
					'content' => $content,
					'filename' => $filename,
					'opt' => $opt
				];
			}

			return $this;
		}

		public function embedImage(string $path, string $cid): self
		{
			if ($path && $cid) {
				$this->embeddedImages[] = [
					'path' => $path,
					'cid' => $cid
				];
			}

			return $this;
		}

		public function send(): bool
		{
			if (!self::$mailer)
				return false;

			try {
				$mail = clone self::$mailer;
				$mail->CharSet = $this->charset;
				$mail->isHTML($this->contentType === 'text/html');

				$mail->setFrom($this->from);
				foreach ($this->recipients as $email) {
					if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
						$mail->addAddress($email);
					}
				}

				$mail->Subject = $this->subject;
				$mail->Body = $this->body;

				foreach ($this->headers as $header) {
					[$key, $value] = explode(':', $header, 2);
					$mail->addCustomHeader(trim($key), trim($value));
				}

				foreach ($this->attachments as $file) {
					$mail->addStringAttachment(
						$file['content'],
						$file['filename'],
						$file['opt']['encoding'] ?? 'base64',
						$file['opt']['type'] ?? 'application/octet-stream'
					);
				}

				foreach ($this->embeddedImages as $image)
					$mail->addEmbeddedImage($image['path'], $image['cid']);

				foreach ($this->cc as $email)
					$mail->addCC($email);

				foreach ($this->bcc as $email)
					$mail->addBCC($email);

				foreach ($this->replyTo ?? [] as $reply)
					$mail->addReplyTo($reply['email'], $reply['name']);

				$mail->send();
				return true;
			} catch (Exception $e) {
				self::logError($e);
				return false;
			}
		}

		private static function logError(Exception $e): void
		{
			if (!self::$logger)
				self::$logger = new Logger('../logs', logFile: 'mail.log');

			self::$logger->error($e->getMessage(), [
				'file' => $e->getFile(),
				'line' => $e->getLine(),
				'trace' => $e->getTraceAsString()
			]);
		}
	}
