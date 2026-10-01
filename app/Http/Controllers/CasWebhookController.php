<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsedDeviceWebhookRequest;
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
    public function usedDevice(UsedDeviceWebhookRequest $request): JsonResponse
    {
        $guid = $request->validated('gguid');

        Log::channel('api')->info('CAS webhook: used device changed.', [
            'gguid' => $guid,
            'action' => $request->validated('action'),
        ]);

        SyncUsedDevice::dispatch($guid);

        return response()->json(['status' => 'queued'], 202);
    }
}
