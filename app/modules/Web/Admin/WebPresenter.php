<?php

namespace App\Web\Admin;

use Lqd\Admin\Control\DataGrid;
use Lqd\Admin\Control\Form;
use Lqd\Pages\DB\Page;
use Lqd\Pages\DB\Text;
use Lqd\Security;
use Lqd\CMS;
use Lqd\Web\DB;
use Lqd\Translator\DBResource\TextRepository;
use Nette\Application\IPresenterFactory;
use Nette\Neon\Exception;
use Nette\Utils\Html;
use Nette\Utils\Random;

class WebPresenter extends \Lqd\Admin\BasePresenter
{
	use MenuTrait;

	const URL_PATTERN = '[a-z0-9_/\-]*';

	/**
	 * @inject
	 * @var \Nette\Caching\IStorage
	 */
	public $cache;

	/** @var \Lqd\Userfiles\Userfiles @inject */
	public $userfiles;

	public function startup()
	{
		parent::startup();

		$this->addMenuItems(':Web:Admin:Web:default');
	}

	public function handleDeleteMenuItem($id)
	{
		$object = $this->stm->getRepository(DB\MenuItem::class)->one(['uuid' => $id]);

		if ($object->getDirectChilds()->enum() > 0) {
			foreach ($object->getDirectChilds() as $child) {
				$child->path = str_replace($object->path, '', $child->path);
				$child->update();
			}
		}
		$object->delete();

		$this->flashMessage('Položka úspěšne smazána', 'success');
		$this->redirect('this');
	}

	public function actionDefault()
	{
		$this->redirect('menu', ['menu' => $this->stm->getRepository(DB\Menu::class)->many()->orderBy(['priority' => 'ASC'])->first()]);
	}

	public function actionMenu(DB\Menu $menu)
	{
		$this->admin->title = 'Menu: ' . $menu->name;
		$this->admin->icon = 'fa fa-font';
		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('menuDetail2', ['menu' => $menu]) . '"><i class="fas fa-plus"></i> Přidat položku menu</a>';
//		$this->template->buttons .= '<a class="btn btn-primary btn-sm" href="' . $this->link('menuDetail2', ['menu' => $menu]) . '"><i class="fas fa-plus"></i> Experimentalni</a>';

		$source = new CMS\Source\TreeCollection($menu->items);

		// Datagrid
		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['priority' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Název', 'name')->addValue('%s', 'name');
		$table->addColumn('URL')->addValue('<a href="%1$s" target="_blank">%1$s</a>', function ($object, $id, $p) {
			return $object->getPlink($p);
		});
		$table->addColumnOrder('Pořadí', 'priority')->addText('priority')->setAttribute('style', 'width: 40px;');
		$table->addColumn('Skrytá', 'hidden', ['class' => 'minimal'])->addLqdCheckbox('hidden');
		$table->addColumn('Offline', 'offline', ['class' => 'minimal'])->addLqdCheckbox('offline');
		$table->addColumn('Typ stránky')->addValue('%s', function ($object) {
			return $object->page ? 'Výchozí' : 'stránka bez obsahu';
		});

		$table->addColumn('Layout')->addValue('%s', function ($object) {
			$pageEntity = $object->getPageEntity(DB\Article::class);
			if ($pageEntity) {
				return DB\Article::LAYOUT_TYPES[$pageEntity->layout];
			}

			return '';
		});

		$table->addColumn('Sidepanel')->addValue('%s', function ($object) {
			$pageEntity = $object->getPageEntity(DB\Article::class);
			if ($pageEntity) {
				return $pageEntity->side_panel ? $pageEntity->side_panel->name : '';
			}

			return '';
		});

		$table->addColumnActionEdit('menuDetail2', ['menu' => $menu]);
		$table->addColumnActionDelete('deleteMenuItem', ['menu' => $menu]);
//        $table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('web_menuitem.name LIKE :q', ['q' => $value . '%']);
		});

		$table->onBind[] = function ($binder, $i) use ($table) {
			if ($table->isTreeView()) {
				$spacing = '';

				if (strlen($binder->path) !== 4) {
					$iterations = (strlen($binder->path) / 4);
					for ($k = 1; $k < $iterations; $k++) {
						$spacing .= '— ';
					}
				}
				$table['body'][$i][1]->setHtml($spacing . ' &nbsp;' . $table['body'][$i][1]->getHtml());

//				if (strlen($binder->path) !== 8) {
//					$table['body'][$i][1]->setHtml($table['body'][$i][1]->getHtml());
//				} else {
//					$table['body'][$i][1]->setHtml($spacing.' &nbsp;' . $table['body'][$i][1]->getHtml());
//				}
			}
		};

