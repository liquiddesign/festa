<?php

namespace App\Web\DB;

use Storm\Model;

/**
 * Záznam o udělení / změně souhlasu s cookies (doklad souhlasu, viz docs/cookie-consent.md)
 * @table{"name":"web_cookie_consent"}
 */
class CookieConsent extends Model
{
	/**
	 * ID souhlasu z cookie cc_cookie (consent_uuid), návštěvník ho vidí v podrobném nastavení
	 * @column
	 * @var string
	 */
	public $consentId = '';

	/**
	 * Povolené kategorie oddělené čárkou
	 * @column
	 * @var string
	 */
	public $categories = '';

	/**
	 * Revize textů lišty, ke které byl souhlas udělen
	 * @column{"type":"int"}
	 * @var int
	 */
	public $revision = 0;

	/**
	 * first = první volba, change = změna volby
	 * @column
	 * @var string
	 */
	public $action = '';

	/**
	 * Vytvořen
	 * @column{"type":"datetime","default":"CURRENT_TIMESTAMP"}
	 */
	public $createdTs;
}
