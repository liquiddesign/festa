# Cookie lišta

Vlastní nasazení open-source knihovny [vanilla-cookieconsent](https://github.com/orestbida/cookieconsent) v2 (MIT).
Nahrazuje službu cookies-správně.cz (licence `TgxQ2ksUgrC6xf9eXBen`), která byla postavená na stejné knihovně –
chování je proto stejné. Převzato z levior-b2b (PR #57, `docs/cookie-consent.md` tam), postup pochází z MC Profi.

Lišta je pro **festa.tools** (správce LEVIOR s.r.o., značka FESTA).

## Kde co je

| Soubor | Účel |
| --- | --- |
| `app/modules/@head.latte` | výchozí stav Google Consent Mode v2 (vše `denied`) + načtení knihovny a našeho skriptu |
| `public/js/cookieConsent.js` | konfigurace lišty, texty (cs/en), tabulka cookies, blokování iframe, evidence souhlasu |
| `public/css/cookieConsent.css` | barvy (modrá jako u původní lišty), font Work Sans, doplňky vzhledu (tlačítko „Cookies“, křížek) |
| `app/modules/Web/CookieConsentPresenter.php` | `POST /cookie-consent` – uloží záznam o souhlasu (route v `app/config/config.neon`) |
| `app/modules/Web/DB/CookieConsent.php` | tabulka `web_cookie_consent` se záznamy souhlasů |

`@head.latte` vkládají všechny front šablony (`@layout.latte`, `Index.default`, `Index.sale`), lišta je tedy všude.
Knihovna je v `public/node_modules/vanilla-cookieconsent` (node_modules jsou ve verzování), nic se nestahuje
z cizí domény.

## Jak to funguje

- Kategorie: `necessary` (vždy), `analytics`, `marketing`, `ad_user_data`, `ad_personalization`.
- Volba je v cookie `cc_cookie` (182 dní) na registrovatelné doméně webu (`festa.tools`) – stejně jako
  u původní lišty (`save_on_subdomain: false`), takže dosavadní souhlasy platí dál.
- Jazyk lišty podle `data-lang` na `<script id="cookie-consent">` (translator posílá `cz` → `cs`), jinak `<html lang>`;
  cs a en, jiný jazyk dostane angličtinu. Šablony `Index.*` mají `<html>` bez `lang`, proto `data-lang`.
- GTM v hlavičce se spouští hned a řídí se Consent Mode. Nemá `data-cookiecategory`: knihovna
  (`page_scripts: true`) po souhlasu znovu spustí **každý** skript s tímhle atributem, i ten, který už běžel – GTM
  by se načetl dvakrát a GA4 by počítala page_view dvakrát (původní lišta to tak dělala).
- Tagy v GTM (Meta Pixel, Google Ads) mají v kontejneru nastavené vyžadované souhlasy (`ad_storage` …), takže se
  bez souhlasu nespustí.
- Google Consent Mode v2: `default` denied v hlavičce před GTM, `update` + událost `consentUpdate` v `dataLayer`
  při načtení stránky s uloženým souhlasem a při každé volbě.
- Po změně už uložené volby se stránka načte znovu.
- Při nesouhlasu se mažou first-party cookies `_ga*`, `_gid`, `_gat`, `_gcl_*`, `_fbp`.
- Iframe v HTML (videa „Festování“ na homepage) se bez souhlasu s analytickými i marketingovými cookies
  nahradí výzvou k povolení; YouTube se po souhlasu přepne na `youtube-nocookie.com`, Vimeo dostane `dnt=1`.
  Výjimka: atribut `data-cookies-ignore`.
- Tlačítko „Cookies“ vlevo dole znovu otevře podrobné nastavení. Z kódu: `cookieconsent.showSettings(0)`,
  `accept_all_cookies()`, `reject_all_cookies()`.
- Souhlasy udělené přes původní lištu zůstávají platné (skript jim při první návštěvě doplní ID a datum).

## Tabulka cookies

Je v `COOKIE_TABLE` v `public/js/cookieConsent.js`. Při převodu (11/2026) ověřeno proti šablonám, hlavičkám
odpovědí produkce a veřejnému kontejneru `GTM-5VXJMMGR` (GA4 `G-BL7ESXDL4V` a `G-F07GM38PJW`, Google Ads
`AW-10892387652` s Conversion Linkerem, Meta Pixel). Oproti původní liště vypadly cookies YouTube/DoubleClick na
cizích doménách (`YSC`, `VISITOR_INFO1_LIVE`, `VISITOR_PRIVACY_METADATA`, `__Secure-*`, `test_cookie`) – web je
sám nenastavuje a videa jdou po souhlasu přes `youtube-nocookie.com`; přibyla session cookie (název z konfigurace,
`data-session-cookie`), kterou nastavují formuláře.

Když přibude nástroj (v šabloně, v nastavení webu `scripts_header`/`scripts_footer` nebo v GTM), doplň ho do
tabulky, případně do `LEFTOVER_COOKIES`.

## Evidence souhlasů

Při první volbě a při každé změně se do tabulky `web_cookie_consent` uloží ID souhlasu (`consent_uuid` z cookie,
návštěvník ho vidí v hlavičce podrobného nastavení), povolené kategorie, revize a čas. IP adresa ani jiné osobní
údaje se neukládají.

Tabulku zakládá Migrator (`composer sync-database`, entita je v `App\Web\DB\*`), případně ručně
`app/modules/Web/data/cookie_consent.sql`. Na produkci ji založ **před** nasazením. Dokud tabulka chybí, endpoint
vrací 500 a lišta funguje dál, jen se volby neevidují.

## Úpravy

- **Texty a tabulka cookies:** `TEXTS`, `EXPIRATIONS`, `DESCRIPTIONS`, `COOKIE_TABLE` v `public/js/cookieConsent.js`.
- **Nový souhlas od všech** (např. přibyla kategorie nebo nástroj): zvýšit `REVISION` tamtéž.
- **Odkaz „Více o cookies“:** atribut `data-policy-url` v `app/modules/@head.latte`. Zatím prázdný – festa.tools
  nemá vlastní stránku se zásadami (původní lišta odkazovala na stránku hostovanou u cookies-správně.cz, ta
  se zrušením licence zmizí). Prázdná hodnota = odkaz se nezobrazí.
