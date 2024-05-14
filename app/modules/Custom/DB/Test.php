<?php

namespace App\Custom\DB;

use Storm\Model;

/**
 * @table{"table":"test"}
 */
class Test extends Model
{
    /**
     * @column
     * @var string
     */
    public $name = '';
}
