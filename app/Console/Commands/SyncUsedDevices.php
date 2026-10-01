<?php

namespace App\Console\Commands;

use App\Services\Cas\CasRequestFailedException;
use App\Services\UsedDevices\UsedDeviceSynchronizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('used-devices:sync {gguid?* : GGUIDs to import; without any, all locally known devices are re-synced}')]
#[Description('Fetch used devices from CAS by GGUID (imports new ones, updates known ones, removes deleted ones)')]
class SyncUsedDevices extends Command
{
    public function handle(UsedDeviceSynchronizer $synchronizer): int
    {
        try {
            $guids = collect($this->argument('gguid'))->map(UsedDeviceSynchronizer::normalizeGuid(...));

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

        $this->info("{$count} Gebrauchtgeräte synchronisiert.");

        return self::SUCCESS;
    }
}
