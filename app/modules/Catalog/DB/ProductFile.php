<?php

namespace App\Catalog\DB;

/**
 * @table{"name":"catalog_product_file"}
 */
class ProductFile extends \Storm\Model
{

	/**
	 * Product
	 * @relation{"Product":"fk_product"}
	 * @constraint
	 * @column{"name":"fk_product"}
	 * @var Product
	 */
	public $product;


	/**
	 * @column
	 */
	public $file = '';

	/**
	 * @column{"nullable":true}
	 */
	public $description = '';

	/**
	 * @column{"default":0}
	 */
	public $priority = 0;

	/**
	 * @column{"default":0}
	 */
	public $hidden = 0;


	public function getConvertedFile()
    {
        return $this->file;
    }
}