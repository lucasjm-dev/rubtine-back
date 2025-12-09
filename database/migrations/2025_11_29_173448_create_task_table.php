<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('subcategory_id')->constrained('subcategories')->onDelete('restrict');

            $table->string('title', 256)->nullable();
            $table->string('description', 256)->nullable();
            $table->string('status', 32)->default('DRAFT');
            $table->boolean('public')->default(false);


            $table->timestamps();
        });
    }

    /**|
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
