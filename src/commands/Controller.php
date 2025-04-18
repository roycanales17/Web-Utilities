<?php

	namespace Commands;

	use App\Console\Command;

	class Controller extends Command
	{
		protected string $signature = 'make:controller';
		protected string $description = 'Creates controller class';

		public function handle(): void
		{
			$this->info('Creating controller class...');
			$this->success('Successfully created controller class.');
		}
	}
