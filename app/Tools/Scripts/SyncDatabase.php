<?php

namespace App\Tools\Scripts;

use App\Tools\Script;
use Storm\Connection;
use Storm\Migrator;

class SyncDatabase extends Script
{
    /**
     * @var \Storm\Connection
     */
    private $stm;

    /**
     * @var \Storm\Migrator
     */
    private $migrator;

    public function __construct(Connection $stm, Migrator $migrator)
    {
        $this->stm = $stm;
        $this->migrator = $migrator;
    }

    public function doSyncDatabase(): void
    {
        $this->getScript(ClearCache::class, [])->doClearCache();


        $this->write('Analyzuji databázi...');


        $sql = $this->migrator->getSyncString($this->getContainer()->parameters['composer']->getPrefixesPsr4());

        if (!$this->isComposerCli()) {
            $this->write($sql);
        }

        if ($sql) {
            $this->write($sql);

            $this->write('------------------------------');

            if ($this->getIO()->askConfirmation("Spustit altery databáze? (y)")) {
                $this->write('Synchronizuji databázi ...', false);
                $this->stm->query($sql);
                $this->write('hotovo.');
            }
        } else {
             $this->write('Databáze je již synchronizována.');
        }
    }
}
