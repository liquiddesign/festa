<?php
namespace App\Catalog\Control;
  
use Lqd\Modules\ControlTrait;
use Lqd\Translator\Translator;
use Nette\Application\UI\Control;
use App\Catalog\DB;
use Lqd\CMS;
use Tools\Filters;

class Products extends Control
{
	use ControlTrait;

	/**
	 * @var DB\ProductRepository
	 */
	public $products;
	
	private $filter;

	private $category;

    protected $wrappers = [
	    'container' => '',
	    'link' => 'a class="btn btn-tab"',
	    'item' =>'',
	    '.active' => 'active',
	    'separator' => 'span class="paging__dash"',
	    'separator_text' => '',
	    'prev' => '',
	    'prev_text' => '',
	    'next' => '',
	    'next_text' => '',
    ];

	
	/**
	 * @param DB\IProductRepository
	 */
	public function __construct(array $filter = [], DB\ProductRepository $products, DB\CategoryRepository $category)
	{
		$this->filter = $filter;
		$this->products = $products;                                
		$onPage = 12;
		$this->category = $filter['category'] ?? NULL;

        $many = $products->getProducts();

//        if (isset($filter['q'])) {
//        	$many->where('name LIKE :q', ['q' => '%'.$filter['q'].'%']);
//        }
//        if (isset($filter['category'])) {
//        	$category = $category->one($filter['category']);
//        	$many = $category->products;
//        }
//
//        if (isset($filter['news'])) {
//        	$many = $products->getNewProducts();
//        }

        $many->filter($filter);

        $many->groupBy(['uuid']);
        //$many->orderBy(['priority' => 'ASC', 'name' => 'ASC'], TRUE);

		$list = new CMS\Table\DataList(new \Lqd\CMS\Source\Collection($many), $onPage);

		$this->addComponent($list, 'list');
	
		$wrappers = $this->wrappers;
		$list['paging']->setRenderer(new CMS\Paging\Renderer\Custom($wrappers));
	}
	
	
	
	public function setLinkCallback(\Closure $link)
	{
	
	}

	public function renderPaging($sorting = FALSE)
	{
		if (isset($this->presenter->translator)) {
			$this->template->setTranslator($this->presenter->translator->getTranslator());
		}

		$this->setTemplateFile('paging');

		$template = $this->template;
		$template->userUrl = $this->presenter->template->userUrl;
		$template->renderSorting = $sorting;
		$template->products = $this['list']->getData();
		$template->onpage = $this['list']['paging']->paginator->getItemsPerPage();
		
		$template->render();
	}

	public function getIDs()
    {
        return array_map(function($val) {return "'".$val."'";}, array_keys($this['list']->getData()->getSource()->toArray()));
    }

    protected function setTemplateVars()
    {
        $template = $this->template;
        if (!isset($template->products)) {
	        $template->products = $this['list']->getData();
        }
        $template->userUrl = $this->presenter->template->userUrl;
        $template->pubUrl = $this->presenter->template->pubUrl;
        $template->empty =  $this['list']['paging']->paginator->getItemCount() == 0;
    }

	public function render($cols)
	{
		if (isset($this->presenter->translator)) {
			$this->template->setTranslator($this->presenter->translator->getTranslator());
		}
		$this->template->cols = $cols;
		$this->template->ts = $this->presenter->context->parameters['ts'];
	    $this->setTemplateVars();
		$this->setTemplateFile()->render();
	}

}
