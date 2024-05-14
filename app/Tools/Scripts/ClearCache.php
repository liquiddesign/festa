<?php

namespace App\Tools\Scripts;

use App\Tools\Script;
use Nette\Utils\FileSystem;

class ClearCache extends Script
{
    /**
     * Run script
     *
     * @throws \Nette\Application\ApplicationException
     * @return void
     */
    public function doClearCache(): void
    {
        $directory = 'temp/cache';
        FileSystem::delete($this->getBaseDir() . '/' . $directory);
        $this->write('Nette cache úspěšně vymazána.');
    }
}
