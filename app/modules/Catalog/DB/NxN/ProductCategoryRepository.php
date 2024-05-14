<?php

namespace App\Catalog\DB\NxN;

class ProductCategoryRepository extends \Storm\Repository
{
	
	public function setAllDeleted()
	{
		$this->many()->update(['deleted' => true]);
	}
}