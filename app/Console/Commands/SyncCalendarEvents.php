<?php

namespace App\Console\Commands;

use App\Services\CalendarEvents\CalendarEventSynchronizer;
use App\Services\Cas\CasClient;
use App\Services\Cas\CasRequestFailedException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('calendar-events:sync {gguid?* : GGUIDs to import; without any, all locally known events are re-synced}')]
#[Description('Fetch events (CAS appointments) by GGUID (imports new ones, updates known ones, removes deleted or unpublished ones)')]
class SyncCalendarEvents extends Command
{
    public function handle(CalendarEventSynchronizer $synchronizer): int
    {
        try {
            $guids = collect($this->argument('gguid'))->map(CasClient::normalizeGuid(...));

            if ($guids->isEmpty()) {
                $count = $synchronizer->syncKnown();
            } else {
                $guids->each(fn (string $guid) => $synchronizer->sync($guid));
                $count = $guids->count();
            }
        } catch (CasRequestFailedException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("{$count} Veranstaltungen synchronisiert.");

        return self::SUCCESS;
    }
}
