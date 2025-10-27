<?php

    use App\Databases\Schema;
    use App\Databases\Handler\Blueprints\Table;

    class CreateUsersTable
    {
        /**
         * Run the migrations.
         */
        public function up(): void
        {
            Schema::create('users', function (Table $table) {
                $table->id();
                $table->string('name', 100);
                $table->string('email', 150);
                $table->string('password');
                $table->string('role', 50);
                $table->string('remember_token');
                $table->string('api_token');
                $table->string('reset_token');
                $table->datetime('reset_expires');
                $table->timestamp('last_login');
                $table->timestamp('created_at')->defaultNow();

                $table->unique('email');
            });
        }

        /**
         * Reverse the migrations.
         */
        public function down(): void
        {
            Schema::dropIfExists('users');
        }
    }
