<?php

namespace App\Catalog\Admin;

use Lqd\Admin\Control\DataGrid;
use Lqd\Admin\Control\Form;
use Lqd\Pages\DB\Page;
use Lqd\Security;
use Lqd\CMS;
use App\Catalog\DB;
use Nette\Caching\Cache;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Nette\Utils\Image;
use Nette\Utils\Random;
use Nette\Utils\Strings;

class CatalogPresenter extends \Lqd\Admin\BasePresenter
{
    /**
     * @inject
     * @var \Nette\Caching\IStorage
     */
    public $cache;


    public function startup()
	{
		parent::startup();
		
		$this['menu']->addItem('Produkty', 'products');
		$this['menu']->addItem('Kategorie', 'categories');
//		$this['menu']->addItem('Výrobci', 'producers');
		//$this['menu']->addItem('Filtry', 'parameters');
		//$this['menu']->addItem('Tagy', 'tags');
//		$this['menu']->addItem('Štítky', 'labels');
		//$this['menu']->addItem('Slevové kupóny', 'coupons');

	}

    public function actionDefault()
    {
        $this->redirect('products');
    }

    public function actionCategories()
    {
        $this->admin->title = 'Kategorie';
        $this->admin->icon = 'fa fa-cubes';

	    $this->template->buttons = '<a class="btn btn-success btn-sm" href="'.$this->link('categoriesDetail').'"><i class="fa fa-plus"></i> Přidat kategorii</a>';


	    $source = new CMS\Source\TreeCollection($this->stm->getRepository(\App\Catalog\DB\Category::class)->many());

        // Datagrid
        $table = new DataGrid($this, 'table', $source);
        $table->setDefaultOrderBy(['priority' => 'ASC']);
        $table->addColumnSelector(['class' => 'minimal']);
        $table->addColumn('Název', 'name')->addValue('<a target="_blank" href="%s">%s</a>',
            [':Catalog:Category:default', ['category' => '%s']],
            'name');
	    $table->addColumn('Kód', 'code')->addValue('%s','code');
	    $table->addColumnMin('Pořadí','priority')->addText('priority');
        $table->addColumnMin('Industry','industry')->addCheckbox('industry');
        $table->addColumnMin('Skrytá','hidden')->addCheckbox('hidden');
        $table->addColumnActionEdit('categoriesDetail');
        $table->addColumnActionDelete();



        // Filter form
        $table['filter']->addText('q')->setAttribute('placeholder', 'název, kód');

        // Filter definition
        $table->addFilter('q', function($source, $value) { $source->where('name LIKE :q OR code LIKE :q', ['q' => $value.'%']);});


        $table->onBind[] = function($binder, $i) use ($table) {
            if ($table->isTreeView()) {
                if (strlen($binder->path) !== 4) {
                    $table['body'][$i][1]->setAttribute('style', 'padding-left: ' . (5 * strlen($binder->path)) . 'px;');
                }
                if (strlen($binder->path) !== 12) {
                    $table['body'][$i][1]->setHtml('<i class="fa fa-minus-square-o"></i> ' . $table['body'][$i][1]->getHtml());
                } else {
                    $table['body'][$i][1]->setHtml('&nbsp; ' . $table['body'][$i][1]->getHtml());
                }
            }
        };

        return;
    }

