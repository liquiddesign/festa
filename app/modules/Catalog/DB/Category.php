<?php

namespace App\Catalog\DB;

use Storm\Model;

/**
 * @table{"name":"catalog_category"}
 */
class Category extends Model
{
    /**
     * @column{"locale":true}
     */
    public $name = '';

	/**
	 * Code
	 * @column
	 */
	public $code = '';

	/**
	 * Path
	 * @column{"unique":true,"nullable":true}
	 */
	public $path = '';

	/**
	 * Perex
	 * @column{"type":"text","nullable":true,"locale":true}
	 */
	public $perex = '';

	/**
	 * Text
	 * @column{"type":"text","nullable":true,"locale":true}
	 */
	public $text = '';

	/**
	 * @column{"default":0}
	 */
	public $hidden = false;

	/**
	 * @column{"default":0}
	 */
	public $industry = false;

	/**
	 * @column{"default":0}
	 */
	public $priority = 0;

	/**
	 * Products
	 * @relation{"NxN\\ProductCategory":":fk_category","Product":"fk_product"}
	 * @var Product[]
	 */
	public $products;

	/**
	 * @column{"nullable":true}
	 */
	public $image = '';

	public function getDirectChilds(array $orderBy = [])
	{
		$childs = $this->getRepository()->many()->where('path LIKE :path AND LENGTH(path)=:length',['path' => $this->path . '%', 'length' => strlen($this->path) + 4]);
		if (!empty($orderBy)) {
			$childs->orderBy($orderBy);
		}
		return $childs;
	}

	public function getChilds(array $orderBy = [])
	{
		$childs =  $this->getRepository()->many()->where('path LIKE :path',['path' => $this->path . '%']);
		if (!empty($orderBy)) {
			$childs->orderBy($orderBy);
		}
		return $childs;
	}

	public function getProductCount()
	{
		$items = 0;
		foreach ($this->getChilds() as $child) {
			$items += $child->products->enum();
		}

		return $items;
	}

	public function getLevel()
	{
		return ((int) strlen($this->path) / 4) - 1;
	}

	public function getDirectParent()
	{
		return strlen($this->path) <= 4 ? null : $this->getRepository()->one(['path' => substr($this->path,0,-4)]);
	}

	public function getDirectParents()
	{
		return strlen($this->path) <= 4 ? null : $this->getRepository()->many()->where(['path' => substr($this->path,0,-4)]);
	}

	public function getTree()
	{
		$paths[] = $path = $this->path;

		while($path = substr($path,0,-4)) {
			$paths[] = $path;
		}

		return $this->getRepository()->many()->where('path', $paths)->orderBy(['path']);
	}
}
