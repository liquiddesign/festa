<?php

namespace App\Catalog\DB;

use Storm\Repository;

class ProductRepository extends Repository
{
	public function getProducts()
	{
		$many = $this->many();
		
		$many->where('hidden', false)->where('deleted', false);
		
		$many->orderBy(['categories.priority', 'catalog_product.category_code']);
		$many->groupBy(['catalog_product.uuid']);
		
		return $many;
	}
	
	public function getNewProducts()
	{
		return $this->many()
			->where('hidden', false)
			->where('deleted', false)
			->where('news', true)
//			->where('catalog_product.news_year = :year', ['year' => date('Y')])
			->orderBy(['catalog_product.news_order' => 'ASC'])
			->take(28);
//		return $this->many()->where('news = 1')->orderBy(['priority']);
	}

	public function filterCategory(\Storm\Collection $collection, Category $category)
	{
		$collection->where('categories.path LIKE :path', ['path' => $category->path . '%']);
	}

	public function filterSearch(\Storm\Collection $collection, $q)
	{
		if (count(explode(' ', $q)) > 1) {
			$collection->select(['rel0' => 'MATCH(catalog_product.name) AGAINST (:q)','rel1' => 'MATCH(catalog_product.name, catalog_product.text, catalog_product.perex) AGAINST (:q)'],TRUE, FALSE);
			$collection->where('catalog_product.ean=:q OR catalog_product.name LIKE :qlike COLLATE utf8_general_ci OR catalog_product.name LIKE :qlikeq COLLATE utf8_general_ci OR  MATCH(catalog_product.name) AGAINST (:q) OR MATCH(categories.name) AGAINST (:q) OR MATCH( catalog_product.name, catalog_product.text, catalog_product.perex) AGAINST(:q )', ['q' => $q , 'qlike' => $q.'%', 'qlikeq' => '%'.$q.'%']);
			return $collection->orderBy(['catalog_product.ean=:q' => 'DESC','catalog_product.name LIKE :qlike' => 'DESC', 'catalog_product.name LIKE :qlikeq' => 'DESC','rel0' => 'DESC', 'catalog_product.ean' => 'ASC', 'catalog_product.code' => 'ASC'], FALSE, FALSE);
		} else {
			$collection->select(['rel0' => 'MATCH(catalog_product.name) AGAINST (:q)','rel1' => 'MATCH(catalog_product.name, catalog_product.text, catalog_product.perex) AGAINST (:q)'],TRUE, FALSE);
			$collection->where('catalog_product.code LIKE :qlike OR catalog_product.ean=:q OR catalog_product.name LIKE :qlike COLLATE utf8_general_ci OR MATCH(catalog_product.name) AGAINST (:q) OR MATCH(categories.name) AGAINST (:q) OR MATCH( catalog_product.name, catalog_product.text, catalog_product.perex) AGAINST(:q )', ['q' => $q , 'qlike' => $q.'%']);
			return $collection->orderBy(['catalog_product.code LIKE :qlike' => 'DESC', 'catalog_product.ean=:q' => 'DESC','catalog_product.name LIKE :qlike' => 'DESC','rel0' => 'DESC', 'catalog_product.ean' => 'ASC', 'catalog_product.code' => 'ASC'], FALSE, FALSE);
		}
	}

	public function filterSearchByNameCodeEan(\Storm\Collection $collection, $q)
	{
		$mutationSuffix = $collection->getRepository()->getConnection()->getLangSuffix();

		return $collection->where("name$mutationSuffix LIKE :qlikeq OR code LIKE :qlike OR ean LIKE :qlike", ['qlike' => $q.'%', 'qlikeq' => '%'.$q.'%'])
			->orderBy(["name$mutationSuffix" => 'ASC']);
	}

	public function filterNews(\Storm\Collection $collection, $news)
	{
		if ($news === true) {
			$collection->where('catalog_product.news = :news OR catalog_product.news_year = :year', ['news' => true, 'year' => date('Y')]);
		}
	}

	public function filterIndustry(\Storm\Collection $collection, bool $industry)
	{
		if ($industry === true) {
			$collection->where('catalog_product.name LIKE :industry', ['industry' => '%industry%']);
		}
	}
	
	public function setAllDeleted()
    {
        $this->many()->update(['deleted' => true]);
    }

}

