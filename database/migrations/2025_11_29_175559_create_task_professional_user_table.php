<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskProfessionalUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_professional_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('tasks')
                ->onDelete('cascade');

            $table->foreignId('professional_user_id')
                ->constrained('professional_users')
                ->onDelete('cascade');

            $table->string('status', 32)->default('PENDING');

            $table->timestamps();
            // Avoid duplicate
            $table->unique(['task_id', 'professional_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('task_professional_user');
    }
}
