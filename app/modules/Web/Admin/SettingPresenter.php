<?php

namespace App\Web\Admin;

use Lqd\Pages\DB\Page;
use Nette\Http\Url;
use Storm\Collection;


class SettingPresenter extends \Lqd\Admin\BasePresenter
{
	use \Lqd\Web\Admin\MenuTrait;

	/**
	 * @inject
	 * @var \Nette\Caching\IStorage
	 */
	public $cache;

	const URL_PATTERN = '[a-z0-9_/\-]*';

	public function startup()
	{
		parent::startup();

		$this['menu']->addItem('Nastavení webu', 'settings');
//		$this['menu']->addItem('URL', 'pages');
		$this['menu']->addItem('URL / Sitemapa', 'pages');
		$this['menu']->addItem('Přesměrování', 'redirections');
		$this['menu']->addItem('Stránka 404', 'page404');
	}


    public function actionPage404()
    {
        $this->admin->title = 'Editace stránky 404';
        $this->admin->icon = 'fa fa-cogs';

        $object = $this->stm->getRepository(\Lqd\Web\DB\Settings::class)->many()->first();

        $form = $this->createForm('form', true);
        $form->setUserPath('userfiles');

        $form->addGroup('Jazykové verze');
        $form->addLangSelector();

		$form->addLocaleRichEdit('text404', 'Obsah', 'small')->setOption('class', 'content');;


        $form->setDefaults($object->jsonSerialize());

        $form->addSubmit('submit', 'Uložit');
        $form->onSuccess[] = function ($form, $values) use ($object) {
            $object->loadFromArray((array) $values);
            $object->update();
            $form->getPresenter()->flashMessage('Uloženo', 'success');
            $form->getPresenter()->redirect('this');
        };

        return;
    }

	public function actionSettings()
	{
		$this->admin->title = 'Editace nastavení';
		$this->admin->icon = 'fa fa-cogs';

		$this->template->text = '';
		$this->template->text .= '<div class="bg-light p-3 mb-3"><h5 class="mt-0">Soubor sitemap.xml</h5>';
		$this->template->text .= '<a href="'.$this->link('//:Web:Export:sitemap').'" target="_blank"> <code>' . $this->link('//:Web:Export:sitemap') . '</code> <a target="_blank" href="' . $this->link('//:Web:Export:sitemap') . '"><i class="fa fa-external-link-square"></i></a>';
		$this->template->text .= '</div>';

		$object = $this->stm->getRepository(\Lqd\Web\DB\Settings::class)->many()->first();

		$form = $this->createForm('form');
		$form->setUserPath('userfiles');

		$form->addGroup('Hlavní údaje');
        $form->addLangSelector();
		$form->addLocaleText('project_name', 'Název webu');
		$form->addLocaleText('project_title', 'Připojit titulek');


		$form->addGroup('API klíče');

		$form->addText('google_analytics_id', 'Google Analytics ID');
		$form->addText('google_map_key', 'Google Maps key');
		$form->addText('google_tag_manager', 'Google Tag Manager');
		$form->addText('google_search_console', 'Google Search Console');

		$form->addGroup('Odkazy na sociální sítě');
		$form->addText('youtube_link', 'Youtube');
		$form->addText('facebook_link', 'Facebook');
		$form->addText('twitter_link', 'Twitter');

		$form->addGroup('Skripty');
		$form->addTextArea('scripts_header', 'Skripty v hlavičce',null,5);
		$form->addTextArea('scripts_footer', 'Skripty v patičce',null, 5);

//		$form->addText('title_format', 'Formát titulku (sprintf)');

		$form->setDefaults($object->jsonSerialize());

		$form->addSubmit('submit', 'Uložit');
		$form->onSuccess[] = function ($form, $values) use ($object) {
			$object->loadFromArray((array) $values);
			$object->twitter_link = $values->twitter_link;

			$object->update();
			$form->getPresenter()->flashMessage('Uloženo', 'success');
			$form->getPresenter()->redirect('this');
		};

		return;
	}

