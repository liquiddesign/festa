<?php

namespace App\Catalog;

use App\Catalog\DB\Category;
use App\Catalog\DB\Product;
use Nette\Application\UI\Presenter;
use Nette\Utils\FileSystem;
use Nette\Utils\UnknownImageFileException;
use Tracy\Debugger;

/**
 * Product Presenter
 */
class ImportPresenter extends Presenter
{
    use \App\PresenterTrait;

    public function getLeviorDb()
    {
	    $dsn = $this->context->parameters['levior']['dsn'];
	    $username = $this->context->parameters['levior']['dbuser'];
	    $password = $this->context->parameters['levior']['dbpass'];
	    $options = [
	    	\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8"
	    ];

	    return $pdo = new \PDO($dsn, $username, $password, $options);
    }

    public function importCategories()
    {
	    $sql = 'SELECT * FROM eshop_category';
	    $statement = $this->getLeviorDb()->prepare($sql);
	    $statement->execute();
	    $categories = $statement->fetchAll(2);

	    foreach ($categories as $leviorCategory) {
			$importedCategory = [
				'uuid' => $leviorCategory['uuid'],
				'name' => $leviorCategory['name_cs'],
				'name_en' => $leviorCategory['name_en'] ?: $leviorCategory['name_cs'],
				'path' => $leviorCategory['path'],
				'perex' => $leviorCategory['perex_cs'],
				'perex_en' => $leviorCategory['perex_en'],
				'text' => $leviorCategory['content_cs'],
				'text_en' => $leviorCategory['content_en'],
				'hidden' => $leviorCategory['hidden'],
				'priority' => $leviorCategory['priority'],
				'code' => $leviorCategory['code'],
			];

		    $this->stm->getRepository(DB\Category::class)->sync(new Category($importedCategory));
	    }
		
		Debugger::log('categories import completed');
    	dump('categories import completed');
    }

    public function importProducts()
    {
	    $sql = 'SELECT p.*, con.content_cs, con.content_en, vis.hidden, vis.priority FROM eshop_product AS p RIGHT JOIN eshop_productcontent AS con ON con.fk_product = p.uuid RIGHT JOIN eshop_visibilitylistitem AS vis ON vis.fk_product = p.uuid WHERE p.subCode = 1 AND fk_producer LIKE "festa%"';
	    $statement = $this->getLeviorDb()->prepare($sql);
	    $statement->execute();
	    $products = $statement->fetchAll(2);
		
		$sql = 'SELECT * FROM eshop_product_nxn_eshop_ribbon WHERE fk_ribbon = "new"';
		$statement = $this->getLeviorDb()->prepare($sql);
		$statement->execute();
		$ribbons = $statement->fetchAll(2);
		$productNews = [];
		
		foreach ($ribbons as $ribbon) {
			$productNews[$ribbon['fk_product']] = 1;
		}
		
	    $this->stm->getLink()->beginTransaction();
    
        $this->stm->getRepository(DB\Product::class)->setAllDeleted();
		$this->stm->getRepository(\App\Catalog\DB\NxN\ProductCategory::class)->setAllDeleted();

	    foreach ($products as $leviorProduct) {
	    	$importedProduct = [
	    		'uuid' => $leviorProduct['uuid'],
	    		'name' => $leviorProduct['name_cs'],
				'name_en' => $leviorProduct['name_en'] ?:  $leviorProduct['name_cs'],
				'text' => $leviorProduct['content_cs'],
				'text_en' => $leviorProduct['content_en'],
				'ean' => $leviorProduct['ean'],
				'weight' => $leviorProduct['weight'],
				'in_package' => $leviorProduct['inPackage'],
				'news' => (int) isset($productNews[$leviorProduct['uuid']]),
				'news_year' => null,
				'news_order' => $leviorProduct['newPriority'],
				'hidden' => $leviorProduct['hidden'],
				'priority' => $leviorProduct['priority'],
				'code' => $leviorProduct['code'],
				'sub_code' => $leviorProduct['subCode'],
				//'category_code' => $leviorProduct['category_code'],
				'mu' => $leviorProduct['unit'],
				'mu2' => $leviorProduct['innerUnit'],
				'in_carton' => $leviorProduct['inCarton'] ?: 0,
				'on_palette' => $leviorProduct['inPalett'] ?: 0,
				'deleted' => 0,
				'image' => $leviorProduct['imageFileName'],
				'showDeclarationConformity' => $leviorProduct['showDeclarationConformity'],
			];
		    $this->stm->getRepository(DB\Product::class)->sync(new Product($importedProduct));

		    $sql = 'SELECT * FROM eshop_product_nxn_eshop_category WHERE fk_product = "'. $leviorProduct['uuid'] . '"';
		    $statement = $this->getLeviorDb()->prepare($sql);
		    $statement->execute();
		    $categories = $statement->fetchAll(2);
			
			foreach ($categories as $productCategory) {
				$productCategoryImported = [
					'fk_category' => $productCategory['fk_category'],
					'fk_product' => $productCategory['fk_product'],
					'deleted' => 0,
				];
			    $this->stm->getRepository(\App\Catalog\DB\NxN\ProductCategory::class)->sync(new \App\Catalog\DB\NxN\ProductCategory($productCategoryImported));
		    }
	    }

	    $this->stm->getLink()->commit();

	    $this->cleanCategories();

		dump('products import completed');
		Debugger::log('products import completed');
    }