    public function actionCategoriesDetail(DB\Category $object = null)
    {
        $cache = $this->cache;

        $this->setTemplateFile('default-form.latte');

        $this->admin->title = $object ? 'Kategorie: '. $object->name : 'Nová kategorie';
        $this->admin->icon = 'fa fa-cogs';

        $form = $this->createForm('form');

        $form->addGroup('Kategorie');
	    $form->addLangSelector();

        $form->setUserPath('userfiles');

        $roots = $this->stm->getRepository(DB\Category::class)->getRootItems()->orderBy(['priority']);
        if ($object) {
            $roots->where('path != :path', ['path' => $object->path]);
        }
        $tree = [];
        foreach($roots as $key => $category) {
            $tree[$key] = $category->name;
            foreach($category->getDirectChilds()->orderBy(['priority']) as $key2 => $child) {
               $tree[$key2] = '--' . $child->name;
            }
        }


        $form->addSelect('parent', 'Nadřazená položka', ['' => '-žádná-'] + $tree);

        $form->addLocaleText('name', 'Název');

        $form->addLocaleText('fullname', 'Dlouhý název (pro SEO)');

        $form->addLocaleTextArea('perex', 'Perex');
        $form->addLocaleRichEdit('text', 'Text');
	    $upload = $form->addUploadImage('image', 'Obrázek kategorie', [
		    'categories' => function(Image $image) {
			    $image->resize(300, 250, Image::FIT);
	    	},
	    ]);
	    $upload->onDelete[] = function() use ($object, $form, $cache) {
	    	$object->image = NULL;
	    	$object->update();
	    	$cache->clean([ Cache::TAGS => ["categories"],]);
	        $form->getPresenter()->redirect('this');
	    };
	    $upload->setInfoText('<i>Nahraný obrázek bude zmenšen na šířku 300px a výšku 250px.</i>');

	    $form->addText('priority', 'Pořadí')->setDefaultValue(0)->setRequired(true);
        //$form->addCheckbox('recommended', 'Doporučená');
        $form->addCheckbox('hidden', 'Skrytá');
        $form->addSubmit('submit', 'Uložit');


//        $form->addGroup('Stránka:');
//        $form->addPageContainer('category_detail', ['category' => $object], $object);

        if ($object) {
	        $form->setDefaults($object->jsonSerialize());

            if ($parent = $object->getDirectParent()) {
                $form['parent']->setDefaultValue($parent->getPK());
            }
        }

        $stm = $this->stm;
        $form->onSuccess[] = function($form, $values) use ($object, $cache, $stm) {

	        $new = !$object;
	        $object = $object ?? $stm->getRepository(DB\Category::class)->create();

            $object->loadFromArray((array) $values);
	        $object->image = $form['image']->isUpload() ? $form['image']->upload($object->uuid . '.%2$s') : $object->image;
            $parent = $values['parent'] ? $stm->getRepository(DB\Category::class)->one($values['parent']) : null;

            if ($new) {
                $object->path = ($parent ? $parent->path : '') . Random::generate(4, 'A-Z0-9');
            }

            if ($new) $stm->getRepository(DB\Category::class)->add($object); else $object->update();

            $form->onEdit($form, $values, $object, $cache);

            $cache->clean([ Cache::TAGS => ["categories"],]);

            $form->getPresenter()->flashMessage('Uloženo', 'success');
            $form->getPresenter()->redirect(':Catalog:Admin:Catalog:categories');
        };

        return;
    }

    public function actionProducers()
    {
        $this->admin->title = 'Výrobci';
        $this->admin->icon = 'fa fa-cubes';

	    $this->template->buttons = '<a class="btn btn-success btn-sm" href="'.$this->link('producersDetail').'"><i class="fa fa-plus"></i> Přidat výrobce</a>';


	    $source = new CMS\Source\Collection($this->stm->getRepository(DB\Producer::class)->many());

        $table = new DataGrid($this, 'table', $source);
        $table->addColumnSelector(['class' => 'minimal']);
        $table->addColumn('Název', 'name')->addValue('<a target="_blank" href="%s">%s</a>',
            [':Eshop:Catalog:producer', ['producer' => '%s']],
            'name'
        );
        $table->addColumnMin('Pořadí','priority')->addText('priority')->setAttribute('style','width: 40px;');
        $table->addColumnMin('Skrytý','hidden')->addCheckbox('hidden');
        $table->addColumnActionEdit('producersDetail');
        $table->addColumnActionDelete();

        $table['filter']->addText('q')->setAttribute('placeholder', 'název');

        $table->addFilter('q', function($source, $value) { $source->where('name LIKE :q', ['q' => $value.'%']);});

        return;
    }