	public function actionRedirectionsNew()
	{
		$this->admin->title = 'Nové přesměrování';
		$this->admin->icon = 'fa fa-pagelines';

		$form = $this->createForm('form');

//		$form->setRenderer(new \Lqd\CMS\Form\Renderer\AdminLTE2Columns());
        
		$form->addGroup('Hlavní údaje');

		$scheme = $this->getHttpRequest()->getUrl()->getScheme();

		$urls = $form->addText('url','Odkud')->setRequired()->setAttribute('placeholder', "relativní adresa nebo absolutní včetně $scheme://");
        /*foreach($urls->getControls() as $url) {
            $lang = $url->getName();
            $url->setAttribute('placeholder', 'Prostá url adresa např. "produkt-sesivacka-125"')->setRequired(TRUE)->addConditionOn($url, $form::FILLED)->addRule($form::PATTERN, 'Zadávejte jen malá písmena, čísla, lomítka a pomlčky', $form::URL_PATTERN);

            $pageRepo = $this->stm->getRepository(\Lqd\Pages\DB\Page::class);
            $url->addRule('Lqd\Admin\Control\Form::validateUrl', 'URL adresa musí být unikátní', [$pageRepo, null, $lang, null]);


        }*/
        $form->addText('redirecturl', 'Kam')->setRequired()->setAttribute('placeholder', "relativní adresa nebo absolutní včetně $scheme://");
		$form->addSubmit('submit', 'Uložit');


		$stm = $this->stm;
        $form->onValidate[] = function($form, $values) {

            if (strpos($values['url'], 'http') === 0) {
                $url = new Url($values['url']);

                if ($this->getHttpRequest()->getUrl()->getHostUrl() !== $url->getHostUrl()) {
                    $form['url']->addError('Absolutní URL adresa musí být na doméně '.$this->getHttpRequest()->getUrl()->getHostUrl());
                }
            }


        };
		$form->onSuccess[] = function($form, $values) use ($stm) {

            $url = $values['url'];
            if (strpos($url, 'http') === 0) {
                $url = new Url($url);

                // detect language
                $relativeUrl = substr($url->getPath(), strlen($this->getHttpRequest()->getUrl()->scriptPath));
            } else {
                $relativeUrl = $url;
            }


            $lang = strtok($relativeUrl, '/');


            if (in_array($lang, $this->pages->getLanguages(true)) && $lang != $this->pages->getDefaultLanguage()) {
                $relativeUrl = (string) substr($relativeUrl, strlen($lang) + 1);
            } else {
                $lang = $this->pages->getDefaultLanguage();
            }

            // delete page with url in lang
            $this->stm->getRepository(Page::class)->many()->where('url'. $this->stm->getLangSuffix($lang), $relativeUrl)->delete();


            $page = $this->stm->getRepository(Page::class)->create([
                'redirecturl' => $values['redirecturl'],
                'code' => 301,
                'redirect' => true,
            ]);

            $page->setValue('url', $relativeUrl, $lang);

            $this->stm->getRepository(Page::class)->add($page);

			$form->getPresenter()->flashMessage('Uloženo', 'success');
			$form->getPresenter()->redirect('redirections');
		};

	}

