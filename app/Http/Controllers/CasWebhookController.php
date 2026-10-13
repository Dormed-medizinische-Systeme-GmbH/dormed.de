<?php

namespace App\Http\Controllers;

use App\Http\Requests\CasWebhookRequest;
use App\Jobs\SyncCalendarEvent;
use App\Jobs\SyncUsedDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CasWebhookController extends Controller
{
    /**
     * Trigger only: the payload carries no device data, the queued job
     * fetches the current state from the CAS API. "action" (new/changed/
     * deleted) is logged but not acted on - the job reconciles against the
     * CAS view either way.
     */
    public function usedDevice(CasWebhookRequest $request): JsonResponse
    {
        $guid = $request->validated('gguid');

        Log::channel('webhook')->info('CAS webhook: used device changed.', [
            'gguid' => $guid,
            'action' => $request->validated('action'),
        ]);

        SyncUsedDevice::dispatch($guid);

        return response()->json(['status' => 'queued'], 202);
    }

    /**
     * Same trigger-only contract for events (CAS APPOINTMENT records).
     */
    public function calendarEvent(CasWebhookRequest $request): JsonResponse
    {
        $guid = $request->validated('gguid');

        Log::channel('webhook')->info('CAS webhook: event changed.', [
            'gguid' => $guid,
            'action' => $request->validated('action'),
        ]);

        SyncCalendarEvent::dispatch($guid);

        return response()->json(['status' => 'queued'], 202);
    }
}
