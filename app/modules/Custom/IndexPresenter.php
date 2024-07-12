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
		$this->template->videosFestovani = [
			"https://www.youtube.com/embed/rfgBXge3cAs",
			"https://www.youtube.com/embed/6KGnp3vemq8",
			"https://www.youtube.com/embed/oMWdjsGVyyg",
			"https://www.youtube.com/embed/r5Jg79T83Fo",
			"https://www.youtube.com/embed/zxHmFstlVzQ",
			"https://www.youtube.com/embed/WHejoRMMOSQ",
			"https://www.youtube.com/embed/-BKqygACbys",
			"https://www.youtube.com/embed/S1CKu0x4fGc",
			"https://www.youtube.com/embed/VZGmYn4_6TU",
			"https://www.youtube.com/embed/xrhcjr9EcnU",
			"https://www.youtube.com/embed/BiLC7YNInlA",
			"https://www.youtube.com/embed/kQDAmQOR-RM",
			"https://www.youtube.com/embed/ymKDqTp4aPU",
			"https://www.youtube.com/embed/P-rzUxPR32g",
			"https://www.youtube.com/embed/aNAuflnxpao",
			"https://www.youtube.com/embed/6o5HllrOSlc",
			"https://www.youtube.com/embed/L3KGAQ9y3FM",
			"https://www.youtube.com/embed/6ksbrZ6HjAQ",
			"https://www.youtube.com/embed/6P89Pr5mfiU",
			"https://www.youtube.com/embed/Vnz0kA0BFBE",
			"https://www.youtube.com/embed/7ovIgEeTyYo",
			"https://www.youtube.com/embed/Owl2D1-kw_g",
			"https://www.youtube.com/embed/4j7MK5y2rMs",
			"https://www.youtube.com/embed/sm4ZddYplxk",
			"https://www.youtube.com/embed/kfWp-sEDhtI",
			"https://www.youtube.com/embed/Hb1QF2qWKN4",
			"https://www.youtube.com/embed/r7KbHrfb3jI",
			"https://www.youtube.com/embed/WJa0o4v35u0",
			"https://www.youtube.com/embed/N-af0xAgBx4",
			"https://www.youtube.com/embed/lOAlKhalrAM",
			"https://www.youtube.com/embed/a6JcEsnOC24",
			"https://www.youtube.com/embed/lOAlKhalrAM",
			"https://www.youtube.com/embed/9NlRK4cCltM",
			"https://www.youtube.com/embed/7Ta6f2EwKjI",
			"https://www.youtube.com/embed/j5rCsaZsjLA",
		];
	}
	
	public function renderSale()
	{
	
	}
}
