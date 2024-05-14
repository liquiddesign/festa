<?php

namespace App\Catalog;

use App\Catalog\DB\Product;
use Nette\Application\UI\Presenter;

/**
 * Product Presenter
 */
class ProductPresenter extends Presenter
{
    use \App\PresenterTrait;

    public function actionDetail(\App\Catalog\DB\Product $product): void
    {
        $this->template->product = $product;
        $this->template->relatedProducts = $product->getRelatedProducts();

	    if ($category = $product->categories->first()) {
		    foreach($category->getTree()->where('hidden', false) as $c) {
			    $this['breadcrumb']->addLevel($c->name, $this->link(':Catalog:Category:detail', $category));
		    }
	    }

        $this['breadcrumb']->addLevel($product->name, $this->link('this'));
    }

    public function renderDetail(): void
    {
    }

    public function actionNews(): void
    {
	    $this->params['news'] = true;
        $this['breadcrumb']->addLevel($this->pages->getPage()->getTitle(), $this->link('this'));
    }

    public function renderNews(): void
    {
    }

    public function handleDownloadFile(\App\Catalog\DB\ProductFile $file)
    {
    	$filePath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $file->file;
	    $response = new \Nette\Application\Responses\FileResponse($filePath, $file->file, 'application/pdf');
	    $this->sendResponse($response);
    }
}
