<?php
namespace App\Web\Admin;

use Lqd\Web\DB;

trait MenuTrait
{
	public function addMenuItems($currentMenu)
	{
		$this['menu']->setCurrentMenu($currentMenu);


		foreach ($this->stm->getRepository(DB\Menu::class)->many()->orderBy(['priority' => 'ASC']) as $index => $item) {
			$this['menu']->addItem($item->name, ':Web:Admin:Web:menu', ['menu' => $item]);
		}
//		$this['menu']->addItem('Články', ':Web:Admin:Web:articles');
		$this['menu']->addItem('Text boxy', ':Web:Admin:Web:textboxes');
		$this['menu']->addItem('Překlady', ':Web:Admin:Web:translations');
		$this['menu']->addItem('Slidery', ':Web:Admin:Web:sliders');
//		$this['menu']->addItem('Loga', ':Web:Admin:Web:logos'); - zatim to nikde nepotrebujeme
//		$this['menu']->addItem('Galerie', ':Web:Admin:Web:galleries');
//		$this['menu']->addItem('Mapy', ':Web:Admin:Web:maps');
//		$this['menu']->addItem('Taby', ':Web:Admin:Web:tabs');
//		$this['menu']->addItem('Sidepanely', ':Web:Admin:Web:sidePanel');
//		$this['menu']->addItem('Video', ':Web:Admin:Video:video');

		$menus = $this->admin->getMenu();
		if ($menu = reset($menus)){
            $menu->setActive();
        }

	}
}
