<?php

namespace App\Tools\Scripts;

use App\Tools\Script;
use Lqd\Userfiles\Userfiles;

class RegenerateUserfiles extends Script
{
    /**
     * @var \Lqd\Userfiles\Userfiles
     */
    private $userfiles;

    public function __construct(Userfiles $userfiles)
    {
        $this->userfiles = $userfiles;
    }

    public function doCreateUserfiles(): void
    {
        $args = $this->getArguments();
        $type = $args[0];
        $from = $args[1];
        $to = $args[2];

        $this->userfiles->regenerate($this->getBaseDir(), $type, $from, $to);
    }
}
