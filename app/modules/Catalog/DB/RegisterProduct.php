<?php

namespace App\Catalog\DB;

use Storm\Model;

/**
 * @table{"name":"register_product"}
 */
class RegisterProduct extends Model
{
	/**
	 * @column
	 */
	public $fullName = '';

	/**
	 * @column
	 */
	public $email = '';

	/**
	 * @column
	 */
	public $company = '';

	/**
	 * @column
	 */
	public $seller = '';

	/**
	 * @column
	 */
	public $specialization = '';

	/**
	 * @column
	 */
	public $serialNumber = '';

	/**
	 * Product
	 * @relation{"Product":"fk_product"}
	 * @constraint
	 * @column{"name":"fk_product"}
	 * @var Product
	 */
	public $product;

	/**
	 * @column{"type":"datetime","nullable":true}
	 */
	public $orderTs;

	/**
	 * @column{"type":"datetime","nullable":true,"default":"CURRENT_TIMESTAMP"}
	 */
	public $createdTs;
}
