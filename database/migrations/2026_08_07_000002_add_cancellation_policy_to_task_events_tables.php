<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCancellationPolicyToTaskEventsTables extends Migration
{
    /**
     * Run the migrations.
     *
     * Override de la política de cancelación por evento o schedule.
     * Null significa "hereda": la cadena evento → schedule → settings del
     * profesional → default genérico se resuelve al enviar el recordatorio.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('task_event_schedules', function (Blueprint $table) {
            $table->unsignedSmallInteger('cancellation_notice_hours')->nullable()->after('reminder_minutes_before');
            $table->string('cancellation_fee_type', 32)->nullable()->after('cancellation_notice_hours');
            $table->decimal('cancellation_fee_value', 10, 2)->nullable()->after('cancellation_fee_type');
        });

        Schema::table('task_events', function (Blueprint $table) {
            $table->unsignedSmallInteger('cancellation_notice_hours')->nullable()->after('reminder_minutes_before');
            $table->string('cancellation_fee_type', 32)->nullable()->after('cancellation_notice_hours');
            $table->decimal('cancellation_fee_value', 10, 2)->nullable()->after('cancellation_fee_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('task_event_schedules', function (Blueprint $table) {
            $table->dropColumn(['cancellation_notice_hours', 'cancellation_fee_type', 'cancellation_fee_value']);
        });

        Schema::table('task_events', function (Blueprint $table) {
            $table->dropColumn(['cancellation_notice_hours', 'cancellation_fee_type', 'cancellation_fee_value']);
        });
    }
}
