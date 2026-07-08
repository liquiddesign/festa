<?php

namespace App\Custom\DB;

use Storm\Model;

/**
 * @table{"name":"custom_qrcode"}
 */
class QrCode extends Model
{
	/**
	 * @column{"unique":true}
	 * @var string
	 */
	public $sourceUrl = '';

	/**
	 * @column
	 * @var string
	 */
	public $targetUrl = '';

	/**
	 * @column{"type":"datetime","default":"CURRENT_TIMESTAMP"}
	 */
	public $createdTs;

	/**
	 * @column{"type":"datetime","nullable":true}
	 */
	public $updatedTs;
}
