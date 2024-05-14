<?php

namespace App\Custom;

use App\Catalog\DB\Category;
use App\Catalog\DB\Product;
use Nette\Application\UI\Presenter;

/**
 * Homepage Presenter
 */
class IndexPresenter extends Presenter
{
    use \App\PresenterTrait;

    public function actionDefault(): void
    {
	    $this->template->categories = $this->stm->getRepository(Category::class)->getRootItems();
	    $this->template->newProducts = $this->stm->getRepository(Product::class)->getNewProducts();
    }

    public function renderDefault(): void
    {
		$videos = [
			'62' => [
				'url' => 'https://www.youtube.com/embed/s5Zblq0JT6w',
				'desc' => 'Renovace podlahy v dílně',
			],
			'63' => [
				'url' => 'https://www.youtube.com/embed/3MVSmGsfDzE',
				'desc' => 'Stůl a židle',
			],
			'64' => [
				'url' => 'https://www.youtube.com/embed/yt1fUc9hm98',
				'desc' => 'Obklad stěny',
			],
			'65' => [
				'url' => 'https://www.youtube.com/embed/lzT70-RumqQ',
				'desc' => 'Kanalizace',
			],
			'66' => [
				'url' => 'https://www.youtube.com/embed/yMPTa9jKwrs"',
				'desc' => 'Schody',
			],
			'68' => [
				'url' => 'https://www.youtube.com/embed/dL-2rLjPcTM',
				'desc' => 'Koupelna',
			],
			'69' => [
				'url' => 'https://www.youtube.com/embed/hkHJF6713jY',
				'desc' => 'Zatravňovací dlažba',
			],
			'70' => [
				'url' => 'https://www.youtube.com/embed/WAVep1luXdQ',
				'desc' => 'Izolace a sádrokartony',
			],
		];
		$this->template->videos = $videos;
    }
	
	public function renderSale()
	{
	
	}
}