		return;
	}

	public function handleSaveTranslation()
	{
		$post = $this->request->getPost();

		$text = $this->stm->getRepository(\Lqd\Translator\DBResource\Text::class)->one($post['pk']);
		$text->setValue('text', $post['value'], $post['name']);
		$text->update();

		$container = $this->context;
		$container->getService("cache.storage")->clean([\Nette\Caching\Cache::ALL => true]);
	}

	public function actionTranslations()
	{
		$this->admin->title = 'Překlady';
		$this->admin->icon = 'fa fa-files-o';

		$source = new CMS\Source\Collection($this->stm->getRepository(\Lqd\Translator\DBResource\Text::class)->many());

		// DateGrid
		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['uuid' => 'ASC']);
		//$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumnMin('ID', 'uuid')->addValue('%s', 'uuid');

		$columnsWhere = '';
		foreach ($this->context->parameters['langs'] as $lang) {
			$column = 'text' . $this->stm->getLangSuffix($lang);
			$html = Html::el();
			$c = $this->stm->getRepository(\Lqd\Translator\DBResource\Text::class)->many()->where("$column IS NULL OR $column=''")->enum();
			if ($c > 0)
				$html->setHtml(strtoupper($lang) . ' <span style="font-weight: normal">(<span style="color: red;">' . $c . '</span> nepřeložených)</span>'); else
				$html->setHtml(strtoupper($lang) . ' <i class="fa fa-check" style="color: green;"></i>');

			$table->addColumn($html, $column)->addValue('%s', function ($object) use ($lang) {
				$text = $object->getValue('text', $lang);

				$html = Html::el();

				return $html->setHtml('<a href="#" class="form-editable ajax" data-placeholder="Napište překlad ..." data-emptytext="-nepřeloženo-" data-url="' . $this->link('saveTranslation!') . '" id="' . $lang . '" data-pk="' . $object->uuid . '">' . $text . '</a>');
			});

			$columnsWhere .= " OR $column=:q";
		}

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'id nebo překlad');

		// Filter definition
		$table->addFilter('q', function ($source, $value) use ($columnsWhere) {
			$source->where('text.uuid LIKE :q OR text.text LIKE :q' . $columnsWhere, ['q' => '%' . $value . '%']);
		});
	}

	public function actionArticles()
	{
		$this->admin->title = 'Články';
		$this->admin->icon = 'fa fa-font';
		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('articlesDetail') . '"><i class="fas fa-plus"></i> Přidat článek</a>';

		$source = new CMS\Source\Collection($this->stm->getRepository(DB\Article::class)->many());

		// DateGrid
		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['name' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Název', 'name')->addValue('%s', 'name');
		$table->addColumn('Adresa')->addValue('<a href="%1$s" target="_blank">%1$s</a>', ['//:Web:Article:detail', ['article' => '%s']]);
		$table->addColumnActionEdit('articlesDetail');
		$column = $table->addColumnActionDelete();

		$table->onBind[] = function ($data, $i) use ($column, $table) {
			if ($data->getPK() == '404') {
				$table['body'][$i][4] = '';
			}
		};
		// $table['body']['rows']['404'][0]->setValue('a');

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});
	}

	public function actionMenuDetail2(DB\MenuItem $object = null, DB\Menu $menu)
	{
		$this->admin->title = $object ? 'Položka menu: ' . $object->name : 'Nová položka menu';
		$form = $this->createForm('form', true);

		$form->addGroup('Jazykové verze');
		$form->addLangSelector();
		$form->addGroup('Hlavní údaje');
		$form->addLocaleText('name', 'Název stránky');
		$form->addCheckbox('no_content', 'Stránka bez obsahu (směřuje na první položku podmenu)')->setOption('label', 'Bez obsahu')
			->addCondition($form::EQUAL, true)
			->toggle('#layout', false)
			->toggle('#side_panel', false)
			->toggle('.seo', false)
			->toggle('.content', false)
//			->toggle('.seo-field', false)
			->endCondition();

		$roots = $this->stm->getRepository(DB\MenuItem::class)->many()->where('fk_menu', $menu->uuid)->where('hidden', false)->where('LENGTH(web_menuitem.path) = 4')->orderBy(['priority' => 'ASC']);
		$items = [];
		foreach ($roots as $root) {
			if ($object && $object->getPK() != $root->getPK()) {
				$items[$root->getPK()] = $root->name;

				foreach ($root->getDirectChilds(['priority' => 'ASC']) as $child) {
					if ($object && $child->getPK() !== $object->getPK()) {
						$items[$child->getPK()] = '- ' . $child->name;
					}
				}
			} elseif (!$object) {
				$items[$root->getPK()] = $root->name;
			}
		}

		if ($object) {
			$roots->where('uuid', $object->getPK(), '!=');
		}

		if ($menu->allow_parents) {
			$form->addSelect('parent', 'Nadřazená položka', ['' => '-žádná-'] + $items);
			if ($object) {
				$form['parent']->setDefaultValue($object->getDirectParent() ? $object->getDirectParent()->getPK() : null);
			}
		}
		$art = $form->addContainer('article');

		$layouts = DB\Article::LAYOUT_TYPES;

		$textbox = $this->stm->getRepository(DB\SidePanel::class)->many();

		$art->addLocaleRichEdit('text', 'Obsah', 'small')->setOption('class', 'content');
		$art->addSelect('layout', 'Layout', $layouts)->setOption('id', 'layout')
			->addCondition(\Nette\Application\UI\Form::EQUAL, 'normal_left')
			->toggle('sticky_sidepanel')
			->toggle('sidepanel')
			->endCondition();

		$art->addSelect('fk_side_panel', 'Sidepanel', $textbox->toArray('name'))->setPrompt('-žádný-')->setOption('id', 'sidepanel');
		$art->addCheckbox('sticky_sidepanel', 'Boční panel se bude scrollovat spolu s textem')->setOption('id', 'sticky_sidepanel')->setOption('label', 'Sticky sidepanel');

		$form->addText('priority', 'Pořadí')->setDefaultValue(10)->setAttribute('size',4);
		$form->addCheckbox('hidden', 'Nezobrazí se v menu')->setOption('label', 'Skrytá');


		$form->addGroup('URL a SEO')->setOption('container', \Nette\Utils\Html::el('fieldset')->class('SEO seo-field'));

		$pageC = $form->addContainer('page');

		$article = null;

		if ($object && $object->page && isset($object->page->getParams()['article']) && $object->page->template === 'web_article') {
			// ukazuje na stranku s clankem
			$article = $this->stm->getRepository(DB\Article::class)->one($object->page->getParams()['article']);
		}

		if ($object && $object->page && $object->page->template) {
			$form->addPageContainer($object->page->template, $object->page->template == 'web_article' ? ['article' => $article] : [], $object->page);
		} else {
			$form->addPageContainer('web_article', ['article' => $article], $article);
		}
		$pageC->addCheckbox('offline', 'Stránka bude nedostupná')->setOption('label', 'Offline');


		$form->addGroup('');

		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');

		if ($object) {
			$form->setDefaults($object->toArray(['page']));
			if ($article) {
				$form['article']->setDefaults($article->toArray());
			}

		}
		$stm = $this->stm;
		$cache = $this->cache;

		$form->onSuccess[] = function ($form, $values) use ($object, $stm, $menu, $cache, $article) {
			if ($menu->allow_parents) {
				$parent = $values['parent'] ? $stm->getRepository(DB\MenuItem::class)->one($values['parent']) : null;
			}

			$new = !$object;
			$object = $object ?? $stm->getRepository(DB\MenuItem::class)->create(['fk_menu' => $menu->getPK()]);
			$object->loadFromArray((array) $values);

			if ($new) {
				if (!$values['no_content']) {
					$article = $stm->getRepository(DB\Article::class)->create(['name' => $values['name'][$form->getPrimaryLang()]]);
					$article->loadFromArray((array) $values['article']);
					$stm->getRepository(DB\Article::class)->add($article);

					$form->onEdit($form, $values, $article, $cache);

					$object->fk_page = $form->insertedPage->getPK();
				}

				$object->path = $menu->allow_parents && $parent ? $parent->path . Random::generate(4, 'A-Z0-9') : '' . Random::generate(4, 'A-Z0-9');
				$stm->getRepository(DB\MenuItem::class)->add($object);
			} else {
				$directChilds = $object->getDirectChilds();

				if ($menu->allow_parents) {
					if ($parent) {
						$object->setParentPath($parent->path);
					} else {
						$object->setParentPath();

					}
				} else {
					$object->setParentPath();
				}

				foreach ($directChilds as $child) {
					$child->setParentPath($object->path);
					foreach ($child->getDirectChilds() as $child2) {
						$child2->setParentPath($child->path);
						$child2->update();
					}
					$child->update();
				}

				$object->update();

//				$article = $stm->getRepository(DB\Article::class)->one(['template' => 'web_article']);

				if (!$values['no_content']) {

					if ($article) {
						$article->loadFromArray($values['article']);
						$article->fk_side_panel = $values['article']['fk_side_panel'] ? $values['article']['fk_side_panel'] : null;
						$article->update();
						$form->onEdit($form, $values, $article, $cache);
					} else {
						$form->onEdit($form, $values, $article, $cache);
//						dump($object->page->template);
//						$page = $this->presenter->pages->getPageByTpl($object->page->template, (array) $values);
//						dump($page);
					}
				}
			}

			//$object = $object ?? $stm->getRepository(DB\Article::class)->create();
			//$object->loadFromArray((array) $values);

			$form->getPresenter()->flashMessage('Uloženo', 'success');
			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('menu', ['menu' => $menu]);
			} else {
				$form->getPresenter()->redirect('this',$object);
			}
		};
	}

	public function actionArticlesDetail(DB\Article $object = null)
	{
//		if (!($object && $object->getPK() == '404')) {
//			$this->setTemplateFile('default-form.latte');
//		}

		$this->admin->title = $object ? 'Článek: ' . $object->name : 'Nový článek';
		$this->admin->icon = 'fa fa-font';

		$form = $this->createForm('form');
		$form->addGroup('Jazykové verze');
		$form->addLangSelector();
		$form->addGroup('Hlavní údaje');

		$form->addText('name', 'Název')->setAttribute('class', 'fillseo');
		$form->addLocaleRichEdit('text', 'Obsah', 'big');

		if (!($object && $object->getPK() == '404')) {
			$form->addGroup('SEO');
			$form->addPageContainer('web_article', ['article' => $object], $object);
		}

		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');

		$stm = $this->stm;
		$cache = $this->cache;
		$form->onSuccess[] = function ($form, $values) use ($object, $stm, $cache) {
			$new = !$object;
			$object = $object ?? $stm->getRepository(DB\Article::class)->create();
			$object->loadFromArray((array) $values);

			$new ? $stm->getRepository(DB\Article::class)->add($object) : $object->update();

			$form->onEdit($form, $values, $object, $cache);

			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('articles');
			} else {
				$form->getPresenter()->redirect('this',$object);
			}
			$form->getPresenter()->redirect($new ? 'articles' : 'this');
		};

		return;
	}

	public function actionTextboxes()
	{
		$this->admin->title = 'Textové boxy';
		$this->admin->icon = 'fa fa-align-justify';
		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('textboxesDetail') . '"><i class="fas fa-plus"></i> Přidat textbox</a>';

		$source = new CMS\Source\Collection($this->stm->getRepository(DB\Textbox::class)->many());

		// DateGrid
		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['priority' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Interní název', 'name')->addValue('%s', 'name');
		$table->addColumn('Kód', 'id')->addValue('[widget]textboxes=%s[/widget]', 'id');
//		$table->addColumn('Titulek', 'title')->addValue('%s', 'title');
//		$table->addColumnMin('Pořadí', 'priority')->addText('priority')->setAttribute('style', 'width: 40px;');

		$table->addColumnActionEdit('textboxesDetail');
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});
	}

	public function actionTextboxesDetail(DB\Textbox $object = null)
	{
		$this->admin->title = $object ? 'Textový box: ' . $object->name : 'Nový textový box';
		$this->admin->icon = 'fa fa-align-justify';

		$form = $this->createForm('form');
		$form->addGroup('Hlavní údaje');
		$form->addLangSelector();
		$form->addText('name', 'Interní název');
//		$form->addLocaleText('title', 'Title');
		$form->addLocaleRichEdit('text', 'Obsah', 'small');

//		$form->addText('priority', 'Pořadí')->setDefaultValue(0)->setRequired(true)->addRule($form::INTEGER);

		$upload->onDelete[] = function () use ($object, $form) {
			$object->image = null;
			$object->update();
			$form->getPresenter()->redirect('this');
		};

		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$form->addSubmit('submit', 'Uložit');
		$form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');

		$stm = $this->stm;
		$cache = $this->cache;
		$form->onSuccess[] = function ($form, $values) use ($object, $stm, $cache) {
			$new = !$object;
			if ($new) {
				$values['id'] = \Nette\Utils\Random::generate(4);
			}
			$object = $object ?? $stm->getRepository(DB\Textbox::class)->create();
			$object->loadFromArray((array) $values);
			$new ? $stm->getRepository(DB\Textbox::class)->add($object) : $object->update();
			$object->update();

			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('textboxes');
			} else {
				$form->getPresenter()->redirect('this', $object);
			}
		};

		return;
	}

	public function actionLogos()
	{
		$this->admin->title = 'Přehled log widgetů';
		$this->admin->icon = 'far fa-images';

		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('logosDetail') . '"><i class="fa fa-plus"></i> Přidat logo widget</a>';
		$source = new CMS\Source\Collection($this->stm->getRepository(DB\Logo::class)->many());
		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['name' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);

		$table->addColumn('Název')->addText('name');
		$table->addColumn('Kód', 'id')->addValue('[widget]logos=%s[/widget]', 'id');
		$table->addColumnMin()->addValue('<a class="btn btn-sm btn-dark" href="%s"><i class="far fa-images"></i> Loga</a>', ['logosImages', ['object' => '%s']]);
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});
	}

	public function actionLogosDetail(DB\Logo $object = null)
	{
		$this->admin->title = $object ? 'Logo widget: ' . $object->name : 'Nový logo widget';
		$this->admin->icon = 'far fa-images';

		$form = $this->createForm('form');
		$form->addGroup('Jazykové verze');
		$form->addLangSelector();
		$form->addGroup('Hlavní údaje');
		$form->addLocaleText('name', 'Název');

		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$form->addSubmit('submit', 'Uložit');
		$form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');

		$stm = $this->stm;
		$form->onSuccess[] = function ($form, $values) use ($object, $stm) {
			$new = !$object;
			$object = $object ?? $stm->getRepository(\Lqd\Web\DB\Logo::class)->create();
			$values = $form->getValues(true);

			if ($new) {
				$values['id'] = \Nette\Utils\Random::generate(3);
			}

			$object->loadFromArray($values);

			$new ? $stm->getRepository(\Lqd\Web\DB\Logo::class)->add($object) : $object->update();

			$object->update();

			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('logos');
			} else {
				$form->getPresenter()->redirect('this', $object);
			}
		};
	}

	public function actionLogosImages(DB\Logo $object)
	{
		$this->admin->title = 'Loga widgetu (' . $object->name . ')';
		$this->admin->icon = 'far fa-images';

		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Web\DB\LogoItem::class)->many()->where('fk_logo', $object->uuid));

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['priority' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumnMin()->addValue('<a href="%s" data-lightbox="gallery-images"><i class="far fa-images"></i></a>', function ($object) {
			return $this->userUrl . '/logos/' . $object->image;
		});
		$table->addColumn('Název')->addText('name');
//		$table->addColumn('Popisek')->addText('description');
		$table->addColumn('Adresa')->addText('link');
		$table->addColumn('Výška (auto/px)', 'height', ['style' => 'width: 140px;'])->addText('height');
		$table->addColumnMin('Pořadí', 'priority')->addText('priority')->setAttribute('style', 'width: 40px;');
		$table->addColumn('Skrytý', 'hidden', ['class' => 'minimal'])->addLqdCheckbox('hidden');

		$table->addColumnActionDelete();

		// Create form
		$form = $this->createForm('form');
		$form->getElementPrototype()->setAttribute('class', 'form-inline');
		$form->addMultiUpload('images');
		$form->addSubmit('submit', 'Nahrát loga');
		$form->setRenderer(new CMS\Form\Renderer\BootstrapInline);

		$this->template->tableHeaderText = '<h4>Seznam obrázků</h4>';

		$stm = $this->stm;
		$presenter = $this;
		$logo = $object;
		$form->onSuccess[] = function ($form, $values) use ($logo, $stm, $presenter) {
			$contextParameters = $presenter->context->getParameters();
			$path = $contextParameters['userDir'] . '/logos';

			foreach ($values['images'] as $image) {
				if ($image->isImage() && $image->isOk()) {
					$object = $stm->getRepository(\Lqd\Web\DB\LogoItem::class)->create();
					$ext = pathinfo($image->getSanitizedName(), PATHINFO_EXTENSION);
					$object->logo = $logo;
					$object->image = $object->getPK() . '.' . $ext;
					$stm->getRepository(\Lqd\Web\DB\LogoItem::class)->add($object);

					$image = $image->toImage();
					$image->resize(300, null);
//					$image->sharpen();
					$image->save($path . '/' . $object->image);
				}
			}

			$form->getPresenter()->flashMessage('Uloženo', 'success');
			$form->getPresenter()->redirect('this');
		};

		return;
	}

	public function actionGalleries()
	{
		$this->admin->title = 'Přehled galerii';
		$this->admin->icon = 'far fa-images';

		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('galleriesDetail') . '"><i class="fa fa-plus"></i> Přidat galerii</a>';

		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Web\DB\Gallery::class)->many());

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['name' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);

		$table->addColumn('Název', 'name')->addValue('%s', 'name');
		$table->addColumn('Kód', 'id')->addValue('[widget]gallery=%s[/widget]', 'id');
		$table->addColumn('Šířka velkého náhledu', 'originWidth')->addValue('%s', function ($object) {
			return $object->originWidth != 0 ? $object->originWidth . 'px' : 'dopočítat';
		});
		$table->addColumn('Výška velkého náhledu', 'originHeight')->addValue('%s', function ($object) {
			return $object->originHeight != 0 ? $object->originHeight . 'px' : 'dopočítat';
		});
		$table->addColumn('Šířka malého náhledu', 'thumbWidth')->addValue('%s', function ($object) {
			return $object->thumbWidth != 0 ? $object->thumbWidth . 'px' : 'dopočítat';
		});
		$table->addColumn('Výška malého náhledu', 'thumbHeight')->addValue('%s', function ($object) {
			return $object->thumbHeight != 0 ? $object->thumbHeight . 'px' : 'dopočítat';
		});
