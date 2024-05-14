<?php

namespace App\Catalog\DB;

use Storm\Model;

/**
 * @table{"name":"catalog_product"}
 */
class Product extends Model
{
	/**
	 * @column{"locale":true}
	 */
    public $name = '';

	/**
	 * @column{"type":"text","nullable":true,"locale":true}
	 */
	public $perex = '';

	/**
	 * @column{"type":"text","nullable":true,"locale":true}
	 */
	public $text = '';

	/**
	 * @column
	 */
	public $code = '';

	/**
	 * @column
	 */
	public $sub_code = '';

	/**
	 * @column{"nullable":true}
	 */
	public $category_code = '';

	/**
	 * @column{"nullable":true}
	 */
	public $ean = '';

	/**
	 * @column{"nullable":true}
	 */
	public $mu = '';

	/**
	 * @column{"nullable":true}
	 */
	public $mu2 = '';

	/**
	 * @column{"nullable":true}
	 */
	public $rating = 0.0;

	/**
	 * @column{"default":0}
	 */
	public $change = 0;

	/**
	 * @column{"type":"float","nullable":true}
	 */
	public $weight = 0.0;

	/**
	 * @column{"nullable":true}
	 */
	public $in_package = 0;

	/**
	 * @column
	 */
	public $in_carton = 0;

	/**
	 * @column
	 */
	public $on_palette = 0;

	/**
	 * @column{"default":0}
	 */
	public $news = false;
	
	/**
	 * @column{"nullable":"true"}
	 */
	public $news_year = 0;
	
	/**
	 * @column{"default":0}
	 */
	public $news_order = 0;

	/**
	 * @column{"default":0}
	 */
	public $hidden = false;

	/**
	 * @column{"default":0}
	 */
	public $deleted = false;

	/**
	 * @column{"default":0}
	 */
	public $priority = 0;

	/**
	 * @column{"type":"datetime","nullable":true}
	 */
	public $imagemtime;

	/**
	 * @column{"default":0}
	 */
	public $recommended = false;

	/**
	 * @column{"nullable":true}
	 */
	public $image_dir = '';

	/**
	 * @column{"nullable":true}
	 */
	public $image = '';


	/**
	 * Categories
	 * @relation{"NxN\\ProductCategory":":fk_product","Category":"fk_category"}
	 * @var Category[]
	 */
	public $categories;

	/**
	 * Photos
	 * @relation{"ProductPhoto":":fk_product"}
	 * @var ProductPhoto[]
	 */
	public $photos;

	/**
	 * Files
	 * @relation{"ProductFile":":fk_product"}
	 * @var ProductFile[]
	 */
	public $files;

	public function getPhotos()
	{
		return $this->photos->where('hidden', false)->orderBy(['priority']);
	}

	public function getMainPhoto()
	{
		return $this->getPhotos()->first();
	}

	public function getFiles()
	{
		return $this->files->where('hidden', false)->orderBy(['priority']);
	}

	public function getRelatedProducts()
	{
		return $this->categories->first() ? $this->categories->first()->products->where('catalog_product.uuid != :uuid', ['uuid' => $this->getPK()]) : null;
	}

	public function getPrimaryCategory()
	{
		return $this->categories->first();
	}

	public function getCategoryID()
	{
		return (string) $this->categories->first();
	}

	public function getCategories()
	{
		return $this->categories->where('hidden', false)->orderBy(['priority']);
	}

	public function isInFestaIndustry()
	{
		return $this->getPrimaryCategory() && $this->getPrimaryCategory()->industry == true;
	}
}