    public function actionProducersDetail(DB\Producer $object = null)
    {
        $this->setTemplateFile('default-form.latte');

	    $cache = $this->cache;

        $this->admin->title = $object ? 'Výrobce: '. $object->name : 'Nový výrobce';
        $this->admin->icon = 'fa fa-cogs';


        $form = $this->createForm('form');


	    $form->setUserPath('userfiles');


	    $form->addGroup('Výrobce:');
	    $form->addLangSelector();
	    $form->addLocaleText('name', 'Název');
	    $upload = $form->addUploadImage('image', 'Obrázek v seznamu výrobců', [
		    'producers' => function(Image $image) { $image->resize(220, NULL);},
	    ]);
	    $upload->onDelete[] = function() use ($object, $form, $cache) { $object->image = NULL; $object->update(); $cache->clean([ Cache::TAGS => ["categories"],]); $form->getPresenter()->redirect('this'); };
	    $upload->setInfoText('<i>Nahraný obrázek bude zmenšen na šířku 220px. Výška bude dopočítána.</i>');

	    //$form->addLocaleText('fullname', 'Dlouhý název (pro SEO)');
	    $form->addLocaleTextArea('perex', 'Perex');
	    $form->addLocaleRichEdit('text', 'Text');
        $form->addText('priority', 'Pořadí')->setDefaultValue(0)->setRequired(TRUE)->addRule($form::INTEGER);
        $form->addCheckbox('hidden', 'Skrytý');


        $form->addGroup('Stránka:');

        $form->addPageContainer('eshop_producer', ['producer' => $object], $object);

        $form->addSubmit('submit', 'Uložit');

        if ($object) {
	        $form->setDefaults($object->jsonSerialize());
        }
	    $stm = $this->stm;
        $form->onSuccess[] = function($form, $values) use ($object, $cache, $stm) {

	        $new = !$object;
	        $object = $object ?? $stm->getRepository(DB\Producer::class)->create();
            $object->loadFromArray((array)$values);
	        $object->image = $form['image']->isUpload() ? $form['image']->upload($object->uuid . '.%2$s') : $object->image;

	        if ($new) $stm->getRepository(DB\Producer::class)->add($object); else $object->update();

	        $form->onEdit($form, $values, $object, $cache);

            $form->getPresenter()->flashMessage('Uloženo', 'success');
            $form->getPresenter()->redirect('this');
        };

        return;
    }


