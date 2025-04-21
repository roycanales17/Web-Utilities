<?php

	namespace Commands;
	
	use App\Console\Command;
	use App\Console\Terminal;

	class ClearApp extends Command {
		
		protected string $signature = 'clear:app';
		protected string $description = 'Reset app: clear logs, config cache, routes, and views';
		
		public function handle(): void
		{
			if (!$this->confirm('Are you sure you want to reset the application? This will only clean logs, config, routes, and views.')) {
				$this->info("Reset cancelled.\n");
				return;
			}

			Terminal::handle('clear:logs', ['force' => true]);
			Terminal::handle('clear:config', ['force' => true]);
			Terminal::handle('clear:directory', ['path' => 'routes']);
			Terminal::handle('clear:directory', ['path' => 'views']);

			$this->success('Application cleared.');
		}
	}