	public function actionPagesDetail(\Lqd\Pages\DB\Page $object = NULL)
	{
		$this->admin->title = $object ? 'URL: '. $object->title : 'Nová URL';
		$this->admin->icon = 'fa fa-pagelines';

		$form = $this->createForm();
//		$form->setRenderer(new \Lqd\CMS\Form\Renderer\AdminLTE2Columns());

		$form->addGroup('Jazykové verze');
		$form->addLangSelector();

		$form->addGroup('Hlavní údaje');

		$tpls = $this->pages->getTemplates();
		$templates = [];
		foreach($tpls as $id => $template) {
			$templates[$id] = $template['name'] ?? $id;
		}

		if ($object === NULL) {
			$json = [];
			foreach($tpls as $id => $template) {
				$matches = $params =[];
				preg_match_all('/(?:<([a-z0-9]+)>)+/', $template['url'], $matches);

				if (isset($matches[1]))
					foreach($matches[1] as $pattern) {
						$params[$pattern] = 'zde_doplnte_id_'.$pattern;
					}

				$json[$id] = [
					'params' => http_build_query($params),
				];
			}

			$select = $form->addSelect('template', 'Šablona', $templates)->setPrompt('-zvolte šablonu-')->setRequired(TRUE);
			$select->setAttribute('data-templates', json_encode($json));
		}

		$urls = $form->addLocaleText('url','Url');

		if (count($this->context->parameters['langs']) > 1) {
			foreach ($urls->getControls() as $url) {
				$lang = $url->getName();
				$url->setAttribute('placeholder', 'Prostá url adresa např. "produkt-sesivacka-125"')->setRequired(FALSE)->addConditionOn($url, $form::FILLED)->addRule($form::PATTERN, 'Zadávejte jen malá písmena, čísla, lomítka a pomlčky', $form::URL_PATTERN);
				$pageRepo = $this->stm->getRepository(Page::class);
				if ($object) {
					$required = !$pageRepo->many()->where('url = "" AND page.uuid!=:uuid', ['uuid' => $object->uuid])->isEmpty();

				}
				if ($object) {
					$url = $url->addConditionOn($url, $form::NOT_EQUAL, $object->getUrl($lang));
				}
				$url->addRule('Lqd\Admin\Control\Form::validateUrl','URL adresa musí být unikátní', [$pageRepo, $object ? $object->template : null, $lang, $object ? $object->getParams() : null]);

			}
		} else {
			$urls->setAttribute('placeholder', 'Prostá url adresa např. "produkt-sesivacka-125"')->setRequired(FALSE)->addConditionOn($urls, $form::FILLED)->addRule($form::PATTERN, 'Zadávejte jen malá písmena, čísla, lomítka a pomlčky', $form::URL_PATTERN);
			$pageRepo = $this->stm->getRepository(Page::class);
			$lang = $this->context->parameters['langs'][0];
			if ($object) {
				$required = !$pageRepo->many()->where('url = "" AND page.uuid!=:uuid', ['uuid' => $object->uuid])->isEmpty();

			}
			if ($object) {
				$urls = $urls->addConditionOn($urls, $form::NOT_EQUAL, $object->getUrl($lang));
			}
			$urls->addRule('Lqd\Admin\Control\Form::validateUrl','URL adresa musí být unikátní', [$pageRepo, $object ? $object->template : null, $lang, $object ? $object->getParams() : null]);
		}


		$form->addText('params', 'Parametry URL');
		$form->addLocaleText('redirecturl', 'Přesměrování');
		$form->addLocaleText('title', 'SEO titulek');
		$form->addLocaleText('description', 'SEO popisek');
//        $form->addText('title', 'Titulek')->setRequired(TRUE);
//        $form->addTextArea('description', 'Popisek');
//	    @TODO title, description
		$form->addGroup('Pokročilé');
		$form->addText('robots', 'Robots')->setDefaultValue('index,follow');
		$form->addText('canonical', 'Kanonická URL')->setAttribute('placeholder', 'V případě, že kanonická adresa je shodá, ponechte')->setRequired(FALSE)->addConditionOn($form['canonical'], $form::FILLED)->addRule($form::URL);
		$form->addText('code', 'HTTP kód')->setDefaultValue(200);
		$form->addTextArea('meta', 'Meta atributy')->setAttribute('placeholder','Tahle část se umístí do hlavičky stránky');
		$form->addGroup('Sitemap.xml');
		$form->addCheckbox('sitemap', 'Zahrnout do exportu')->setDefaultValue(TRUE);
		$form->addText('sitemap_change', 'Frekvence změny')->setDefaultValue('weekly');
		$form->addText('sitemap_priority', 'Priorita')->setDefaultValue('0.5');
		$form->addCheckbox('offline', 'Offline')->setDefaultValue(false);

		$form->addSubmit('submit', 'Uložit');

		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}
		$stm = $this->stm;
		$cache = $this->cache;
		$form->onSuccess[] = function($form, $values) use ($object, $stm, $tpls, $cache) {
			$new = !$object;
			if (!$object) {
				$tpl = $tpls[$values['template']];
				$object = $stm->getRepository(\Lqd\Pages\DB\Page::class)->create( ['plink' => $tpl['plink']]);
			}

			$object->loadFromArray((array) $values);
			$new ? $stm->getRepository(\Lqd\Pages\DB\Page::class)->add($object) : $object->update();
			$form->onEdit($form, $values,$object, $cache);

			$form->getPresenter()->flashMessage('Uloženo', 'success');
			$form->getPresenter()->redirect($new ? 'pages' : 'this');
		};
	}

	public function actionPagess()
	{
		$this->admin->title = 'URL';
		$this->admin->icon = 'fa fa-pagelines';
		$this->template->buttons = '<a class="btn btn-success" href="'.$this->link('pagesDetail').'"><i class="fa fa-plus"></i> Přidat URL</a>';
//		$this->template->buttons .= ' <a class="btn btn-success" href="'.$this->link('pagesRedirect').'"><i class="fa fa-plus"></i> Přidat Přesměrování</a>';


		$tpls = $this->pages->getTemplates();
		$templates = [];
		foreach($tpls as $id => $template) {
			$templates[$id] = $template['name'] ?? $id;
		}

		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Pages\DB\Page::class)->many());

		// DateGrid
		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['template' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Titulek a popisek', 'title')->addValue('%s<br><small>%s</small>', 'title', 'description');

		$request = $this->getHttpRequest();
		$baseUrl = substr($this->getHttpRequest()->url->scriptPath, 0, -1);
		$table->addColumn('Url', 'url')->addValue('<a href="%s" target="_blank">%s</a>', function($object) use ($request, $baseUrl) { return $request->getUrl()->hostUrl. $baseUrl . '/'. $object->url;}, 'url');
//		$table->addColumn('Http kód', 'code')->addValue('%d', 'code');
//		$table->addColumn('Přesměrování', 'redirecturl')->addValue('<a href="%s" target="_blank">%s</a>', function($object) use ($request, $baseUrl) { return $request->getUrl()->hostUrl. $baseUrl . '/'. $object->redirecturl;}, 'redirecturl');
//		$table->addColumn('Robots', 'robots')->addValue('%s', 'robots');

//		$table->addColumn('Šablona', 'template')->addValue('%s', function($object) use ($templates) { return $templates[$object->template] ?? $object->template;});
		$table->addColumnMin('Sitemapa', 'sitemap')->addLqdCheckbox('sitemap');
		$table->addColumnMin('Offline','offline')->addValue('%s',function ($object){
			return $object->offline ? 'Ano' : '-';
		});

		$table->addColumnActionEdit('pagesDetail');
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q', '')->setAttribute('placeholder', 'Vyhledat titulek, URL, popis')->setAttribute('size',30);

		// Filter definition
		$table->addFilter('q', function($source, $value) { $source->where('title LIKE :q OR description LIKE :q OR url LIKE :q', ['q' => $value.'%']);});
	}

	public function actionRedirections()
	{
		$this->admin->title = 'Přesměrování';
		$this->template->buttons = ' <a class="btn btn-success" href="'.$this->link('redirectionsNew').'"><i class="fa fa-plus"></i> Přidat Přesměrování</a>';

		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Pages\DB\Page::class)->many()->where('redirect',1));
        $stm = $this->stm->getConnection();

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['redirection_priority' => 'ASC']);
		$table->addColumn('Vytvořeno','created')->addValue('%s','redirection_created');

		$table->addColumn('Odkud','url')->addValue('%s',function($obj) use ($stm) {
            return ($obj->getRedirectLang() !== $stm->getPrimaryLang()) ? $obj->getRedirectLang() . '/' . $obj->getValue('url', $obj->getRedirectLang()) : $obj->getValue('url', $obj->getRedirectLang());
        });
		$table->addColumn('Kam','redirecturl')->addValue('%s','redirecturl');
