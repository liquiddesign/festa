<?php

namespace App\Web;

use Lqd\CMS;

class SearchPresenter extends \Nette\Application\UI\Presenter
{
    use \App\PresenterTrait;

//	/**
//	 * @inject
//	 * @var \Lqd\Web\DB\ArticleRepository
//	 */
//	public $articleRepo;
//
	/**
	 * @inject
	 * @var \App\Catalog\DB\ProductRepository
	 */
	public $productRepo;

	public function actionDefault($q)
	{
		$this['breadcrumb']->addLevel('Výsledky vyhledávání', $this->link('this'));
		$this->template->q = $q;
		$this->template->products = $this->productRepo->many()->filter(['search' => $q]);
	}
}
