<?php

	namespace App\Utilities\Handler;

	use App\Utilities\Mail;

	abstract class Mailable
	{
		private array $to = [];
		private string $from = '';
		private string $subject = '';
		private string $replyTo = '';
		private string $charset = 'UTF-8';
		private string $contentType = 'text/html';
		private int $priority = 3;
		private array $cc = [];
		private array $bcc = [];
		private array $attachments = [];
		private array $embedded = [];
		private string $body = '';
		private string $plainBody = '';
		private string $altBody = '';

		/**
		 * Render the HTML view.
		 */
		protected function view(string $view, array $data = []): self
		{
			$this->body = view($view, $data);
			$this->plainBody = strip_tags(
				preg_replace(['/<br\s*\/?>/i', '/<\/p>/i'], ["\n", "\n\n"], $this->body)
			);

			return $this;
		}

		/**
		 * Render plain text view.
		 */
		protected function text(string $view, array $data = []): self
		{
			$this->altBody = view($view, $data);
			return $this;
		}

		protected function subject(string $subject): self
		{
			$this->subject = $subject;
			return $this;
		}

		protected function from(string $from): self
		{
			$this->from = $from;
			return $this;
		}

		/**
		 * Accepts single or multiple recipients.
		 */
		protected function to(string|array $to): self
		{
			$emails = is_array($to) ? $to : [$to];
			$this->to = array_merge($this->to, array_filter($emails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
			return $this;
		}

		protected function cc(string|array $cc): self
		{
			$emails = is_array($cc) ? $cc : [$cc];
			$this->cc = array_merge($this->cc, array_filter($emails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
			return $this;
		}

		protected function bcc(string|array $bcc): self
		{
			$emails = is_array($bcc) ? $bcc : [$bcc];
			$this->bcc = array_merge($this->bcc, array_filter($emails, fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL)));
			return $this;
		}

		protected function replyTo(string $replyTo): self
		{
			$this->replyTo = $replyTo;
			return $this;
		}

		protected function attach(string $content, string $filename, array $opt = []): self
		{
			$this->attachments[] = compact('content', 'filename', 'opt');
			return $this;
		}

		protected function embed(string $path, string $cid = null): self
		{
			$this->embedded[] = ['path' => $path, 'cid' => $cid ?? md5($path)];
			return $this;
		}

		protected function priority(int $priority): self
		{
			$this->priority = $priority;
			return $this;
		}

		protected function charset(string $charset): self
		{
			$this->charset = $charset;
			return $this;
		}

		protected function contentType(string $contentType): self
		{
			$this->contentType = $contentType;
			return $this;
		}

		/**
		 * Build and send the email.
		 */
		protected function build(): bool
		{
			$mail = Mail::to($this->to);

			$mail->charset($this->charset);
			$mail->contentType($this->contentType);
			$mail->subject($this->subject);
			$mail->from($this->from);
			$mail->body($this->body ?: $this->plainBody);

			if ($this->replyTo)
				$mail->header('Reply-To', $this->replyTo);

			if ($this->priority)
				$mail->header('X-Priority', (string) $this->priority);

			foreach ($this->cc as $email)
				$mail->cc($email);

			foreach ($this->bcc as $email)
				$mail->bcc($email);

			foreach ($this->embedded as $image)
				$mail->embedImage($image['path'], $image['cid']);

			foreach ($this->attachments as $attachment)
				if (isset($attachment['content'], $attachment['filename']))
					$mail->attach(
						$attachment['content'],
						$attachment['filename'],
						$attachment['opt'] ?? []
					);

			return $mail->send();
		}

		/**
		 * Send asynchronously via queue (background).
		 */
		public function queue(): bool
		{
			$mail = Mail::to($this->to)
				->from($this->from)
				->subject($this->subject)
				->body($this->body ?: $this->plainBody)
				->charset($this->charset)
				->contentType($this->contentType);

			foreach ($this->cc as $email)
				$mail->cc($email);

			foreach ($this->bcc as $email)
				$mail->bcc($email);

			foreach ($this->attachments as $attachment)
				if (isset($attachment['content'], $attachment['filename']))
					$mail->attach(
						$attachment['content'],
						$attachment['filename'],
						$attachment['opt'] ?? []
					);

			foreach ($this->embedded as $image)
				$mail->embedImage($image['path'], $image['cid']);

			return $mail->queue();
		}

		/**
		 * Child classes must define `send()`.
		 */
		abstract public function send(): bool;
	}