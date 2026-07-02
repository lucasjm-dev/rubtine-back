<?php

namespace App\Console\Commands;

use App\Domains\Tasks\Enums\EventNotificationStatus;
use App\Domains\Tasks\Enums\EventNotificationType;
use App\Domains\Tasks\Enums\TaskEventStatus;
use App\Domains\Tasks\Jobs\SendEventReminderJob;
use App\Domains\Tasks\Models\TaskEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Busca los TaskEvent cuya ventana de recordatorio ya venció
 * (scheduled_at - reminder_minutes_before <= ahora <= scheduled_at)
 * y despacha un SendEventReminderJob por cada uno.
 *
 * Pensado para correr cada minuto desde el scheduler. Evita duplicados
 * descartando eventos que ya tienen una notificación WhatsApp en estado SENT.
 */
class SendEventReminders extends Command
{
    protected $signature = 'events:send-reminders';

    protected $description = 'Dispatch WhatsApp reminders for task events whose reminder window is due';

    public function handle(): int
    {
        $now = Carbon::now();
        logger()->info('now', [$now]);
        $events = TaskEvent::query()
            ->whereNotNull('reminder_minutes_before')
            ->where('status', TaskEventStatus::PENDING)
            ->where('scheduled_at', '>=', $now)
            ->whereRaw("scheduled_at - (reminder_minutes_before * interval '1 minute') <= ?", [$now])
            // No mandamos recordatorios "tardíos" (catch-up). El aviso solo es
            // válido si su momento ideal (scheduled_at - reminder_minutes_before)
            // todavía era futuro la última vez que se escribió el evento. Si el
            // evento se creó o editó DESPUÉS de ese momento, la ventana ya había
            // vencido y no tiene sentido enviarlo. updated_at cubre ambos casos
            // (creación y edición) porque Eloquent lo actualiza en los dos.
            ->whereRaw("scheduled_at - (reminder_minutes_before * interval '1 minute') >= updated_at")
            ->whereHas('task.beneficiary', fn($q) => $q->whereNotNull('phone'))
            ->whereDoesntHave('notifications', function ($q) {
                $q->where('type', EventNotificationType::WHATSAPP);
            })
            ->get();
        logger()->info('Eventos encontrados: ' . $events->count());

        foreach ($events as $event) {
            // "Reclamamos" el evento creando una notificación PENDING. Esto evita
            // que el próximo tick del scheduler lo vuelva a despachar mientras el
            // Job todavía está en la cola (relevante con QUEUE_CONNECTION async).
            // El Job luego pasa esta notificación a SENT o FAILED.
            $event->notifications()->create([
                'type'    => EventNotificationType::WHATSAPP,
                'status'  => EventNotificationStatus::PENDING,
                'send_at' => $now,
            ]);

            SendEventReminderJob::dispatch($event->id);
        }

        $this->info("Dispatched {$events->count()} reminder(s).");

        return self::SUCCESS;
    }
}
