<?php

use App\Domains\Tasks\Enums\EventNotificationStatus;
use App\Domains\Tasks\Enums\EventNotificationType;
use App\Domains\Tasks\Enums\TaskEventStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')
                ->constrained('tasks')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('description', 512)->nullable();
            $table->string('status', 32)->default(TaskEventStatus::PENDING);
            $table->dateTime('scheduled_at');
            $table->dateTime('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('event_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_event_id')
                ->constrained('task_events')
                ->onDelete('cascade');
            $table->string('type', 32)->default(EventNotificationType::PUSH);
            $table->string('status', 32)->default(EventNotificationStatus::PENDING);
            $table->dateTime('send_at');
            $table->dateTime('sent_at')->nullable();
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
        Schema::dropIfExists('event_notifications');
        Schema::dropIfExists('task_events');
    }
}