	/**
	* @title Produkty
	*/
	public function actionProducts()
	{
		$this->admin->title = 'Produkty';
        $this->admin->icon = 'fa fa-cubes';

		$this->template->buttons = '<a class="btn btn-success btn-sm" href="'.$this->link('productsDetail').'"><i class="fa fa-plus"></i> Přidat produkt</a>';

        $source = new CMS\Source\Collection($this->stm->getRepository(DB\Product::class)->many()->groupBy(['uuid']));

        // Datagrid
		$table = new DataGrid($this, 'table', $source);
		$table->addColumnSelector(['class' => 'minimal']);
//        $table->addColumn('Kód', 'code')->addValue('%s','code');
		$table->addColumn('Název', 'name')->addValue('<a target="_blank" href="%s">%s</a>',
			[':Catalog:Product:detail', ['product' => '%s']],
			'name'
		);
		$table->addColumn('Kód', 'code')->addValue('%s','code');
        $table->addColumn('Kategorie')->addValue('%s', 'categories|format:name' );
        $table->addColumnMin('Novin.', 'news')->addCheckbox('news');
        $table->addColumnMin('Skrytý.', 'hidden')->addCheckbox('hidden');
		$table->addColumnMin()->addValue('<a class="btn btn-xs btn-success" href="%s"><i class="fa fa-photo"></i> Fotografie</a>', ['productsPhotos', ['object' => '%s']]);
		$table->addColumnMin()->addValue('<a class="btn btn-xs btn-success" href="%s"><i class="fa fa-files-o"></i> Soubory</a>', ['productsFiles', ['object' => '%s']]);
//        $table->addColumnMin()->addValue('<a class="btn btn-xs btn-success" href="%s"><i class="fa fa-bars"></i> Filtry</a>', ['productsFilters', ['object' => '%s']]);
//        $table->addColumnMin()->addValue('<a class="btn btn-xs btn-success" href="%s"><i class="fa fa-pencil"></i> Varianty</a>', ['productsVariants', ['object' => '%s']]);
//        $table->addColumnMin()->addValue('<a  class="btn btn-xs btn-info" target="_blank" href="%s"> <i class="fa fa-refresh"></i> Obrázek</a>', ['downloadImage!', ['product' => '%s']]);

        $table->addColumnActionEdit('productsDetail');
        $table->addColumnActionDelete();

        // Filter form
        $table['filter']->addText('q')->setAttribute('style','width:300px; margin-right: 10px;')->setAttribute('placeholder', 'název, kód, kategorie');
//        $table['filter']->addText('category_code')->setAttribute('style','width:100px; margin-right: 10px;')->setAttribute('placeholder', 'kód kategorie');

        // Filter definition
        $table->addFilter('q', function($source, $value) { $source->where('catalog_product.name LIKE :q OR catalog_product.code LIKE :q OR categories.name LIKE :q', ['q' => $value.'%']);});
//        $table->addFilter('category_code', function($source, $value) { $source->where('eshop_product.category_code = :q', ['q' => $value]);});


        return;
	}

    public function actionProductsDetail(DB\Product $object = null)
    {
        $this->setTemplateFile('default-form.latte');

        $this->admin->title = $object ? 'Produkt: '. $object->name : 'Nový produkt';
        $this->admin->icon = 'fa fa-cogs';

        $form = $this->createForm('form');
        $form->addGroup('Produkt');
        $form->addLangSelector();
        $form->addLocaleText('name', 'Název');
        $form->addLocaleRichEdit('perex', 'Perex');
        $form->addLocaleRichEdit('text', 'Text');
	    $form->addText('ean', 'EAN');
	    $form->addText('weight', 'Hmotnost');
	    $form->addText('in_package', 'Balení');
	    $form->addCheckboxList('categories', 'Kategorie', $this->stm->getRepository(DB\Category::class)->many()->toArray('name'));
	    $form->addCheckbox('news', 'Novinka');
        $form->addCheckbox('hidden', 'Skrytý');

        $form->addSubmit('submit', 'Uložit');

//	    $form->addGroup('Stránka:');
//        $form->addPageContainer('product_detail', ['product' => $object], $object);

	    if ($object) {
		    $form->setDefaults($object->jsonSerialize());
	    }
        $cache = $this->cache;
	    $stm = $this->stm;
        $form->onSuccess[] = function ($form, $values) use ($object, $cache, $stm) {
	        $new = !$object;
	        $object = $object ?? $stm->getRepository(DB\Product::class)->create();
            $object->loadFromArray((array)$values);

	        if ($new) {
		        $stm->getRepository(DB\Product::class)->add($object);
	        }

	        $object->categories->removeAll();
	        $object->categories->addByPK($values['categories']);
            $object->update();

            $form->onEdit($form, $values, $object, $cache);

            $form->getPresenter()->flashMessage('Uloženo', 'success');
            $form->getPresenter()->redirect('this');
        };

        return;
    }

