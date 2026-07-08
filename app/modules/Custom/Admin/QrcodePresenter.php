<?php

namespace App\Custom\Admin;

use Lqd\Admin\Control\DataGrid;
use Lqd\CMS;
use App\Custom\DB;
use Nette\Utils\DateTime;

class QrcodePresenter extends \Lqd\Admin\BasePresenter
{
	public function startup()
	{
		parent::startup();

		$this['menu']->addItem('QR kódy', 'default');
	}

	public function actionDefault()
	{
		$this->admin->title = 'QR kódy';
		$this->admin->icon = 'fas fa-qrcode';

		$this->template->buttons = '<a class="btn btn-success btn-sm" href="' . $this->link('detail') . '"><i class="fa fa-plus"></i> Přidat záznam</a>';
		$this->template->buttons .= ' <a class="btn btn-info btn-sm" href="' . $this->link('export') . '"><i class="fa fa-download"></i> Export CSV</a>';
		$this->template->buttons .= ' <a class="btn btn-warning btn-sm" href="' . $this->link('import') . '"><i class="fa fa-upload"></i> Import CSV</a>';

		$source = new CMS\Source\Collection($this->stm->getRepository(DB\QrCode::class)->many());

		$table = new DataGrid($this, 'table', $source);
		$table->setDefaultOrderBy(['createdTs' => 'DESC']);
		$table->addColumnSelector(['class' => 'minimal']);
		$table->addColumn('Zdrojová URL', 'sourceUrl')->addValue('%s', 'sourceUrl');
		$table->addColumn('Cílová URL', 'targetUrl')->addText('targetUrl');
		$table->addColumn('Vytvořeno', 'createdTs')->addValue('%s', 'createdTs');
		$table->addColumn('Změněno', 'updatedTs')->addValue('%s', 'updatedTs');
		$table->addColumnActionEdit('detail');
		$table->addColumnActionDelete();

		// Filter
		$table['filter']->addText('q', '')->setAttribute('placeholder', 'Vyhledat URL')->setAttribute('size', 40);

		$table->addFilter('q', function ($source, $value) {
			$source->where('sourceUrl LIKE :q OR targetUrl LIKE :q', ['q' => '%' . $value . '%']);
		});
	}

	public function actionDetail(DB\QrCode $object = null)
	{
		$this->setTemplateFile('default-form.latte');

		$this->admin->title = $object ? 'QR kód: ' . $object->sourceUrl : 'Nový QR kód';
		$this->admin->icon = 'fas fa-qrcode';

		$form = $this->createForm('form');
		$form->addGroup('QR kód');
		$form->addText('sourceUrl', 'Zdrojová URL')->setRequired('Zadejte zdrojovou URL')->setAttribute('placeholder', 'např. youtube/12345');
		$form->addText('targetUrl', 'Cílová URL')->setRequired('Zadejte cílovou URL')->setAttribute('placeholder', 'např. https://www.youtube.com/watch?v=xxx');

		$form->addSubmit('submit', 'Uložit');

		if ($object) {
			$form->setDefaults($object->jsonSerialize());
		}

		$stm = $this->stm;
		$form->onSuccess[] = function ($form, $values) use ($object, $stm) {
			$new = !$object;
			$object = $object ?? $stm->getRepository(DB\QrCode::class)->create();

			$object->loadFromArray((array) $values);
			$object->sourceUrl = \trim($values['sourceUrl']);

			if ($new) {
				$stm->getRepository(DB\QrCode::class)->add($object);
			} else {
				$object->updatedTs = new DateTime();
				$object->update();
			}

			$form->getPresenter()->flashMessage('Uloženo', 'success');
			$form->getPresenter()->redirect('default');
		};
	}

	public function actionExport()
	{
		$qrCodes = $this->stm->getRepository(DB\QrCode::class)->many()->orderBy(['createdTs' => 'DESC']);

		$response = new \Nette\Application\Responses\CallbackResponse(function ($httpRequest, $httpResponse) use ($qrCodes) {
			$httpResponse->setContentType('text/csv', 'utf-8');
			$httpResponse->setHeader('Content-Disposition', 'attachment; filename="qr-codes.csv"');

			$output = \fopen('php://output', 'w');
			\fwrite($output, "\xEF\xBB\xBF"); // UTF-8 BOM
			\fputcsv($output, ['sourceUrl', 'targetUrl'], ';');

			foreach ($qrCodes as $qrCode) {
				\fputcsv($output, [$qrCode->sourceUrl, $qrCode->targetUrl], ';');
			}

			\fclose($output);
		});

		$this->sendResponse($response);
	}

	public function actionImport()
	{
		$this->setTemplateFile('default-form.latte');

		$this->admin->title = 'Import QR kódů z CSV';
		$this->admin->icon = 'fas fa-qrcode';

		$form = $this->createForm('form');
		$form->addGroup('Import CSV');
		$form->addUpload('file', 'CSV soubor')->setRequired('Vyberte CSV soubor')->addRule($form::MIME_TYPE, 'Soubor musí být CSV', ['text/csv', 'text/plain', 'application/vnd.ms-excel']);
		$form->addSubmit('submit', 'Importovat');

		$stm = $this->stm;
		$form->onSuccess[] = function ($form, $values) use ($stm) {
			$file = $values['file'];

			if (!$file->isOk()) {
				$form->getPresenter()->flashMessage('Chyba při nahrávání souboru', 'danger');
				$form->getPresenter()->redirect('this');
				return;
			}

			$handle = \fopen($file->getTemporaryFile(), 'r');

			if ($handle === false) {
				$form->getPresenter()->flashMessage('Nepodařilo se otevřít soubor', 'danger');
				$form->getPresenter()->redirect('this');
				return;
			}

			// Skip BOM if present
			$bom = \fread($handle, 3);
			if ($bom !== "\xEF\xBB\xBF") {
				\rewind($handle);
			}

			$header = \fgetcsv($handle, 0, ';');
			$imported = 0;
			$updated = 0;

			$repo = $stm->getRepository(DB\QrCode::class);

			while (($row = \fgetcsv($handle, 0, ';')) !== false) {
				if (\count($row) < 2 || empty(\trim($row[0]))) {
					continue;
				}

				$sourceUrl = \trim($row[0]);
				$targetUrl = \trim($row[1]);

				$existing = $repo->many()->where('sourceUrl', $sourceUrl)->first();

				if ($existing) {
					$existing->targetUrl = $targetUrl;
					$existing->updatedTs = new DateTime();
					$existing->update();
					$updated++;
				} else {
					$qrCode = $repo->create();
					$qrCode->sourceUrl = $sourceUrl;
					$qrCode->targetUrl = $targetUrl;
					$repo->add($qrCode);
					$imported++;
				}
			}

			\fclose($handle);

			$form->getPresenter()->flashMessage("Importováno: $imported nových, $updated aktualizováno", 'success');
			$form->getPresenter()->redirect('default');
		};
	}
}
