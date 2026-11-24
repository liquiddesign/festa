<?php

namespace App\Web;

use App\Web\DB\CookieConsent;
use Nette;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Nette\Utils\Strings;

/**
 * Evidence souhlasů s cookies – volá ji public/js/cookieConsent.js při první volbě a při každé změně.
 */
final class CookieConsentPresenter extends Nette\Application\UI\Presenter
{
	const CATEGORIES = ['necessary', 'analytics', 'marketing', 'ad_user_data', 'ad_personalization'];

	const ACTIONS = ['first', 'change'];

	/**
	 * @inject
	 * @var \Storm\Connection
	 */
	public $stm;

	public function actionDefault(): void
	{
		$httpRequest = $this->getHttpRequest();

		if (!$httpRequest->isMethod('POST')) {
			$this->error('Method not allowed', Nette\Http\IResponse::S405_METHOD_NOT_ALLOWED);
		}

		try {
			$data = Json::decode((string) $httpRequest->getRawBody(), Json::FORCE_ARRAY);
		} catch (JsonException $e) {
			$data = null;
		}

		$consentId = \is_array($data) && isset($data['consentId']) ? $data['consentId'] : null;
		$categories = \is_array($data) && isset($data['categories']) ? $data['categories'] : null;
		$action = \is_array($data) && isset($data['action']) ? $data['action'] : null;

		if (!\is_string($consentId) || !\preg_match('/^[0-9a-f]{8}(-[0-9a-f]{4}){3}-[0-9a-f]{12}$/i', $consentId)
			|| !\is_array($categories) || !\in_array($action, self::ACTIONS, true)) {
			$this->error('Invalid consent data', Nette\Http\IResponse::S400_BAD_REQUEST);
		}

		// necessary je vždy povolené; ostatní bereme jen ze známého seznamu a ve stálém pořadí
		$allowed = [];

		foreach (self::CATEGORIES as $category) {
			if ($category === 'necessary' || \in_array($category, $categories, true)) {
				$allowed[] = $category;
			}
		}

		$repository = $this->stm->getRepository(CookieConsent::class);
		$consent = $repository->create();
		$consent->consentId = Strings::lower($consentId);
		$consent->categories = \implode(',', $allowed);
		$consent->revision = (int) ($data['revision'] ?? 0);
		$consent->action = $action;
		$repository->add($consent);

		$this->sendJson(['status' => 'ok']);
	}
}
