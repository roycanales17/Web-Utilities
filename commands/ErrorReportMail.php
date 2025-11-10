<?php

	namespace Commands;
	
	use App\Console\Command;
	
	class ErrorReportMail extends Command {
		
		protected string $signature = 'make:email_report';
		protected string $description = 'Creates an exception email reporting template.';
		
		public function handle(): void
		{
			$classInfo = $this->extractClassInfo('ErrorReportMail', 'handler/Mails');
			$basePath = base_path('/' . $classInfo['directory']);
			$content = <<<PHP
			<?php
				namespace {$classInfo['namespace']};
			
				use App\Utilities\Handler\Mailable;
				use Exception;
				use Error;
			
				class {$classInfo['class']} extends Mailable {
			
					private Error|Exception \$exception;
					private string \$to;
					private string \$ticker;
			
					public function __construct(Error|Exception \$exception, string \$ticker) {
						\$this->to = env('ERROR_MAIL_RECEIVER', 'receiver@example.com');
						\$this->ticker = \$ticker;
						\$this->exception = \$exception;
					}
			
					public function send(): bool {
						\$appName = env('APP_NAME', 'Framework');
						\$ticker = \$this->ticker;
						\$subject = "🚨 [{\$appName}] Exception Detected #{\$ticker}";
						\$from = env('MAIL_FROM_ADDRESS', 'support@example.com');
			
						return \$this->view('errors.mails.email-report', ['exception' => \$this->exception])
							->contentType('text/html')
							->subject(\$subject)
							->from(\$from)
							->to(\$this->to)
							->queue();
					}
				}
			PHP;

			$source = $this->getRealPath("resources/blades/email-report.blade.php");
			$destination_dir = base_path($this->getDefaultDirectoryView());
			$destination = $destination_dir . '/errors/mails/email-report.blade.php';

			if (!$this->createDirectory($destination_dir)) {
				return;
			}

			if (!$this->moveFile($source, $destination)) {
				return;
			}

			$this->create($classInfo['class'] . '.php', $content, $basePath);
		}

		protected function getRealPath(string $path): string
		{
			$path = trim($path, '/');
			return realpath(__DIR__ . "/../$path");
		}
	}