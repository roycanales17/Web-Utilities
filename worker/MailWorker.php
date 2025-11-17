<?php

	use App\Utilities\Mail;

	try {
		if (!function_exists('findProjectRoot')) {
			function findProjectRoot($startDir = __DIR__) {
				$dir = realpath($startDir);
				while ($dir && $dir !== '/' && !file_exists($dir . '/vendor/autoload.php')) {
					$dir = dirname($dir);
				}
				return $dir ?: null;
			}
		}

		$base = findProjectRoot(__DIR__);
		if (!$base) {
			throw new Exception("Could not locate project root containing vendor/autoload.php");
		}

		require_once $base . '/vendor/autoload.php';

		// --- CLI argument check ---
		if ($argc < 2) {
			throw new Exception("Missing file argument.");
		}

		$file = $argv[1];
		if (!file_exists($file)) {
			throw new Exception("Queue file not found: {$file}");
		}

		// --- Load and decode ---
		$data = json_decode(file_get_contents($file), true);
		if (!$data || !is_array($data)) {
			throw new Exception("Invalid email payload.");
		}

		// --- Determine log file early ---
		$logDir = base_path('storage/private/logs/app/mails');
		if (!is_dir($logDir)) {
			mkdir($logDir, 0777, true);
		}

		$logFile = !empty($data['logFilename'])
			? "{$logDir}/processed/{$data['logFilename']}.log"
			: "{$logDir}/mail-worker.log";

		// --- Prepare log header ---
		$recipients = implode(', ', $data['recipients'] ?? []);
		$subject = $data['subject'] ?? '(no subject)';
		$sender = $data['from'] ?? '(unknown sender)';

		$startTime = microtime(true);
		file_put_contents($logFile, str_repeat('-', 80) . PHP_EOL, FILE_APPEND);
		file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] 🚀 Start sending email\n", FILE_APPEND);
		file_put_contents($logFile, "From: {$sender}\nTo: {$recipients}\nSubject: {$subject}\n", FILE_APPEND);

		// --- Configure mail ---
		$conf = $data['configurations'] ?? [];
		if (!empty($conf)) {
			file_put_contents($logFile, "Mail Config: " . json_encode($conf, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
			Mail::configure(
				$conf['host'] ?? 'localhost',
				(int)($conf['port'] ?? 587),
				$conf['encryption'] ?? 'tls',
				$conf['mailer'] ?? 'smtp',
				$conf['credentials'] ?? []
			);
		} else {
			file_put_contents($logFile, "⚠️ No mail configuration found in payload.\n", FILE_APPEND);
		}

		// --- Build mail ---
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

		foreach ($data['cc'] ?? [] as $cc) $mail->cc($cc);
		foreach ($data['bcc'] ?? [] as $bcc) $mail->bcc($bcc);
		foreach ($data['replyTo'] ?? [] as $reply)
			if (!empty($reply['email'])) $mail->replyTo($reply['email'], $reply['name'] ?? '');

		foreach ($data['attachments'] ?? [] as $attachment)
			if (!empty($attachment['content']) && !empty($attachment['filename']))
				$mail->attach($attachment['content'], $attachment['filename'], $attachment['opt'] ?? []);

		foreach ($data['embeddedImages'] ?? [] as $image)
			if (!empty($image['path']) && !empty($image['cid']))
				$mail->embedImage($image['path'], $image['cid']);

		// --- Attempt to send mail ---
		try {
			$mail->send();

			$duration = round(microtime(true) - $startTime, 2);
			file_put_contents(
				$logFile,
				'[' . date('Y-m-d H:i:s') . "] ✅ SUCCESS: Sent \"{$subject}\" to {$recipients} in {$duration}s\n",
				FILE_APPEND
			);
		} catch (Throwable $sendError) {
			$duration = round(microtime(true) - $startTime, 2);
			file_put_contents(
				$logFile,
				'[' . date('Y-m-d H:i:s') . "] ❌ FAILED sending \"{$subject}\" to {$recipients} after {$duration}s\n",
				FILE_APPEND
			);
			file_put_contents($logFile, "Error: {$sendError->getMessage()}\nFile: " . basename($sendError->getFile()) . ':' . $sendError->getLine() . "\n", FILE_APPEND);
			file_put_contents($logFile, "Trace:\n" . $sendError->getTraceAsString() . "\n", FILE_APPEND);
		}

	} catch (Throwable $e) {
		$errorMessage = '[' . date('Y-m-d H:i:s') . '] ❌ General Error: ' . $e->getMessage();
		if ($e->getFile()) $errorMessage .= ' in ' . basename($e->getFile()) . ':' . $e->getLine();
		file_put_contents($logFile ?? __DIR__ . '/mail-worker-fatal.log', $errorMessage . PHP_EOL, FILE_APPEND);
	} finally {
		if (isset($file) && file_exists($file)) {
			@unlink($file);
		}
		file_put_contents($logFile, '[' . date('Y-m-d H:i:s') . "] 🧹 Cleaned up queue file.\n", FILE_APPEND);
		file_put_contents($logFile, str_repeat('-', 80) . PHP_EOL, FILE_APPEND);
	}