<?php

namespace App\Console\Commands;

use App\Services\Cas\CasRequestFailedException;
use App\Services\UsedDevices\UsedDeviceSynchronizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('used-devices:sync')]
#[Description('Import all online used devices from CAS and remove the ones that are no longer online')]
class SyncUsedDevices extends Command
{
    public function handle(UsedDeviceSynchronizer $synchronizer): int
    {
        try {
            $count = $synchronizer->syncAll();
        } catch (CasRequestFailedException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("{$count} Gebrauchtgeräte synchronisiert.");

        return self::SUCCESS;
    }
}
