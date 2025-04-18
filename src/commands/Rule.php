<?php

	namespace Commands;

	use App\Console\Command;

	class Rule extends Command
	{
		protected string $signature = 'make:rule';

		protected string $description = 'Creates a validation rule class';

		public function handle(): void
		{
			$this->info('Creating new validation rule class...');
			$this->success('Validation rule class created successfully.');
		}
	}
