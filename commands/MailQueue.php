<?php

	namespace Commands;

	use App\Console\Command;
	use App\Utilities\Mail;
	use Throwable;

	class MailQueue extends Command
	{
		protected string $signature = 'mail:queue';
		protected string $description = 'Process a queued email message';

		public function handle(string $filePath = ''): void
		{
			if (!file_exists($filePath)) {
				$this->error('Payload file not found.');
				return;
			}

			$config = json_decode(file_get_contents($filePath), true);
			unlink($filePath);

			if (empty($config)) {
				$this->error('Invalid or empty email payload.');
				return;
			}

			// Build and send
			$mail = Mail::to($config['recipients'])
				->from($config['from'])
				->subject($config['subject'])
				->body($config['body'])
				->charset($config['charset'])
				->contentType($config['contentType']);

			foreach ($config['cc'] ?? [] as $email)
				$mail->cc($email);

			foreach ($config['bcc'] ?? [] as $email)
				$mail->bcc($email);

			foreach ($config['replyTo'] ?? [] as $reply)
				$mail->replyTo($reply['email'], $reply['name']);

			foreach ($config['headers'] ?? [] as $header) {
				if (str_contains($header, ':')) {
					[$key, $value] = explode(':', $header, 2);
					$mail->header(trim($key), trim($value));
				}
			}

			foreach ($config['attachments'] ?? [] as $file)
				$mail->attach($file['content'], $file['filename'], $file['opt'] ?? []);

			foreach ($config['embeddedImages'] ?? [] as $img)
				$mail->embedImage($img['path'], $img['cid']);

			try {
				$mail->send();
				$this->info("✅ Email successfully sent to: " . implode(', ', $config['recipients']));
			} catch (Throwable $e) {
				$this->error("❌ Failed to send email: {$e->getMessage()}");
			}
		}
	}
