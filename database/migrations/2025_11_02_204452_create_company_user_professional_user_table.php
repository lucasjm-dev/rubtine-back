<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyUserProfessionalUserTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_user_professional_user', function (Blueprint $table) {
            $table->id();

            $table->foreignId('company_user_id')
                ->constrained('company_users')
                ->onDelete('cascade');

            $table->foreignId('professional_user_id')
                ->constrained('professional_users')
                ->onDelete('cascade');

            $table->boolean('approved')->default(false);

            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('company_user_professional_user');
    }
}
