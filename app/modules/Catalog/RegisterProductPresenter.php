<?php

namespace App\Catalog;

use App\Catalog\DB\Product;
use App\Catalog\DB\ProductRepository;
use App\Catalog\DB\RegisterProduct;
use App\Catalog\DB\RegisterProductRepository;
use Lqd\CMS\Form\Form;
use Nette\Application\UI\Presenter;

/**
 * Product Presenter
 */
class RegisterProductPresenter extends Presenter
{
	use \App\PresenterTrait;

	/** @var RegisterProductRepository */
	public $registerProductRepository;

	protected function startup()
	{
		parent::startup();

		$this->registerProductRepository = $this->stm->getRepository(RegisterProduct::class);
	}

	public function actionDetail(\App\Catalog\DB\Product $product): void
	{
		$this['breadcrumb']->addLevel('adas', $this->link('this'));
	}

	public function renderDetail(): void
	{
	}

	public function createComponentRegisterProductForm()
	{
		$form = new Form();

		$form->addText('fullName', $this->translator->getTranslator()->translate('registerProduct.FullName'))
			->setRequired();
		$form->addText('company', $this->translator->getTranslator()->translate('registerProduct.Company'))
			->setRequired();
		$form->addText('specialization', $this->translator->getTranslator()->translate('registerProduct.Specialization'))
			->setRequired();
		$form->addText('seller', $this->translator->getTranslator()->translate('registerProduct.Seller'))
			->setRequired();
		$form->addText('serialNumber', $this->translator->getTranslator()->translate('registerProduct.SerialNumber'))
			->setRequired();
		$form->addEmail('email', $this->translator->getTranslator()->translate('registerProduct.Email'))
			->setRequired();
		$form->addSelect('product', $this->translator->getTranslator()->translate('registerProduct.Product'))
			->checkDefaultValue(false);
		$form->addText('orderTs', $this->translator->getTranslator()->translate('registerProduct.OrderTs'))
			->setHtmlType('date')
			->setRequired();
		$form->addSubmit('submit', $this->translator->getTranslator()->translate('registerProduct.Send'));

		$form->onSuccess[] = function (Form $form) {
			if (!$form->isValid()) {
				return;
			}

			$product = $this->getHttpRequest()->getPost('product');

			if (!$product) {
				$form['product']->addError($this->translator->getTranslator()->translate('registerProduct.Required'));
			}
		};

		$form->onSuccess[] = function (Form $form) {
			$values = $form->getValues(true);

			/** @var RegisterProduct $product */
			$product = $this->registerProductRepository->create(['fk_product' => (string) $this->getHttpRequest()->getPost('product')]);
			$product->loadFromArray($values);

			$this->registerProductRepository->add($product);

			$this->flashMessage($this->translator->getTranslator()->translate('registerProduct.Sent'), 'success');
			$this->redirect('this');
		};

		return $form;
	}

	public function handleSearchProducts($q = null, $offset = null): void
	{
		if (!$q) {
			$this->payload->result = [];
			$this->sendPayload();
		}

		/** @var ProductRepository $productRepository */
		$productRepository = $this->stm->getRepository(Product::class);

		$products = $productRepository->many()->where('deleted', false)->where('hidden', false)->filter(['search' => $q])
			->page(isset($offset) ? (int)$offset : 1, 5);

		$payload = [];

		foreach ($products as $product) {
			$payload[] = [
				'id' => $product->getPK(),
				'text' => $product->code . ' - ' . $product->name,
			];
		}

		$this->payload->results = $payload;
		$this->payload->pagination = ['more' => \count($products) === 5];

		$this->sendPayload();
	}
}
