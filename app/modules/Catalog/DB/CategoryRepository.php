<?php

namespace App\Catalog\DB;

class CategoryRepository extends \Storm\Repository
{
	const PATH_LENGTH = 4;

	public function root()
	{
		return $this->many()->where(new \Storm\Literal('LENGTH(path)'), self::PATH_LENGTH);
	}

	public function getTree()
	{
		$last = [];
		$categories = [];
		//foreach($this->many()->select(['COUNT(products.uuid) AS pocet'])->groupBy(['`ecommerce_category`.uuid'])->having('pocet > 0')->where('hidden', FALSE)->orderBy([new \Storm\Literal('LENGTH(path)'),'priority']) as $id => $category)
		foreach($this->many()->where('hidden', FALSE)->orderBy([new \Storm\Literal('LENGTH(path)'),'priority']) as $id => $category)
		{
			if ($category->getLevel() == 0)
			{
				$categories[$id] = $category;
			} else {
				$last[substr($category->path, 0, -4)]->childs[$id] = $category;
			}

			$last[$category->path] = $category;
		}

		return $categories;
	}

	public function getItems()
	{
		return $this->many()
			->where('hidden', FALSE)
			->orderBy(['priority' => 'ASC']);
	}

	public function getRootItems()
	{
		return $this->many()
			->where('LENGTH(path)=4')
			->where('hidden', FALSE)
			->orderBy(['priority' => 'ASC']);
	}
}