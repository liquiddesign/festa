<?php
namespace Lqd\Web\Control;
  
use Lqd\Modules\ControlTrait;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Control;

use Lqd\Pages;

class Menu extends Control
{
    use ControlTrait;

	/**
	 * @var Pages\Pages
	 */
	public $pages;
	
	public $menuId;
	
	public $repoMenu;

	public $categoryRepo;

	private $activeItem;
    /**
     * @var \Lqd\Translator\Translator
     */
    public $translator;

    /**
	 * @param Pages\Pages
	 */
	public function __construct($menuId, Pages\Pages $pages, \Lqd\Web\DB\MenuItemRepository $repoMenu, \Lqd\Translator\Translator $translator, \App\Catalog\DB\CategoryRepository $categoryRepo)
	{
		$this->pages = $pages;
		$this->menuId = $menuId;
		$this->repoMenu = $repoMenu;
		$this->categoryRepo = $categoryRepo;

        $this->translator = $translator;
    }

	public function getActiveItem()
	{
        $page = $this->pages->getPage();
        if ($page) {
            return $this->activeItem ?: $this->activeItem = $this->repoMenu->many()->where('fk_menu', $this->menuId)->where('fk_page', $page->getId())->first();
        } else {
            return null;
        }

	}

	public function setActiveItemByPage(Pages\DB\Page $page)
	{
		return $this->activeItem = $this->repoMenu->many()->where('fk_page', $page)->first();
	}

	public function isHighlighted(\Lqd\Web\DB\MenuItem $item)
	{
		if ($activeItem = $this->getActiveItem()) {
			return in_array($item->getPK(), $this->getActiveItem()->getTree()->toArray('uuid'));
		} else {
			return false;
		}
	}

	public function render()
	{                  
		$template = $this->template;
		if (isset($this->presenter->translator)) {
			$template->setTranslator($this->presenter->translator->getTranslator());
		}
		$template->menuId = $this->menuId;
		$template->items = $this->repoMenu->getRootItems($this->menuId);
		$template->categories = $this->categoryRepo->getRootItems();
		try {
            $template->page = $this->pages->getPage();
        } catch (BadRequestException $x) {;}
        $this->setTemplateFile()->render();
	}

	public function renderSubmenu()
	{
		$template = $this->setTemplateFile('submenu');
		$template->activeItem = $this->getActiveItem();
//		dump($this->getActiveItem()->getLevel());
//		foreach ($this->getActiveItem()->getDirectChilds()->toArray() as $item) {
//			dump($item->name);
//		}
//		if ($this->getActiveItem()) {
//			$template->activeItemRoot = $this->getActiveItem();
//			$template->activeItemRoot = $this->getActiveItem()->getDirectParent() ?? $this->getActiveItem();
//		}
		$template->render();
	}
	
	public function renderFoot($menuId = 'footer')
	{                  
		$template = $this->setTemplateFile('foot');
		$template->menuId = $menuId;
		$template->items = $this->repoMenu->getRootItems($menuId);
        try {
            $template->page = $this->pages->getPage();
        } catch (BadRequestException $x) {;}
        $template->render();
	}

	public function createComponentSearch(): Search
	{
		return $this->getPresenter()->context->getByType(\Lqd\Web\Control\Factory\Search::class)->create();
	}
}
