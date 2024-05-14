<?php

namespace App\Tools\Scripts;

use App\Tools\Script;
use Lqd\Userfiles\Userfiles;

class CreateUserfiles extends Script
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
        $this->userfiles->createDirectories($this->getBaseDir());
    }
}