//		$table->addColumn('Počet fotek na řádek')->addValue('%s','ratio');
		$table->addColumn('CSS')->addValue('%s','classes');
		$table->addColumnMin('Skrytý')->addLqdCheckbox('hidden')->setAttribute('style', 'width: 40px;');

		$table->addColumnMin()->addValue('<a class="btn btn-sm btn-info" href="%s"><i class="far fa-images"></i> Obrázky</a>', ['galleriesImages', ['gallery' => '%s']]);
		$table->addColumnActionEdit('galleriesDetail');
		$table->addColumnMin()->addValue('<a href="%s" class="text-danger" onclick="return confirm(\'Opravdu smazat?\')"><i class="fas fa-trash-alt"></i></a>', ['deleteGallery',['object' => '%s']]);
//		$table->addColumnActionDelete('deleteGallery');

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});
	}

	public function actionDeleteGallery(DB\Gallery $object)
	{
		foreach ($object->images as $image) {
			$this->handleDeleteGalleryImage($image);
		}
		$object->delete();
		$this->redirect('galleries');
	}

	public function handleDeleteGalleryImage(DB\GalleryImage $object, string $link = null)
	{
		if (file_exists($this->context->getParameters()['userDir'] . '/gallery/origin/'.$object->image)) {
			unlink($this->context->getParameters()['userDir'] . '/gallery/origin/'.$object->image);
		}
		if (file_exists($this->context->getParameters()['userDir'] . '/gallery/thumbs/'.$object->image)) {
			unlink($this->context->getParameters()['userDir'] . '/gallery/thumbs/'.$object->image);

		}
		$object->delete();

		if ($link) {
			$this->redirect($link);
		}
	}

	public function actionGalleriesDetail(\Lqd\Web\DB\Gallery $object = null)
	{
		$this->admin->title = $object ? 'Galerie: ' . $object->name : 'Nová galerie';
		$this->admin->icon = 'far fa-images';

		$form = $this->createForm('form');
		$form->addGroup('Jazykové verze');
		$form->addLangSelector();
		$form->addGroup('Hlavní údaje');
		$form->addLocaleText('name', 'Název');
		$form->addText('originWidth', 'Max šířka (px)')
			->setAttribute('size', 4);
		$form->addText('originHeight', 'Max výška (px)')
			->setAttribute('size', 4)
			->setOption('description','Velké foto zachováva poměr stran')
			->setRequired(false)
			->addConditionOn($form['originWidth'], $form::EQUAL, '')
			->setRequired('Šířka nebo výška velkého náhledu musí být větší než 0px')
			->endCondition();
		$form->addSelect('resizeMethod', 'Styl ořezu náhledu')->setItems(DB\Gallery::RESIZE_METHODS);
		$form->addText('thumbWidth', 'Náhled šířka (px)')
			->setAttribute('size', 4)
			->setRequired(false)
			->addConditionOn($form['resizeMethod'], \Nette\Application\UI\Form::EQUAL,'EXACT')
			->setRequired(true)
			->elseCondition()
			->addConditionOn($form['resizeMethod'], \Nette\Application\UI\Form::EQUAL, 'STRETCH')
			->setRequired(true)
			->endCondition();
		$form->addText('thumbHeight', 'Náhled výška (px)')
			->setAttribute('size', 4)
			->setRequired(false)
			->addConditionOn($form['thumbWidth'], $form::EQUAL, '')
			->setRequired('Šířka nebo výška malého náhledu musí být větší než 0px')
			->elseCondition()
			->addConditionOn($form['resizeMethod'], \Nette\Application\UI\Form::EQUAL,'EXACT')
			->setRequired(true)
			->elseCondition()
			->addConditionOn($form['resizeMethod'], \Nette\Application\UI\Form::EQUAL, 'STRETCH')
			->setRequired(true)
			->endCondition();

		$form->addText('ratio','Počet fotek na řádek')->setDefaultValue('4/4/3')->setAttribute('size', 4)->setOption('description','mobile/tablet/desktop např. 4/4/3');
		$form->addText('classes','CSS třídy');

		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$form->addSubmit('submit', 'Uložit');
		$form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');

		$stm = $this->stm;
		$cache = $this->cache;

		$form->onSuccess[] = function ($form, $values) use ($object, $stm, $cache) {
			$new = !$object;
			$object = $object ?? $stm->getRepository(\Lqd\Web\DB\Gallery::class)->create();
			$values = $form->getValues(true);

			if ($new) {
				$values['id'] = \Nette\Utils\Random::generate(3);
			}

			$object->loadFromArray($values);

			$new ? $stm->getRepository(\Lqd\Web\DB\Gallery::class)->add($object) : $object->update();

			$object->update();

			$form->onEdit($form, $values, $object, $cache);

			$form->getPresenter()->flashMessage('Uloženo', 'success');
			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('galleries');
			} else {
				$form->getPresenter()->redirect('this', $object);
			}
		};
	}

	public function actionGalleriesImages(\Lqd\Web\DB\Gallery $gallery)
	{
		$this->admin->title = 'Obrázky galerie (' . $gallery->name . ')';
		$this->admin->icon = 'far fa-images';

		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Web\DB\GalleryImage::class)->many()->where('fk_gallery = :gallery', ['gallery' => $gallery->uuid]));

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['priority' => 'ASC']);
//		$table->addColumnSelector(['class' => 'minimal']);

