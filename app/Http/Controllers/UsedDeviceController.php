<?php

namespace App\Http\Controllers;

use App\UsedDevice;
use Illuminate\Contracts\View\View;

class UsedDeviceController extends Controller
{
    public function index(): View
    {
        $devices = UsedDevice::query()->orderBy('name')->get();

        return view('ultraschallgeraete.gebraucht2', [
            'devices' => $devices,
            'brandCounts' => $devices->countBy('brand'),
            'systemCounts' => $devices->countBy('system'),
            'yearBandCounts' => $devices->countBy('yearBand'),
            'brandLabels' => $devices->pluck('brandLabel', 'brand'),
            'systems' => UsedDevice::SYSTEMS,
            'yearBands' => UsedDevice::YEAR_BANDS,
        ]);
    }
}