//		$table->addColumn('Http kód', 'code')->addValue('%d', 'code');
		//$table->addColumnMin('Pořadí','priority')->addLqdCheckbox('redirection_priority');
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q', '')->setAttribute('placeholder', 'Vyhledat URL')->setAttribute('size',30);

		// Filter definition
		$table->addFilter('q', function($source, $value) { $source->where('url LIKE :q OR redirecturl LIKE :q', ['q' => $value.'%']);});
	}

	public function actionPages()
	{
		$this->admin->title = 'URL / Sitemapa';

		$this->template->buttons = '<a class="btn btn-success" href="'.$this->link('pagesDetail').'"><i class="fa fa-plus"></i> Přidat URL</a>';

		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Pages\DB\Page::class)->many()->where('redirect', false));

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['last_modified' => 'ASC']);
		$table->addColumn('Titulek a popisek', 'title')->addValue('%s<br><small>%s</small>', 'title', 'description');
		$table->addColumn('URL','url')->addValue('%s','url');
//		$table->addColumn('Přesměrování na','redirecturl')->addValue('%s','redirecturl');
		$table->addColumn('Lastmodified','last_modified')->addText('last_modified')->setAttribute('class','datepicker')->setAttribute('size',20);
		$table->addColumnMin('Sitemapa', 'sitemap')->addLqdCheckbox('sitemap');
		$table->addColumnMin('','offline')->addValue('%s',function ($object){
			return $object->offline ? 'offline'  : '';
		});
		$table->addColumnActionEdit('pagesDetail');
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q', '')->setAttribute('placeholder', 'Vyhledat titulek, URL, popis')->setAttribute('size',30);

		// Filter definition
		$table->addFilter('q', function($source, $value) { $source->where('title LIKE :q OR description LIKE :q OR url LIKE :q', ['q' => $value.'%']);});
	}
}
