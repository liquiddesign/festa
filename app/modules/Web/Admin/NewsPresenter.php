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
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Nette\Utils\Random;

class NewsPresenter extends \Lqd\Admin\BasePresenter
{
	/**
	 * @inject
	 * @var \Nette\Caching\IStorage
	 */
	public $cache;

	public function startup()
	{
		parent::startup();

//		$this['menu']->addItem('Aktuality', 'articles');


	}

}
