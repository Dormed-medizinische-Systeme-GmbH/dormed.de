<?php

namespace App\Jobs;

use App\Services\CalendarEvents\CalendarEventSynchronizer;
use DateTime;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Brings one event in line with CAS (create/update/delete). Retried until
 * {@see retryUntil()} because of the nightly CAS reboot window, like the
 * other CAS jobs. Unique per event so a burst of webhooks for the same
 * appointment collapses into one run.
 */
class SyncCalendarEvent implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public function __construct(public string $guid) {}

    public function uniqueId(): string
    {
        return $this->guid;
    }

    public function retryUntil(): DateTime
    {
        return now()->addHours(3);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 120, 300, 600];
    }

    public function handle(CalendarEventSynchronizer $synchronizer): void
    {
        $synchronizer->sync($this->guid);
    }

    public function failed(Throwable $exception): void
    {
        Log::channel('webhook')->error('SyncCalendarEvent permanently failed.', [
            'gguid' => $this->guid,
            'message' => $exception->getMessage(),
        ]);
    }
}
