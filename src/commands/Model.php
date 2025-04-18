<?php

	namespace Commands;

	use App\Console\Command;

	class Model extends Command
	{
		protected string $signature = 'make:model';
		protected string $description = 'Creates model class';

		public function handle(): void
		{
			$this->info('Creating model class...');
			$this->success('Successfully created model class.');
		}
	}
