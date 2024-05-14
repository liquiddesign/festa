<?php

namespace App\Tools\Scripts;

use App\Tools\Script;
use Storm\Migrator;

class BackupDatabase extends Script
{
    /**
     * @var \Storm\Migrator
     */
    private $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    public function doBackupDatabase(): void
    {
        $args = $this->getArguments();

        $filename = $args[0] ?? \date('d-m-y-G-i') . '.sql';

        $this->migrator->databaseBackup($filename);
    }
}
