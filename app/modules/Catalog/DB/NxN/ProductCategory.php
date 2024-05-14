<?php

namespace App\Catalog\DB\NxN;

/**
 * @table{"name":"catalog_nxn_productcategory"}
*/
class ProductCategory extends \Storm\Model
{
	/**
	* Category
	* @relation{"\\App\\Catalog\\DB\\Category":"fk_category"}
	* @constraint
	* @column{"name":"fk_category"}
	* @var \App\Catalog\DB\Category
	*/
	public $category;
	
	/**
	* Product
	* @relation{"\\App\\Catalog\\DB\\Product":"fk_product"}
	* @constraint
	* @column{"name":"fk_product"}
	* @var \App\Catalog\DB\Product
	*/
	public $product;
	
	/**
	 * @column{"default":0}
	 */
	public $deleted = false;
}