<?php

	namespace Commands;

	use App\Console\Command;

	class Component extends Command
	{
		protected string $signature = 'make:component';
		protected string $description = 'Creates stream wire component';

		public function handle($className = ''): void
		{
			$this->info('Creating stream wire component...');
			$this->success('Successfully created stream wire component.');
		}
	}
