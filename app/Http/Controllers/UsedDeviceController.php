<?php

namespace App\Http\Controllers;

use App\Services\UsedDevices\UsedDeviceCatalog;
use Illuminate\Contracts\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class UsedDeviceController extends Controller
{
    public function index(UsedDeviceCatalog $catalog): View
    {
        $devices = $catalog->all();

        return view('ultraschallgeraete.gebraucht2', [
            'devices' => $devices,
            'brandCounts' => $catalog->countBy($devices, 'brand'),
            'systemCounts' => $catalog->countBy($devices, 'system'),
            'yearBandCounts' => $catalog->countBy($devices, 'yearBand'),
            'brandLabels' => $devices->pluck('brandLabel', 'brand'),
            'systems' => UsedDeviceCatalog::SYSTEMS,
            'yearBands' => UsedDeviceCatalog::YEAR_BANDS,
        ]);
    }

    /**
     * Serves a device image out of CAS, so the browser never talks to CAS
     * (and never sees its credentials). Only devices from the online list
     * resolve to an image.
     */
    public function image(UsedDeviceCatalog $catalog, string $id): BinaryFileResponse
    {
        $path = $catalog->imagePath($id);

        abort_if($path === null, 404);

        return response()->file($path, ['Cache-Control' => 'public, max-age=86400']);
    }
}
