<?php

namespace App\Tools\Scripts;

use App\Tools\Script;

class Phpinfo extends Script
{
    public function doPhpinfo(): void
    {
        \phpinfo();
    }
}