    public function actionProductsFilters(DB\Product $object)
    {

        $this->admin->title = 'Filtry: '. $object->name;
        $this->admin->icon = 'fa fa-cogs';

        $form = new Form($this,'form');
        $parameters = $form->addContainer('parameters');
        foreach ($this->stm->getRepository(DB\ParameterGroup::class)->many() as $index => $item) {
            $checkboxlist = $parameters->addCheckboxList($index, $item->name, $item->parameters->toArray('name'));
            $p = clone $object->parameters;
            $checkboxlist->setDefaultValue($p->where('fk_group', $index)->toArray('uuid'));
        }

        $form->addSubmit('submit', 'Uložit');

        $form->setDefaults($object->jsonSerialize());
        $form->onSuccess[] = function($form, $values) use ($object) {
            $ids = [];
            foreach($values['parameters'] as $value) {
                $ids = array_merge($ids, $value);
            }

            $object->parameters->removeAll(); // zapouzdrit to load from array
            $object->parameters->addByPK($ids); // zapouzdrit to load from array

            $form->getPresenter()->flashMessage('Uloženo', 'success');
            $form->getPresenter()->redirect('this');
        };

        return;
    }

	public function actionProductsFiles(DB\Product $object)
	{
		$this->admin->title = 'Soubory: '. $object->name;
		$this->admin->icon = 'fa fa-cogs';
		$userUrl =  $this->template->userUrl;

		$source = new CMS\Source\Collection($object->getFiles());

		$table = new DataGrid($this, 'table', $source);
		$table->setDefaultOrderBy(['priority' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('', 'name', ['class' => 'minimal'])->addValue('%s','file');
		$table->addColumn('Popisek')->addValue('%s','description');
		$table->addColumnMin('Pořadí','priority')->addText('priority')->setDefaultValue(0)->setAttribute('style','width: 40px;');
		$table->addColumnMin('Skrytý','hidden')->addCheckbox('hidden');
		$table->addColumnActionDelete();

		$form = new Form($this,'form');
		$form->setUserPath('userfiles');
		$form->addGroup('Nový soubor');
		$upload = $form->addUpload('file', 'Soubor');

		$form->addText('description', 'Popisek');
		$form->addText('priority', 'Pořadí')->setDefaultValue(0);
		$form->addCheckbox('hidden', 'Skrytý');
		$form->addSubmit('submit', 'Vložit');
		$stm = $this->stm;
		$form->onSuccess[] = function($form, $values) use ($object, $stm) {
			$object = $stm->getRepository(DB\ProductFile::class)->create(['fk_product' => (string) $object]);
			$object->loadFromArray((array) $values);
			$object->file = '';
			$stm->getRepository(DB\ProductFile::class)->add($object);


			if ($values['file']->isOk()) {
				$dir = $form->getPresenter()->context->parameters['userDir'];
				$values['file']->move($dir . '/files/' . $values['file']->getName());
				$object->file = $values['file']->getName();

			}
			$object->update();
			$form->getPresenter()->flashMessage('Uloženo', 'success');
			$form->getPresenter()->redirect('this');
		};

	}

    public function actionProductsPhotos(DB\Product $object)
    {
        $this->admin->title = 'Fotografie: '. $object->name;
        $this->admin->icon = 'fa fa-cogs';
        $userUrl =  $this->template->userUrl;

        $source = new CMS\Source\Collection($object->getPhotos());

        $table = new DataGrid($this, 'table', $source);
        $table->setDefaultOrderBy(['priority' => 'ASC']);
        $table->addColumnSelector(['class' => 'minimal']);
        $table->addColumn('', 'name', ['class' => 'minimal'])->addValue('<img src="%s" width="78">',
            function($photo, $id, $p) use ($userUrl) { return $userUrl . '/products_gallery/thumb/' . $photo->image;}
        );
        $table->addColumn('Popisek')->addValue('%s','description');
        $table->addColumnMin('Pořadí','priority')->addText('priority')->setDefaultValue(0)->setAttribute('style','width: 40px;');
        $table->addColumnMin('Skrytý','hidden')->addCheckbox('hidden');
        $table->addColumnActionDelete();

        $form = new Form($this,'form');
        $form->setUserPath('userfiles');
        $form->addGroup('Nová fotografie');
        $upload = $form->addUploadImage('image', 'Obrázek', [
            'products_gallery/thumb' => function(Image $image) {
        	    $image->resize(400, 257, Image::EXACT);
        	},
            'products_gallery/detail' => function(Image $image) { $image->resize(800, NULL);
            },
            'products_gallery/origin' => function(Image $image) { $image->resize(1024, NULL);},
        ]);

        $form->addText('description', 'Popisek');
        $form->addText('priority', 'Pořadí');
        $form->addCheckbox('hidden', 'Skrytý');
        $form->addSubmit('submit', 'Vložit');
        $stm = $this->stm;
        $form->onSuccess[] = function($form, $values) use ($object, $stm) {
            $object = $stm->getRepository(DB\ProductPhoto::class)->create(['fk_product' => (string) $object]);
            $object->loadFromArray((array) $values);
            $object->image = '';
            $stm->getRepository(DB\ProductPhoto::class)->add($object);

            $object->image = $form['image']->isUpload() ? $form['image']->upload($object->uuid . '.%2$s') : $object->image;
            $object->update();
            $form->getPresenter()->flashMessage('Uloženo', 'success');
            $form->getPresenter()->redirect('this');
        };

    }

	public function actionProductsVideos(DB\Product $object)
	{
		$this->admin->title = 'Přiložená videa: '. $object->name;
		$this->admin->icon = 'fa fa-cogs';
		$userUrl =  $this->template->userUrl;

		$source = new CMS\Source\Collection($this->stm->getRepository(DB\ProductVideo::class)->many());

		$table = new DataGrid($this, 'table', $source);
		$table->setDefaultOrderBy(['priority' => 'ASC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('', 'name', ['class' => 'minimal'])->addValue('<img src="%s" width="78">',
			function($photo, $id, $p) use ($userUrl) { return ($photo->image) ? $userUrl . '/products_videos/' . $photo->image : '';}
		);
		$table->addColumn('Odkaz')->addValue('%s','link');
		$table->addColumn('Popisek')->addValue('%s','description');
		$table->addColumnMin('Pořadí','priority')->addText('priority')->setAttribute('style','width: 40px;');
		$table->addColumnMin('Skrytý','hidden')->addCheckbox('hidden');
		$table->addColumnActionDelete();

		$form = new Form($this,'form');
		$form->setUserPath('userfiles');
		$form->addGroup('Nové video');
		$upload = $form->addUploadImage('image', 'Náhledový obrázek', [
			'products_videos' => function(Image $image) { $image->resize(78, 78);},
		]);

		$upload->setInfoText('<i>Pokud necháte obrázek prázdný, bude použit obrázek produktu.<br> Nahraný obrázek bude zmenšen na rozměry 78px x 78x. </i>');
		$form->addText('link', 'Odkaz')->setRequired(true)->setAttribute('placeholder', 'https://www.youtube.com/embed/kod_videa');;
		$form->addText('description', 'Popisek');
		$form->addText('priority', 'Pořadí')->setDefaultValue(0);
		$form->addCheckbox('hidden', 'Skrytý');
		$form->addSubmit('submit', 'Vložit');
		$stm = $this->stm;
		$form->onSuccess[] = function($form, $values) use ($object, $stm) {
			$object = $stm->getRepository(DB\ProductVideo::class)->create(['fk_product' => (string) $object]);
			$object->loadFromArray((array) $values);
			$object->image = '';
			$stm->getRepository(DB\ProductVideo::class)->add($object);

			$object->image = $form['image']->isUpload() ? $form['image']->upload($object->uuid . '.%2$s') : $object->image;
			$object->update();
			$form->getPresenter()->flashMessage('Uloženo', 'success');
			$form->getPresenter()->redirect('this');
		};

	}

}

