<?php

	namespace Commands;
	
	use App\Console\Command;
	use App\utilities\Server;
	use Illuminate\Database\Facades\Blueprint;
	use Illuminate\Database\Facades\Schema;

	class SessionHandler extends Command {
		
		protected string $signature = 'make:session';
		protected string $description = 'Migrate database session';
		
		public function handle(): void
		{
			if (!Schema::hasTable('sessions')) {
				Schema::create('sessions', function (Blueprint $table) {
					$table->int('id', 128)->autoIncrement();
					$table->text('data');
					$table->string('ip_address', 128)->default(Server::IPAddress());
					$table->dateTime('last_activity' )->current_date()->on_update();
				});

				$this->success("Sessions table was created.");
				return;
			}

			$this->warn("Sessions table already exists.");
		}
	}