//		$table->addColumnMin()->addValue('<a class="btn btn-sm btn-success" href="%s"><i class="far fa-images"></i> Obrázky</a>', ['images', ['gallery' => '%s']]);

		$table->addColumnMin()->addValue('<a href="%s" data-lightbox="gallery-images"><i class="far fa-images"></i></a>', function ($object) {
			return $this->userUrl . '/gallery/origin/' . $object->image;
		});
		$table->addColumn('Název')->addText('name');
		$table->addColumn('Popisek')->addText('description');
		$table->addColumnMin('Pořadí', 'priority')->addText('priority')->setAttribute('style', 'width: 40px;');

		$table->addColumnMin()->addValue('<a href="%s" class="text-danger" onclick="return confirm(\'Opravdu smazat?\')"><i class="fas fa-trash-alt"></i></a>', ['DeleteGalleryImage!',['object' => '%s', 'link' => 'this']]);
		$this->template->tableHeaderText = '<h4>Seznam obrázků</h4>';

		// Create form
		$form = $this->createForm('form');
//		$form->addGroup('Nahrávaní obrázků');
//		$form->getElementPrototype()->setAttribute('class', 'form-inline');
		$form->addMultiUpload('images')
			->setRequired();
		$form->addSubmit('submit', 'Nahrát obrázky');
		$form->setRenderer(new CMS\Form\Renderer\BootstrapInline);

		$stm = $this->stm;
		$presenter = $this;
		$form->onSuccess[] = function ($form, $values) use ($gallery, $stm, $presenter) {
			$originWidth = $gallery->originWidth != 0 ? $gallery->originWidth : null;
			$originHeight = $gallery->originHeight != 0 ? $gallery->originHeight : null;
			$thumbWidth = $gallery->thumbWidth != 0 ? $gallery->thumbWidth : null;
			$thumbHeight = $gallery->thumbHeight != 0 ? $gallery->thumbHeight : null;

			$contextParameters = $presenter->context->getParameters();

			foreach ($values['images'] as $image) {
				if ($image->isImage() && $image->isOk()) {
					$ext = pathinfo($image->getSanitizedName(), PATHINFO_EXTENSION);

					$galleryImage = $stm->getRepository(\Lqd\Web\DB\GalleryImage::class)->create(['fk_gallery' => $gallery->getPK(), 'image' => '']);
					$stm->getRepository(\Lqd\Web\DB\GalleryImage::class)->add($galleryImage);
					$galleryImage->image = $galleryImage->getPK() . '.' . $ext;
					$galleryImage->update();

					/* Prepare path, ext, origin and thumbnail */
					$path = $contextParameters['userDir'] . '/gallery';
					$origin = $image->toImage();
					$thumbnail = clone $origin;

					$method = $gallery->resizeMethod;


					/* Reize both */
					$origin->resize($originWidth, $originHeight, \Nette\Utils\Image::FIT);
					$thumbnail->resize($thumbWidth, $thumbHeight, constant('\Nette\Utils\Image::' . $method));
					$origin->sharpen();
					$thumbnail->sharpen();

					$origin->save($path . '/origin/' . $galleryImage->image);
					$thumbnail->save($path . '/thumbs/' . $galleryImage->image);
				}
			}

			$form->getPresenter()->flashMessage('Obrázky byly přidány', 'success');
			$presenter->redirect('this', $gallery);
		};

		return;
	}

	public function actionMaps()
	{
		$this->admin->title = 'Mapy';
		$this->admin->icon = 'fa fa-map-o';

		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('mapsDetail') . '"><i class="fa fa-plus"></i> Přidat mapu</a>';
		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Web\DB\Map::class)->many());

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['name' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Název', 'name')->addValue('%s', 'name');
		$table->addColumn('Kód', 'id')->addValue('[widget]maps=%s[/widget]', 'id');
		$table->addColumn('Šířka widgetu', 'width')->addValue('%s', 'width');
		$table->addColumn('Výška widgetu', 'height')->addValue('%s', 'height');
		$table->addColumn('Zoom', 'zoom')->addValue('%s', 'zoom');
		$table->addColumn('Typ mapy', 'map_type')->addValue('%s', function ($object) {
			return \Lqd\Web\DB\Map::MAP_TYPES[$object->map_type];
		});
//        $table->addColumn('Zoomování', 'show_zoom', ['class' => 'minimal'])->addCheckbox('show_zoom');
		$table->addColumn('Zobrazit ovládaní mapy', 'enable_ui', ['style' => 'width: 100px;'])->addLqdCheckbox('enable_ui');
		$table->addColumn('Povolit posouvání kurzorem', 'draggable', ['class' => 'minimal'])->addLqdCheckbox('draggable');

		$table->addColumnMin()->addValue('<a class="btn btn-sm btn-dark" href="%s"><i class="fas fa-map-marker-alt"></i> Mapové body</a>', ['mapsMarkers', ['object' => '%s']]);

		$table->addColumnActionEdit('mapsDetail2');
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});
	}

	public function actionMapsDetail(\Lqd\Web\DB\Map $object = null)
	{
		$this->admin->title = $object ? 'Mapa: ' . $object->name : 'Nová mapa';
		$this->admin->icon = 'fa fa-map-o';

		$form = $this->createForm('form', true);
		$form->addGroup('Jazykové verze');
		$form->addLangSelector();

		$form->addGroup('Hlavní údaje');
		$form->addLocaleText('name', 'Název');
		$form->addText('width', 'Šířka widgetu')->setRequired(true)->setDefaultValue('100%')->setAttribute('size',5);
		$form->addText('height', 'Výška widgetu')->setRequired(true)->setDefaultValue('400px')->setAttribute('size',5);
		$form->addText('zoom', 'Zoom')->setAttribute('size',4)->setAttribute('style','width: 67px !important;')->setRequired(true)->setDefaultValue(15);
		$form->addSelect('map_type', 'Typ mapy')->setItems(\Lqd\Web\DB\Map::MAP_TYPES);

//        $form->addCheckbox('show_zoom', 'Zoomování')
//            ->setDefaultValue(true);
		$form->addCheckbox('enable_ui', 'Zobrazit ovládaní mapy')->setDefaultValue(true);
		$form->addCheckbox('draggable', 'Povolit posouvání kurzorem')->setDefaultValue(true);

		$form->addGroup('Mapový bod');
		$marker = $form->addContainer('marker');
		$marker->addLocaleText('name', 'Název');
		$marker->addText('address', 'Adresa')->setRequired(true);
		$marker->addLocaleRichEdit('text','Text', 'small');


		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$form->addSubmit('submit', 'Uložit');
		$form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');


		$stm = $this->stm;
		$form->onSuccess[] = function ($form) use ($object, $stm) {

			$stm->getConnection()->getLink()->beginTransaction();
			$new = !$object;
			$object = $object ?? $stm->getRepository(\Lqd\Web\DB\Map::class)->create();
			$values = $form->getValues(true);

			if ($new) {
				$values['id'] = \Nette\Utils\Random::generate(3);
			}

			$object->loadFromArray($values);

			$new ? $stm->getRepository(\Lqd\Web\DB\Map::class)->add($object) : $object->update();

			$object->update();

			$geocode = $this->getGeoCode($values['marker']['address']);

			$marker = $stm->getRepository(DB\MapMarker::class)->create(['fk_map' => $object->getPK(), 'latitude' => $geocode[0], 'longitude' => $geocode[1]]);
			$marker->loadFromArray($values['marker']);
			$stm->getRepository(DB\MapMarker::class)->add($marker);

			$stm->getConnection()->getLink()->commit();

			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('maps');
			} else {
				$form->getPresenter()->redirect('mapsDetail2', $object);
			}
		};
	}

	public function actionMapsDetail2(\Lqd\Web\DB\Map $object)
	{
		$this->admin->title = $object ? 'Mapa: ' . $object->name : 'Nová mapa';
		$this->admin->icon = 'fa fa-map-o';

		$form = $this->createForm('form', true);
		$form->addGroup('Jazykové verze');
		$form->addLangSelector();

		$form->addGroup('Hlavní údaje');
		$form->addLocaleText('name', 'Název');
		$form->addText('width', 'Šířka widgetu')->setRequired(true)->setDefaultValue('100%')->setAttribute('size',5);
		$form->addText('height', 'Výška widgetu')->setRequired(true)->setDefaultValue('400px')->setAttribute('size',5);
		$form->addText('zoom', 'Zoom')->setAttribute('size',4)->setAttribute('style','width: 67px !important;')->setRequired(true);
		$form->addSelect('map_type', 'Typ mapy')->setItems(\Lqd\Web\DB\Map::MAP_TYPES);

//        $form->addCheckbox('show_zoom', 'Zoomování')
//            ->setDefaultValue(true);
		$form->addCheckbox('enable_ui', 'Zobrazit ovládaní mapy')->setDefaultValue(true);
		$form->addCheckbox('draggable', 'Povolit posouvání kurzorem')->setDefaultValue(true);


		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$form->addSubmit('submit', 'Uložit');
		$form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');


		$stm = $this->stm;
		$form->onSuccess[] = function ($form) use ($object, $stm) {

			$stm->getConnection()->getLink()->beginTransaction();
			$new = !$object;
			$object = $object ?? $stm->getRepository(\Lqd\Web\DB\Map::class)->create();
			$values = $form->getValues(true);

			if ($new) {
				$values['id'] = \Nette\Utils\Random::generate(3);
			}

			$object->loadFromArray($values);

			$new ? $stm->getRepository(\Lqd\Web\DB\Map::class)->add($object) : $object->update();

			$object->update();

			$stm->getConnection()->getLink()->commit();

			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('maps');
			} else {
				$form->getPresenter()->redirect('this', $object);
			}
		};
	}

	public function actionMapsMarkers(\Lqd\Web\DB\Map $object)
	{
		$this->admin->title = 'Body mapy (' . $object->name . ')';
		$this->admin->icon = 'fa fa-map-o';

		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('mapMarkersDetail', ['object' => $object]) . '"><i class="fa fa-plus"></i> Přidat mapový bod</a>';
		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Web\DB\MapMarker::class)->many()->where('fk_map', $object));

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['name' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Název', 'name')->addValue('%s', 'name');
		$table->addColumn('Zeměpisná šířka', 'latitude')->addValue('%s', 'latitude');
		$table->addColumn('Zeměpisná délka', 'longitude')->addValue('%s', 'longitude');
		$table->addColumn('Adresa', 'address')->addValue('%s', 'address');

		$table->addColumnActionEdit('mapMarkersEdit');
		$table->addColumnActionDelete();
		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});
	}

	public function actionMapMarkersDetail(\Lqd\Web\DB\Map $object)
	{
		$this->admin->title = 'Nový mapový bod';
		$this->admin->icon = 'fa fa-map-o';

		$form = $this->createForm('form', true);
		$form->addGroup('Jazykové verze');
		$form->addLangSelector();
		$form->addGroup('Hlavní údaje');
		$form->addLocaleText('name', 'Název');
		$form->addText('address', 'Adresa')->setRequired(true);
		$form->addHidden('map')->setDefaultValue($object->uuid);
		$form->addLocaleRichEdit('text','Text', 'small');

		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');


		$stm = $this->stm;

		$form->onSuccess[] = function ($form) use ($stm, $object) {
			$values = $form->getValues(true);

			$geocode = $this->getGeoCode($values['address']);
			$marker = $stm->getRepository(\Lqd\Web\DB\MapMarker::class)->create();
			$marker->loadFromArray($values);
			$marker->latitude = $geocode[0];
			$marker->longitude = $geocode[1];
			$stm->getRepository(\Lqd\Web\DB\MapMarker::class)->add($marker);

			$marker->update();
			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('mapsMarkers',$object);
			} else {
				$form->getPresenter()->redirect('mapMarkersEdit', $marker);
			}
		};
	}

	public function actionMapMarkersEdit(\Lqd\Web\DB\MapMarker $object)
	{
		$this->admin->title = 'Mapový bod: ' . $object->name;
		$this->admin->icon = 'fa fa-map-o';

		$form = $this->createForm('form', true);
		$form->addGroup('Jazykové verze');
		$form->addLangSelector();
		$form->addGroup('Hlavní údaje');
		$form->addLocaleText('name', 'Název');
		$form->addText('address', 'Adresa')->setRequired(true);
//		$form->addHidden('map')->setDefaultValue($object->uuid);
		$form->addLocaleRichEdit('text','Text', 'small');

		$form->setDefaults($object ? $object->jsonSerialize() : []);

		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');


		$stm = $this->stm;
		$form->onSuccess[] = function ($form) use ($stm, $object) {
			$values = $form->getValues(true);

			$geocode = $this->getGeoCode($values['address']);
			$object->loadFromArray($values);
			$object->latitude = $geocode[0];
			$object->longitude = $geocode[1];

			$object->update();
			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('mapsMarkers',$object->map);
			} else {
				$form->getPresenter()->redirect('this', $object);
			}
		};
	}

	public function getGeoCode(string $address)
	{
		// url encode the address
		$address = urlencode($address);

		$url = "https://maps.googleapis.com/maps/api/geocode/json?key=" . DB\Map::GOOGLE_MAP_KEY . "&address={$address}";


		// decode the json
		$resp = json_decode(file_get_contents($url), true);

		// response status will be 'OK', if able to geocode given address
		if ($resp['status'] == 'OK') {
			// get the important data
			$lati = $resp['results'][0]['geometry']['location']['lat'];
			$longi = $resp['results'][0]['geometry']['location']['lng'];
			$formatted_address = $resp['results'][0]['formatted_address'];

			// verify if data is complete
			if ($lati && $longi && $formatted_address) {
				// put the data in the array
				$data_arr = [];

				array_push($data_arr, $lati, $longi, $formatted_address);

				return $data_arr;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	public function actionTabs()
	{
		$this->admin->title = 'Skupina tabů';
		$this->admin->icon = 'fas fa-bookmark';

		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('tabsDetail') . '"><i class="fa fa-plus"></i> Přidat skupinu tabů</a>';
		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Web\DB\Tab::class)->many());

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['name' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Název', 'name')->addValue('%s', 'name');
		$table->addColumn('Kód', 'id')->addValue('[widget]tabs=%s[/widget]', 'id');
		$table->addColumnMin()->addValue('<a class="btn btn-sm btn-dark" href="%s"><i class="fas fa-bookmark"></i> Taby</a>', ['tabsItems', ['object' => '%s']]);

		$table->addColumnActionEdit('tabsDetail');
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});
	}

	public function actionTabsDetail(\Lqd\Web\DB\Tab $object = null)
	{
		$this->admin->title = $object ? 'Skupina tabů: ' . $object->name : 'Nová skupina tabů';
		$this->admin->icon = 'fa fa-bookmark-o';

		$form = $this->createForm('form', true);
		$form->addGroup('Jazykové verze');
		$form->addLangSelector();
		$form->addGroup('Hlavní údaje');
		$form->addLocaleText('name', 'Název');
		$form->addCheckbox('first_mobile','První položka bude na mobilu rozbalena');

		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');

		$stm = $this->stm;

		$form->onSuccess[] = function ($form) use ($object, $stm) {
			$new = !$object;
			$object = $object ?? $stm->getRepository(\Lqd\Web\DB\Tab::class)->create();
			$values = $form->getValues(true);

			if ($new) {
				$values['id'] = \Nette\Utils\Random::generate(3);
			}

			$object->loadFromArray($values);

			$new ? $stm->getRepository(\Lqd\Web\DB\Tab::class)->add($object) : $object->update();

			$object->update();

			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('tabs');
			} else {
				$form->getPresenter()->redirect('this', $object);
			}
		};
	}

	public function actionTabsItems(\Lqd\Web\DB\Tab $object)
	{
		$this->admin->title = 'Položky skupiny (' . $object->name . ')';
		$this->admin->icon = 'fa fa-bookmark-o';

		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('tabsItemsDetail', ['object' => $object]) . '"><i class="fa fa-plus"></i> Přidat tab</a>';
		$source = new \Lqd\CMS\Source\Collection($object->items);

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['priority' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Název', 'name')->addValue('%s', 'name');
		$table->addColumnMin('Pořadí', 'priority')->addText('priority')->setAttribute('style', 'width: 40px;');
		$table->addColumnActionEdit('tabsItemsedit');
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});
	}

	public function actionTabsItemsDetail(\Lqd\Web\DB\Tab $object)
	{
		$this->admin->title = 'Nový tab';
		$this->admin->icon = 'fa fa-bookmark-o';

		$form = $this->createForm('form', true);
		$form->addGroup('Hlavní údaje');
		$form->addLangSelector();

		$form->addLocaleText('name', 'Název');
		$form->addLocaleRichEdit('text', 'Obsah', 'small');
		$form->addText('priority', 'Pořadí')
			->setDefaultValue(10)
			->setAttribute('size',4);
		$form->addHidden('fk_tab', $object->uuid);


		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');


		$stm = $this->stm;

		$form->onSuccess[] = function ($form) use ($stm, $object) {
			$values = $form->getValues(true);

			$item = $stm->getRepository(\Lqd\Web\DB\TabItem::class)->create(['fk_tab' => $values['fk_tab']]);
			$item->loadFromArray($values);
			$stm->getRepository(\Lqd\Web\DB\TabItem::class)->add($item);
			$item->update();

			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('tabsItems', $object);
			} else {
				$form->getPresenter()->redirect('tabsItemsedit', $item);
			}
		};
	}

	public function actionTabsItemsedit(\Lqd\Web\DB\TabItem $object)
	{
		$this->admin->title = 'Úprava tabu: ' . $object->name;
		$this->admin->icon = 'fa fa-bookmark-o';

		$form = $this->createForm('form', true);
		$form->addGroup('Hlavní údaje');
		$form->addLangSelector();
		$form->addLocaleText('name', 'Název');
		$form->addLocaleRichEdit('text', 'Obsah', 'small');
		$form->addText('priority', 'Pořadí')->setDefaultValue(10)->setRequired(true)->setAttribute('size',4);

		$form->setDefaults($object->jsonSerialize());

		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');


		$stm = $this->stm;

		$form->onSuccess[] = function ($form) use ($stm, $object) {
			$values = $form->getValues(true);

			$object->loadFromArray($values);
			$object->update();

			$form->getPresenter()->flashMessage('Uloženo', 'success');
			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('tabsItems', $object->tab);
			} else {
				$form->getPresenter()->redirect('tabsItemsedit', $object);
			}
		};
	}

	public function actionSliders()
	{
		$this->admin->title = 'Přehled sliderů';
		$this->admin->icon = 'fa fa-slideshare';

		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('slidersDetail') . '"><i class="fa fa-plus"></i> Přidat slider</a>';

		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Web\DB\Slider::class)->many());

		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['name' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);

		$table->addColumn('Název', 'name')->addValue('%s', 'name');
		$table->addColumn('Kód', 'id')->addValue('[widget]slider=%s[/widget]', 'id');
		$table->addColumnMin('Skrytý')->addLqdCheckbox('hidden')->setAttribute('style', 'width: 40px;');

		$table->addColumnMin()->addValue('<a class="btn btn-sm btn-dark" href="%s"><i class="far fa-images"></i> Obrazovky</a>', ['slidersSlides', ['slider' => '%s']]);
		$table->addColumnActionEdit('detail');
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

