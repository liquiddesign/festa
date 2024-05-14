<?php

namespace App\Tools\Scripts;

use App\Tools\Script;
use Nette\Utils\FileSystem;

class Maintenance extends Script
{
    /**
     * Do maintenance
     *
     * @throws \Nette\Application\ApplicationException
     */
    public function doMaintenance(): void
    {
        $args = $this->getArguments();
        $fileOff = $this->getBaseDir() . '/public/.maintenance.php';
        $fileOn = $this->getBaseDir() . '/public/maintenance.php';

        if (!\in_array(@$args[0], ['on', 'off'])) {
            $this->writeError('Špatný parameter.');

            return;
        }

        if ($args[0] === 'on') {
            if ($fileOff) {
                FileSystem::rename($fileOff, $fileOn);
            }

            $this->write('Maintenance mód aktivován.');
        } else {
            if (\is_file($fileOn)) {
                FileSystem::rename($fileOn, $fileOff);
            }

            $this->write('Maintenance mód deaktivován.');
        }
    }
}
