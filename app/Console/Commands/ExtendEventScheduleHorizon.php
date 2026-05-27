<?php

namespace App\Console\Commands;

use App\Domains\Tasks\Services\TaskEventScheduleService;
use Illuminate\Console\Command;

class ExtendEventScheduleHorizon extends Command
{
    protected $signature = 'events:extend-horizon';

    protected $description = 'Extend the horizon of active event schedules by generating upcoming task events';

    public function handle(TaskEventScheduleService $service): int
    {
        $this->info('Extending event schedule horizons...');

        $count = $service->extendAllHorizons();

        $this->info("Done. Extended {$count} schedule(s).");

        return self::SUCCESS;
    }
}
