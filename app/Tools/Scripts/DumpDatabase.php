<?php

namespace App\Tools\Scripts;

use App\Tools\Script;
use Storm\Migrator;

class DumpDatabase extends Script
{
    /**
     * @var \Storm\Migrator
     */
    private $migrator;

    public function __construct(Migrator $migrator)
    {
        $this->migrator = $migrator;
    }

    public function doDumpDatabase(): void
    {
        $this->migrator->sqlDump();
    }
}
