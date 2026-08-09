<?php

use App\Domains\Tasks\Enums\CancellationFeeType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfessionalUserSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * Configuración general del profesional (una fila por professional_user).
     * Arranca con la política de cancelación y la duración de sesión; a medida
     * que aparezcan nuevas configuraciones se agregan como columnas tipadas.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('professional_user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_user_id')
                ->unique()
                ->constrained('professional_users')
                ->onDelete('cascade');

            $table->unsignedSmallInteger('cancellation_notice_hours')->default(24);
            $table->string('cancellation_fee_type', 32)->default(CancellationFeeType::FULL_SESSION);
            $table->decimal('cancellation_fee_value', 10, 2)->nullable();

            $table->unsignedSmallInteger('session_duration_minutes')->nullable();

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
        Schema::dropIfExists('professional_user_settings');
    }
}