	/**
	 * Hide categories without products
	 *
	 * @throws \Storm\Exception\InvalidState
	 */
	public function cleanCategories()
    {
	    $categories = $this->stm->getRepository(DB\Category::class)->many();
	    foreach ($categories as $category) {
	    	if ($category->getProductCount() == 0) {
	    		$category->update(['hidden' => 1]);
		    }
	    }
	    
	    //promazani vazeb product--kategorie, ktere uz neexistuji
	    $this->stm->getRepository(\App\Catalog\DB\NxN\ProductCategory::class)->many()->where('deleted', true)->delete();
    }

    public function importProductsFiles()
    {
		// prohlášení o shodě aktuální
		$sourcePath = $this->context->parameters['levior']['url'] . '/userfiles/product_files/prohlaseni-o-shode-aktualni.pdf';
		$targetPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . 'prohlaseni-o-shode-aktualni.pdf';
		file_put_contents($targetPath, file_get_contents($sourcePath));
		
		$sql = 'SELECT * FROM eshop_file';
		$statement = $this->getLeviorDb()->prepare($sql);
		$statement->execute();
		$productFiles = $statement->fetchAll(2);
		
		foreach ($productFiles as $productFile) {
			$importedProductFile = [
				'uuid' => $productFile['uuid'],
				'fk_product' => $productFile['fk_product'],
				'file' => $productFile['fileName'],
				'description' => $productFile['label_cs'],
				'priority' => $productFile['priority'],
				'hidden' => $productFile['hidden'],
			];
			
			try {
				$this->stm->getRepository(\App\Catalog\DB\ProductFile::class)->sync(new \App\Catalog\DB\ProductFile($importedProductFile));
				
				$sourcePath = $this->context->parameters['levior']['url'] . '/userfiles/product_files/' . $importedProductFile['file'];
				$targetPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR . 'files' . DIRECTORY_SEPARATOR . $importedProductFile['file'];
				file_put_contents($targetPath, file_get_contents($sourcePath));
			} catch (\PDOException $e) {
				//
			}
		}

	    dump('products files import completed');
		Debugger::log('products files import completed');
    }