//		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('web_slider.name LIKE :q', ['q' => '%' . $value . '%']);
		});
	}

	public function actionSlidersDetail(\Lqd\Web\DB\Slider $object = null)
	{
		$this->admin->title = $object ? 'Slider: ' . $object->name : 'Nový slider';
		$this->admin->icon = 'fa fa-slideshare';

		$form = $this->createForm('form');
		$form->addGroup('Hlavní údaje');
		$form->addText('name', 'Název');

		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');

		$stm = $this->stm;
		$cache = $this->cache;

		$form->onSuccess[] = function ($form, $values) use ($object, $stm, $cache) {
			$new = !$object;
			$object = $object ?? $stm->getRepository(\Lqd\Web\DB\Slider::class)->create();
			$values = $form->getValues(true);

			if ($new) {
				$values['id'] = \Nette\Utils\Random::generate(3);
			}

			$object->loadFromArray($values);

			$new ? $stm->getRepository(\Lqd\Web\DB\Slider::class)->add($object) : $object->update();

			$object->update();

			$form->onEdit($form, $values, $object, $cache);

			$form->getPresenter()->flashMessage('Uloženo', 'success');

			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('sliders');
			} else {
				$form->getPresenter()->redirect('this', $object);
			}
		};
	}

	public function actionSlidersSlides(\Lqd\Web\DB\Slider $slider)
	{
		$this->admin->title = 'Obrazovky (' . $slider->name . ')';
		$this->admin->icon = 'fa fa-slideshare';
		$this->template->buttons = '<a class="btn btn-success" href="' . $this->link('slidersSlidesdetail', ['slider' => $slider]) . '"><i class="fa fa-plus"></i> Přidat obrazovku</a>';

		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(\Lqd\Web\DB\Slide::class)->many()->where('fk_slider = :slider', ['slider' => $slider->uuid]));

		// DateGrid
		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['priority' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumnMin()->addValue('<a href="%s" data-lightbox="gallery-images"><i class="far fa-images"></i></a>', function ($object) {
			return $this->userUrl . '/slider/' . $object->image;
		});
		$table->addColumn('Název', 'name')->addValue('%s', 'name');
//		$table->addColumn('Titulek', 'title')->addValue('%s', 'title');
//		$table->addColumn('Adresa')->addValue('<a href="%s" target="_blank">%s</a>', 'link', 'link');
		$table->addColumnMin('Pořadí', 'priority')->addText('priority')->setAttribute('style', 'width: 40px;');

		$table->addColumnActionEdit('slidersSlidesdetail', ['slider' => $slider]);
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});
	}

	public function actionSlidersSlidesdetail(DB\Slider $slider, DB\Slide $object = null)
	{
		$this->admin->title = $object ? 'Obrazovka: ' . $object->name : 'Nová obrazovka';
		$this->admin->icon = 'fa fa-slideshare';

		$form = $this->createForm('form');
		$form->addGroup('Hlavní údaje');
		$form->addLangSelector();
		$form->addText('name', 'Název');
//		$form->addLocaleText('title', 'Title');
		$form->addLocaleRichEdit('text', 'Obsah', 'small');
//        $form->addText('link', 'Odkaz');
//        $form->addLocaleText('link_name', 'Název odkazu');
		$upload = $form->addUploadImage('image', 'Obrázek v pozadí', ['slider' => function (\Nette\Utils\Image $image) { /*$image->resize(200, NULL);*/
		},]);
		$form->addText('priority', 'Pořadí')->setDefaultValue(10)->setRequired(true)->setAttribute('size',4);

		$upload->onDelete[] = function () use ($object, $form) {
			$object->image = null;
			$object->update();
			$form->getPresenter()->redirect('this');
		};

		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');


		$stm = $this->stm;
		$cache = $this->cache;
		$form->onSuccess[] = function ($form, $values) use ($object, $stm, $cache, $slider) {
			$new = !$object;
			$object = $object ?? $stm->getRepository(DB\Slide::class)->create();

			$object->loadFromArray((array) $values);
			$object->fk_slider = $slider->uuid;
			$new ? $stm->getRepository(DB\Slide::class)->add($object) : $object->update();
			$object->update();

			$object->image = $form['image']->isUpload() ? $form['image']->upload($object->getPK() . '.%2$s') : $object->image;
			$object->update();

			$form->getPresenter()->flashMessage('Uloženo', 'success');
			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('slidersSlides', ['slider' => $slider]);
			} else {
				$form->getPresenter()->redirect('this', $slider, $object);
			}
		};

		return;
	}

	public function actionSidePanel()
	{
		$this->admin->title = 'Sidepanely';
		$this->template->buttons = Html::el('a')->setAttribute('href', $this->link('sidePanelDetail'))->setAttribute('class', 'btn btn-success')->addHtml(Html::el('i')->setAttribute('class', 'fa fa-plus'))->addText('Přidat sidepanel');

		$source = new \Lqd\CMS\Source\Collection($this->stm->getRepository(DB\SidePanel::class)->many());
		$table = $this->createDataGrid($source);
		$table->setDefaultOrderBy(['name' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Název', 'name')->addValue('%s', 'name');
		$table->addColumnActionEdit('sidePanelDetail');
		$table->addColumnActionDelete();

		// Filter form
		$table['filter']->addText('q')->setAttribute('placeholder', 'název');

		// Filter definition
		$table->addFilter('q', function ($source, $value) {
			$source->where('name LIKE :q', ['q' => $value . '%']);
		});

		return;
	}

	public function actionSidePanelDetail(DB\SidePanel $object = null)
	{
		$this->admin->title = $object ? 'Sidepanel: ' . $object->name : 'Nový sidepanel';
		$form = $this->createForm('form', true);
		$form->addGroup('Jazykové verze');
		$form->addLangSelector();
		$form->addGroup('Hlavní údaje');
		$form->addText('name', 'Název');
		$form->addLocaleRichEdit('text', 'Obsah', 'small');

		$form->setDefaults($object ? $object->jsonSerialize() : []);
		$form->addSubmit('submit', 'Uložit');
        $form->addSubmit('submit2', 'Uložit a pokračovat')->setAttribute('class','btn btn-primary');

		$stm = $this->stm;

		$form->onSuccess[] = function ($form) use ($object, $stm) {
			$new = !$object;
			$object = $object ?? $stm->getRepository(DB\SidePanel::class)->create();
			$values = $form->getValues(true);

			$object->loadFromArray($values);
			$new ? $stm->getRepository(DB\SidePanel::class)->add($object) : $object->update();
			$form->getPresenter()->flashMessage('Uloženo', 'success');
			if ($form['submit']->isSubmittedBy()) {
				$form->getPresenter()->redirect('sidePanel');
			} else {
				$form->getPresenter()->redirect('this', $object);
			}
		};
	}
}
