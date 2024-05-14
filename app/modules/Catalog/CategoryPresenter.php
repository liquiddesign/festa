<?php

namespace App\Catalog;

use App\Catalog\DB\Category;
use Nette\Application\UI\Presenter;

/**
 * Category Presenter
 */
class CategoryPresenter extends Presenter
{
    use \App\PresenterTrait;

    public function actionDefault(): void
    {
        $this->template->categories = $this->stm->getRepository(DB\Category::class)->getRootItems();
	    $this['breadcrumb']->addLevel($this->pages->getPage()->getTitle(), $this->link('this'));
    }

    public function renderDefault(): void
    {
    }

    public function actionDetail(DB\Category $category): void
    {
	    $this->template->categories = $this->stm->getRepository(DB\Category::class)->getRootItems();
	    $this->template->category = $category;

//	    if ($category->getDirectParent()) {
//	    	if ($category->getDirectParent()->getDirectParent()) {
//		        $this['breadcrumb']->addLevel($category->getDirectParent()->getDirectParent()->name, $this->link(':Catalog:Category:detail', $category->getDirectParent()->getDirectParent()));
//	        }
//
//		    $this['breadcrumb']->addLevel($category->getDirectParent()->name, $this->link(':Catalog:Category:detail', $category->getDirectParent()));
//	    }

	    foreach($category->getTree()->where('hidden', false) as $c) {
		    $this['breadcrumb']->addLevel($c->name, $this->link(':Catalog:Category:detail', $c));
	    }

//	    $this['breadcrumb']->addLevel($category->name, $this->link('this'));
    }

    public function renderDetail(): void
    {
    }

    public function renderIndustry(): void
    {
    }

    public function actionIndustry(DB\Category $category = null): void
    {
	    $this->params['industry'] = true;
	    if ($category) {
		    $this->params['category'] = $category;
	    }
	    $this->template->categories = $this->stm->getRepository(DB\Category::class)->many()->where('industry', true);

	    $this['breadcrumb']->addLevel('Festa Industry', $this->link('this'));
    }
}