    public function importProductsImages()
    {
		/* product gallery photos */
		$sql = 'SELECT * FROM eshop_photo';
		$statement = $this->getLeviorDb()->prepare($sql);
		$statement->execute();
		$productPhotos = $statement->fetchAll(2);
		
		$productPhotosClear = [];
		
		foreach ($productPhotos as $productPhoto) {
			$productPhotosClear[$productPhoto['fk_product']][] = $productPhoto;
		}
		
	    foreach ($this->stm->getRepository(DB\Product::class)->many() as $product) {
	    	/* main product photo */
		    if (!empty($product->image)) {
			    $sourcePath = $this->context->parameters['levior']['url'] . '/userfiles/product_gallery_images/origin/' . $product->image;
			    $targetPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . 'origin' . DIRECTORY_SEPARATOR . $product->image;
			    file_put_contents($targetPath, file_get_contents($sourcePath));
		    }
			
			$i = 0;
			
		    foreach ($productPhotosClear[$product->getPK()] as $productPhoto) {
				if ($i === 0) {
					$i++;
					
					continue;
				}
				
				$importedProductPhoto = [
					'uuid' => $productPhoto['uuid'],
					'fk_product' => $productPhoto['fk_product'],
					'image' => $productPhoto['fileName'],
					'priority' => $productPhoto['priority'],
					'hidden' => $productPhoto['hidden'],
				];
				
				$this->stm->getRepository(\App\Catalog\DB\ProductPhoto::class)->sync(new \App\Catalog\DB\ProductPhoto($importedProductPhoto));
			    $sourcePath = $this->context->parameters['levior']['url'] . '/userfiles/product_gallery_images/origin/' . $importedProductPhoto['image'];
			    $targetPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR . 'products_gallery' . DIRECTORY_SEPARATOR . 'origin' . DIRECTORY_SEPARATOR. $importedProductPhoto['image'];
			    file_put_contents($targetPath, file_get_contents($sourcePath));
				
				$i++;
		    }
	    }

	    dump('products photos import completed');
    }

    public function actionProductsImagesResize()
    {
		Debugger::log('products photos resize - start');
	    $originPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR . 'products_gallery' . DIRECTORY_SEPARATOR. 'origin';
	    $detailPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR . 'products_gallery' . DIRECTORY_SEPARATOR. 'detail';
	    $thumbPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR .'products_gallery' . DIRECTORY_SEPARATOR. 'thumb';

	    foreach (\Nette\Utils\Finder::findFiles('*')->in($originPath) as $filePath => $file) {
			try {
				// detail
				$image = \Nette\Utils\Image::fromFile($filePath);
				$image->resize(600, 500)->save($detailPath . '/' . $file->getBasename());
	
				//thumb
				$image = \Nette\Utils\Image::fromFile($filePath);
				$image->resize(300, 250)->save($thumbPath . '/' . $file->getBasename());
			} catch (UnknownImageFileException $exception) {
				Debugger::log($exception->getMessage());
			}
	    }
	
		Debugger::log('products gallery photos resize completed');

	    $originPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR. 'origin';
	    $detailPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR. 'detail';
	    $thumbPath = $this->context->parameters['userDir'] . DIRECTORY_SEPARATOR .'products' . DIRECTORY_SEPARATOR. 'thumb';

	    foreach (\Nette\Utils\Finder::findFiles('*')->in($originPath) as $filePath => $file) {
	    	try {
				// detail
				$image = \Nette\Utils\Image::fromFile($filePath);
				$image->resize(600, 500)->save($detailPath . '/' . $file->getBasename());
			
				//thumb
				$image = \Nette\Utils\Image::fromFile($filePath);
				$image->resize(300, 250)->save($thumbPath . '/' . $file->getBasename());
			} catch (UnknownImageFileException $exception) {
				Debugger::log($exception->getMessage());
			}
	    }

	    Debugger::log('products photos resize - completed');
	    $this->terminate();
    }
    
    public function actionProducts()
    {
        $this->importCategories();
        $this->importProducts();
        $this->importProductsFiles();
        $this->importProductsImages();
        
        $this->terminate();
    }
}
