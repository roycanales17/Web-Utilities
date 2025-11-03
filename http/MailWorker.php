<?php

	use App\Utilities\Mail;

	require base_path('vendor/autoload.php');

	// --- CLI argument check ---
	if ($argc < 2) {
		exit("Missing file argument.\n");
	}

	$file = $argv[1];
	if (!file_exists($file)) {
		exit("Queue file not found: {$file}\n");
	}

	// --- Load and decode ---
	$data = json_decode(file_get_contents($file), true);
	if (!$data || !is_array($data)) {
		exit("Invalid email payload.\n");
	}

	// --- Determine log file early ---
	$logDir = base_path('storage/logs/app/mails');
	if (!is_dir($logDir)) {
		mkdir($logDir, 0777, true);
	}

	// Use custom log name or fallback
	$logFile = !empty($data['logFilename'])
		? "{$logDir}/{$data['logFilename']}.log"
		: "{$logDir}/mail_worker.log";

	try {
		// --- Build the mail ---
		$mail = Mail::to($data['recipients'])
			->from($data['from'])
			->subject($data['subject'])
			->body($data['body'])
			->charset($data['charset'] ?? 'UTF-8')
			->contentType($data['contentType'] ?? 'text/html');

		foreach ($data['headers'] ?? [] as $header) {
			if (strpos($header, ':') !== false) {
				[$k, $v] = explode(':', $header, 2);
				$mail->header(trim($k), trim($v));
			}
		}

		foreach ($data['cc'] ?? [] as $cc) {
			$mail->cc($cc);
		}

		foreach ($data['bcc'] ?? [] as $bcc) {
			$mail->bcc($bcc);
		}

		foreach ($data['replyTo'] ?? [] as $reply) {
			if (!empty($reply['email'])) {
				$mail->replyTo($reply['email'], $reply['name'] ?? '');
			}
		}

		foreach ($data['attachments'] ?? [] as $attachment) {
			if (!empty($attachment['content']) && !empty($attachment['filename'])) {
				$mail->attach($attachment['content'], $attachment['filename'], $attachment['opt'] ?? []);
			}
		}

		foreach ($data['embeddedImages'] ?? [] as $image) {
			if (!empty($image['path']) && !empty($image['cid'])) {
				$mail->embedImage($image['path'], $image['cid']);
			}
		}

		// --- Send mail ---
		$mail->send();

		// Log success
		file_put_contents(
			$logFile,
			'[' . date('Y-m-d H:i:s') . '] ✅ Sent: ' . ($data['subject'] ?? '(no subject)') . PHP_EOL,
			FILE_APPEND
		);

	} catch (Throwable $e) {
		// Log failure (always available since $logFile defined early)
		$errorMessage = '[' . date('Y-m-d H:i:s') . '] ❌ Error: ' . $e->getMessage();
		if ($e->getFile()) {
			$errorMessage .= ' in ' . basename($e->getFile()) . ':' . $e->getLine();
		}
		$errorMessage .= PHP_EOL;

		file_put_contents($logFile, $errorMessage, FILE_APPEND);
	} finally {
		@unlink($file); // cleanup queue file
	}