<?php

namespace App;

use Lqd\Email\Control\ContactForm;
use App\Catalog\Control\Factory\Products;
use Lqd\Modules\PresenterTrait as ModulePresenterTrait;
use Lqd\Pages\PresenterTrait as PagesPresenterTrait;
use Lqd\Translator\PresenterTrait as TranslatorPresenterTrait;
use Lqd\Web\Control\Breadcrumb;
use Lqd\Web\Control\Gallery;
use Lqd\Web\Control\Map;
use Lqd\Web\Control\Menu;
use Lqd\Web\Control\Slider;
use Lqd\Web\Control\Tab;
use Lqd\Web\Control\Textboxes;
use Lqd\Web\Control\Video;
use Lqd\Web\Control\Search;

trait PresenterTrait
{
    use ModulePresenterTrait;
    use TranslatorPresenterTrait {
        TranslatorPresenterTrait::startup as protected translatorStartup;
        TranslatorPresenterTrait::beforeRender as protected translatorBeforeRender;
    }
    use PagesPresenterTrait {
        PagesPresenterTrait::beforeRender as protected pagesBeforeRender;
    }

    /**
     * Storm
     *
     * @inject
     * @var \Storm\Connection
     */
    public $stm;

    public function startup(): void
    {
        parent::startup();

        \Lqd\Common\Filters::$currency = $this->context->parameters['currency'];
        \Lqd\Common\Filters::$currency_locale = $this->context->parameters['currency_locale'];

        $this->translatorStartup();
    }

    public function beforeRender(): void
    {
        $this->pagesBeforeRender();
        $this->translatorBeforeRender();

        $this->template->wwwUrl = $this->context->parameters['wwwUrl'];
        $this->template->pubUrl = $this->context->parameters['pubUrl'];
        $this->template->nodeUrl = $this->template->pubUrl . '/node_modules';
        $this->template->userUrl = $this->context->parameters['userUrl'];
        $this->template->ts = $this->context->parameters['ts'];
        $this->template->settings = $this->stm->getRepository(\Lqd\Web\DB\Settings::class)->many()->first();
        $this->template->langs = $this->translator->getAvailableLanguages();
        $this->template->textBoxes = $this->stm->getRepository(\Lqd\Web\DB\Textbox::class)->many()->toArray();

        return;
    }

    public function createComponentMenu(): Menu
    {
        return $this->context->getByType(\Lqd\Web\Control\Factory\Menu::class)->create('main');
    }

    public function createComponentVideo(): Video
    {
        return $this->context->getByType(\Lqd\Web\Control\Factory\Video::class)->create();
    }

    public function createComponentSearch(): Search
    {
        return $this->context->getByType(\Lqd\Web\Control\Factory\Search::class)->create();
    }

    public function createComponentTabs(): Tab
    {
        return $this->context->getByType(\Lqd\Web\Control\Factory\Tab::class)->create();
    }

    public function createComponentGallery(): Gallery
    {
        return $this->context->getByType(\Lqd\Web\Control\Factory\Gallery::class)->create();
    }

    public function createComponentMaps(): Map
    {
        return $this->context->getByType(\Lqd\Web\Control\Factory\Map::class)->create();
    }

    public function createComponentSlider(): Slider
    {
        return $this->context->getByType(\Lqd\Web\Control\Factory\Slider::class)->create();
    }

    public function createComponentContactForm(): ContactForm
    {
        return $this->context->getByType(\Lqd\Email\Control\Factory\IContactForm::class)->create();
    }

    public function createComponentTextboxes(): Textboxes
    {
        return $this->context->getByType(\Lqd\Web\Control\Factory\Textboxes::class)->create();
    }

    public function createComponentBreadcrumb(): Breadcrumb
    {
        $factory = $this->context->getByType(\Lqd\Web\Control\Factory\Breadcrumb::class);
        $breadcrumb = $factory->create();
        $breadcrumb->setSkipLastLink(true);

        $breadcrumb->addLevel('Úvod', $this->link(':Custom:Index:default'));

        return $breadcrumb;
    }


	protected function createComponentProducts()
	{
		return $this->context->getByType(Products::class)->create([
			'category' => $this->getParameter('category'),
			'products' => $this->getParameter('products'),
			'news' => $this->getParameter('news'),
			'search' => $this->getParameter('q'),
			'industry' => $this->getParameter('industry'),
		]);


	}

}
