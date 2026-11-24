/**
 * Cookie lišta – vlastní nasazení knihovny vanilla-cookieconsent v2 (MIT), viz docs/cookie-consent.md.
 * Převzato z levior-b2b (stejný postup, upravené texty, jazyky a tabulka cookies).
 *
 * Kategorie: necessary, analytics, marketing, ad_user_data, ad_personalization.
 * Souhlas je v cookie `cc_cookie` (pole `level`) na registrovatelné doméně webu (festa.tools).
 * Skripty s atributem data-cookiecategory="…" knihovna spouští až po souhlasu s danou kategorií.
 */
(function () {
	'use strict';

	if (typeof window.initCookieConsent !== 'function') {
		return;
	}

	var scriptTag = document.getElementById('cookie-consent');
	var logUrl = scriptTag ? scriptTag.getAttribute('data-log-url') : null;
	var policyUrl = scriptTag ? scriptTag.getAttribute('data-policy-url') : null;
	var sessionCookie = (scriptTag ? scriptTag.getAttribute('data-session-cookie') : null) || 'PHPSESSID';
	// Provozovatel webu do tabulky funkčních cookies.
	var siteName = 'FESTA';

	var COOKIE_NAME = 'cc_cookie';
	var COOKIE_EXPIRATION = 182;
	// Při změně textů/kategorií, která vyžaduje nový souhlas, zvyš o 1 – lišta se všem zobrazí znovu.
	var REVISION = 1;
	var ALL_CATEGORIES = ['necessary', 'analytics', 'marketing', 'ad_user_data', 'ad_personalization'];
	var LANGUAGES = ['cs', 'en'];

	var hostname = window.location.hostname;
	// Stejně jako původní lišta (save_on_subdomain: false): cookie na registrovatelné doméně (www.festa.tools → festa.tools),
	// aby vedle sebe nevznikly dvě cookie cc_cookie. Pro .tools/.cz/.com stačí poslední dvě části hostname.
	var cookieDomain = /^[\d.]+$/.test(hostname) || hostname.indexOf('.') === -1 ? hostname : hostname.split('.').slice(-2).join('.');
	// Jazyk webu: data-lang na <script> (translator používá „cz“), záložně <html lang>.
	var pageLang = ((scriptTag ? scriptTag.getAttribute('data-lang') : null) || document.documentElement.lang || 'cs').toLowerCase().slice(0, 2);
	pageLang = pageLang === 'cz' ? 'cs' : pageLang;
	var lang = LANGUAGES.indexOf(pageLang) > -1 ? pageLang : 'en';

	// Cookies, které mažeme, když návštěvník kategorii nepovolí (jen first-party, cizí domény smazat nelze).
	var LEFTOVER_COOKIES = {
		analytics: [/^_ga($|_)/, /^_gid$/, /^_gat($|_)/],
		marketing: [/^_gcl_/, /^_fbp$/],
	};

	var TEXTS = {
		cs: {
			title: 'Používáme cookies',
			description: 'Cookies a další údaje používáme k&nbsp;zajištění funkčnosti webu a&nbsp;s&nbsp;vaším souhlasem také k&nbsp;měření návštěvnosti a&nbsp;přizpůsobení obsahu. Kliknutím na „Přijmout všechny“ souhlasíte s&nbsp;využitím cookies a&nbsp;dalších údajů včetně jejich předání pro cílenou reklamu na sociálních a&nbsp;reklamních sítích.',
			acceptAll: 'Přijmout všechny',
			rejectAll: 'Odmítnout všechny',
			settings: 'Podrobné nastavení',
			policy: 'Více o cookies',
			save: 'Uložit',
			close: 'Zavřít',
			floatingButton: 'Cookies',
			headers: ['Cookie', 'Doména', 'Platnost', 'Popis', 'Poskytovatel'],
			introTitle: 'Co jsou cookies?',
			intro: 'Cookies nám pomáhají vyhodnocovat návštěvnost, vylepšovat web a zobrazovat obsah, který vás zajímá. Zde si můžete zvolit, které kategorie povolíte. Svou volbu můžete kdykoli změnit tlačítkem „Cookies“ v&nbsp;levém dolním rohu.',
			necessaryTitle: 'Funkční cookies',
			necessary: 'Jsou nezbytné pro fungování webu (odesílání formulářů, uložení této volby), proto je nelze vypnout. Neukládají informace, které by bylo možné přiřadit konkrétní osobě.',
			analyticsTitle: 'Analytické cookies',
			analytics: 'Umožňují nám měřit počet návštěvníků a sledovat, jak web používají. Díky tomu ho můžeme průběžně zlepšovat. Použijeme je pouze s&nbsp;vaším souhlasem.',
			marketingTitle: 'Marketingové cookies',
			marketing: 'Slouží k&nbsp;měření účinnosti reklamy a&nbsp;k&nbsp;zobrazování reklamy odpovídající vašim zájmům, a&nbsp;to i&nbsp;na stránkách třetích stran. Využívají nástroje externích společností (Google, Meta). Bez nich se nezobrazí ani videa z&nbsp;YouTube. Použijeme je pouze s&nbsp;vaším souhlasem.',
			adUserDataTitle: 'Sdílení údajů pro reklamu (Google)',
			adUserData: 'Souhlas s&nbsp;odesíláním údajů o&nbsp;vašem chování na webu společnosti Google pro účely online reklamy (signál ad_user_data).',
			adPersonalizationTitle: 'Personalizovaná reklama (Google)',
			adPersonalization: 'Souhlas s&nbsp;využitím vašich údajů k&nbsp;personalizaci reklam Google a&nbsp;k&nbsp;remarketingu (signál ad_personalization).',
			iframeBlocked: 'Pro zobrazení tohoto obsahu povolte prosím ukládání cookies.',
			iframeSettings: 'Nastavit jednotlivé typy cookies',
		},
		en: {
			title: 'We use cookies',
			description: 'We use cookies and other data to keep the website working and, with your consent, to measure traffic and personalise content. By clicking “Accept all” you agree to the use of cookies and other data, including their transfer for targeted advertising on social and advertising networks.',
			acceptAll: 'Accept all',
			rejectAll: 'Reject all',
			settings: 'Detailed settings',
			policy: 'More about cookies',
			save: 'Save',
			close: 'Close',
			floatingButton: 'Cookies',
			headers: ['Cookie', 'Domain', 'Expiration', 'Description', 'Provider'],
			introTitle: 'What are cookies?',
			intro: 'Cookies help us analyse traffic, improve the website and show content relevant to you. Here you can choose which categories to allow. You can change your choice at any time using the “Cookies” button in the bottom left corner.',
			necessaryTitle: 'Functional cookies',
			necessary: 'They are essential for the website to work (submitting forms, storing this choice) and therefore cannot be switched off. They do not store information that can be linked to a specific person.',
			analyticsTitle: 'Analytical cookies',
			analytics: 'They let us measure the number of visitors and see how the website is used, so that we can keep improving it. They are used only with your consent.',
			marketingTitle: 'Marketing cookies',
			marketing: 'They are used to measure advertising effectiveness and to show advertising matching your interests, including on third-party sites. They rely on tools of external companies (Google, Meta). YouTube videos are not shown without them either. They are used only with your consent.',
			adUserDataTitle: 'Advertising data sharing (Google)',
			adUserData: 'Consent to send data about your activity on this website to Google for online advertising purposes (the ad_user_data signal).',
			adPersonalizationTitle: 'Personalised advertising (Google)',
			adPersonalization: 'Consent to use your data to personalise Google ads and for remarketing (the ad_personalization signal).',
			iframeBlocked: 'Please enable cookies to view this content.',
			iframeSettings: 'Set individual cookie types',
		},
	};

	// Doby platnosti a popisy ve všech jazycích; klíče používá COOKIE_TABLE.
	var EXPIRATIONS = {
		session: {cs: 'Relace', en: 'Session'},
		months3: {cs: '3 měsíce', en: '3 months'},
		days182: {cs: '182 dní', en: '182 days'},
		years2: {cs: '2 roky', en: '2 years'},
	};

	var DESCRIPTIONS = {
		session: {
			cs: 'Identifikátor relace – odesílání formulářů.',
			en: 'Session identifier – submitting forms.',
		},
		ccCookie: {
			cs: 'Uložená volba cookies.',
			en: 'Stored cookie preferences.',
		},
		ga: {
			cs: 'ID používané k rozlišení návštěvníků (Google Analytics).',
			en: 'ID used to distinguish visitors (Google Analytics).',
		},
		gclAu: {
			cs: 'Propojení konverzí s kliknutím na reklamu Google (Conversion Linker, Google Ads).',
			en: 'Links conversions to Google ad clicks (Conversion Linker, Google Ads).',
		},
		fbp: {
			cs: 'Měření účinnosti reklamy a remarketing na Facebooku a Instagramu (Meta Pixel).',
			en: 'Measures advertising effectiveness and remarketing on Facebook and Instagram (Meta Pixel).',
		},
	};

	// [název, doména, klíč platnosti, klíč popisu, poskytovatel] – ověřeno v šablonách a v kontejneru GTM-5VXJMMGR
	var COOKIE_TABLE = {
		necessary: [
			[sessionCookie, '/', 'session', 'session', siteName],
			['cc_cookie', '/', 'days182', 'ccCookie', siteName],
		],
		analytics: [
			['_ga, _ga_*', '/', 'years2', 'ga', 'Google'],
		],
		marketing: [
			['_gcl_au', '/', 'months3', 'gclAu', 'Google'],
			['_fbp', '/', 'months3', 'fbp', 'Meta'],
		],
	};

	function buildTable(category, language) {
		return COOKIE_TABLE[category].map(function (row) {
			return {
				col1: row[0],
				col2: row[1],
				col3: EXPIRATIONS[row[2]][language],
				col4: DESCRIPTIONS[row[3]][language],
				col5: row[4],
			};
		});
	}

	function buildLanguage(language) {
		var t = TEXTS[language];
		var policyLink = policyUrl ? '<a href="' + policyUrl + '" class="cc-link">' + t.policy + '</a>' : '';

		function displayTable(category) {
			return buildTable(category, language);
		}

		return {
			consent_modal: {
				title: t.title,
				description: t.description + '<div class="cc-add-links"><button type="button" data-cc="c-settings" class="cc-link">' + t.settings + '</button>' + policyLink + '</div>',
				primary_btn: {text: t.acceptAll, role: 'accept_all'},
				secondary_btn: {text: t.rejectAll, role: 'accept_necessary'},
			},
			settings_modal: {
				title: t.settings,
				save_settings_btn: t.save,
				accept_all_btn: t.acceptAll,
				reject_all_btn: t.rejectAll,
				close_btn_label: t.close,
				cookie_table_headers: [{col1: t.headers[0]}, {col2: t.headers[1]}, {col3: t.headers[2]}, {col4: t.headers[3]}, {col5: t.headers[4]}],
				blocks: [
					{title: t.introTitle, description: t.intro + (policyLink ? ' ' + policyLink : '')},
					{title: t.necessaryTitle, description: t.necessary, toggle: {value: 'necessary', enabled: true, readonly: true}, cookie_table: displayTable('necessary')},
					{title: t.analyticsTitle, description: t.analytics, toggle: {value: 'analytics', enabled: false, readonly: false}, cookie_table: displayTable('analytics')},
					{title: t.marketingTitle, description: t.marketing, toggle: {value: 'marketing', enabled: false, readonly: false}, cookie_table: displayTable('marketing')},
					{title: t.adUserDataTitle, description: t.adUserData, toggle: {value: 'ad_user_data', enabled: false, readonly: false}},
					{title: t.adPersonalizationTitle, description: t.adPersonalization, toggle: {value: 'ad_personalization', enabled: false, readonly: false}},
				],
			},
		};
	}

	function uuid() {
		if (window.crypto && typeof window.crypto.randomUUID === 'function') {
			return window.crypto.randomUUID();
		}

		return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
			var r = Math.random() * 16 | 0;

			return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
		});
	}

	function readConsentCookie() {
		var match = document.cookie.match('(^|;) ?' + COOKIE_NAME + '=([^;]*)(;|$)');

		if (!match) {
			return null;
		}

		var candidates = [match[2]];

		try {
			candidates.push(decodeURIComponent(match[2]));
		} catch (e) {
			// hodnota není URL-kódovaná
		}

		for (var i = 0; i < candidates.length; i++) {
			try {
				var parsed = JSON.parse(candidates[i]);

				if (parsed && Array.isArray(parsed.level)) {
					return parsed;
				}
			} catch (e) {
				// zkusíme další variantu
			}
		}

		return null;
	}

	/**
	 * Souhlasy uložené původní lištou (cookies-správně.cz, knihovna 2.7) nemají consent_uuid ani data,
	 * novější verze knihovny by je brala jako neplatné a lištu zobrazila znovu. Doplníme je.
	 */
	function migrateLegacyCookie() {
		var cookie = readConsentCookie();

		if (!cookie || cookie.consent_uuid) {
			return;
		}

		var now = new Date();
		cookie.categories = cookie.level;
		cookie.consent_uuid = uuid();
		cookie.consent_date = now.toISOString();
		cookie.last_consent_update = now.toISOString();
		cookie.rfc_cookie = false;

		var expires = new Date(now.getTime() + COOKIE_EXPIRATION * 24 * 60 * 60 * 1000);
		var cookieString = COOKIE_NAME + '=' + JSON.stringify(cookie) + '; expires=' + expires.toUTCString() + '; Path=/; SameSite=Lax;';

		if (hostname.indexOf('.') > -1) {
			cookieString += ' Domain=' + cookieDomain + ';';
		}

		if (window.location.protocol === 'https:') {
			cookieString += ' Secure;';
		}

		document.cookie = cookieString;
	}

	function removeCookie(name) {
		var domains = [null, hostname, '.' + hostname, cookieDomain, '.' + cookieDomain];

		domains.forEach(function (domain) {
			document.cookie = name + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT; Path=/;' + (domain ? ' Domain=' + domain + ';' : '');
		});
	}

	function removeLeftoverCookies(cc) {
		var names = document.cookie.split(';').map(function (part) {
			return part.split('=')[0].trim();
		});

		Object.keys(LEFTOVER_COOKIES).forEach(function (category) {
			if (cc.allowedCategory(category)) {
				return;
			}

			names.forEach(function (name) {
				var matches = LEFTOVER_COOKIES[category].some(function (pattern) {
					return pattern.test(name);
				});

				if (matches) {
					removeCookie(name);
				}
			});
		});
	}

	function consentState(cc) {
		return {
			ad_storage: cc.allowedCategory('marketing') ? 'granted' : 'denied',
			analytics_storage: cc.allowedCategory('analytics') ? 'granted' : 'denied',
			ad_user_data: cc.allowedCategory('ad_user_data') ? 'granted' : 'denied',
			ad_personalization: cc.allowedCategory('ad_personalization') ? 'granted' : 'denied',
		};
	}

	/** Google Consent Mode v2 – výchozí stav (vše denied) nastavuje inline skript v @head.latte. */
	function pushConsentUpdate(cc) {
		var state = consentState(cc);

		window.dataLayer = window.dataLayer || [];
		window.dataLayer.push(Object.assign({event: 'consentUpdate'}, state));

		if (typeof window.gtag === 'function') {
			window.gtag('consent', 'update', state);
		}
	}

	/** Evidence souhlasu na našem serveru (Web:CookieConsent) – doklad o udělení/změně souhlasu. */
	function logConsent(cc, action) {
		if (!logUrl || typeof window.fetch !== 'function') {
			return Promise.resolve();
		}

		var categories = ALL_CATEGORIES.filter(function (category) {
			return cc.allowedCategory(category);
		});

		return window.fetch(logUrl, {
			method: 'POST',
			headers: {'Content-Type': 'application/json'},
			body: JSON.stringify({
				consentId: cc.get('consent_uuid'),
				categories: categories,
				revision: REVISION,
				action: action,
			}),
			keepalive: true,
		}).catch(function () {
			// evidence nesmí rozbít web
		});
	}

	// --- Blokování iframe (YouTube, mapy…), dokud návštěvník nepovolí analytické a marketingové cookies ---

	var blockedIframes = [];

	function iframePlaceholder() {
		var t = TEXTS[lang];
		var buttonStyle = 'font:inherit;font-weight:600;padding:.6em 1.2em;margin:.25em;border:0;border-radius:.3em;cursor:pointer;';

		return '<!DOCTYPE html><html lang="' + lang + '"><head><meta charset="utf-8"></head>'
			+ '<body style="margin:0;height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;font-family:\'Work Sans\',Arial,sans-serif;font-size:14px;background:#f3f4f6;color:#374151;padding:1em;box-sizing:border-box;">'
			+ '<p style="margin:0 0 1em;">' + t.iframeBlocked + '</p><div>'
			+ '<button type="button" style="' + buttonStyle + 'background:rgb(0,159,223);color:#fff;" onclick="parent.postMessage(\'cc_accept_all\',\'*\')">' + t.acceptAll + '</button>'
			+ '<button type="button" style="' + buttonStyle + 'background:#e5e7eb;color:#374151;" onclick="parent.postMessage(\'cc_show_settings\',\'*\')">' + t.iframeSettings + '</button>'
			+ '</div></body></html>';
	}

	function blockIframes(cc) {
		var fullConsent = cc.allowedCategory('analytics') && cc.allowedCategory('marketing');

		Array.prototype.forEach.call(document.querySelectorAll('iframe[src]'), function (iframe) {
			var src = iframe.getAttribute('src');

			if (!src || src === 'null' || src.indexOf('about:') === 0 || iframe.hasAttribute('data-cookies-ignore')) {
				return;
			}

			if (src.indexOf('youtube.com') > -1 && cc.allowedCategory('marketing')) {
				iframe.setAttribute('src', src.replace('youtube.com', 'youtube-nocookie.com'));

				return;
			}

			if (src.indexOf('vimeo') > -1) {
				if (src.indexOf('dnt=1') === -1) {
					iframe.setAttribute('src', src + (src.indexOf('?') > -1 ? '&' : '?') + 'dnt=1');
				}

				return;
			}

			if (src.indexOf('google.com/recaptcha') > -1 || fullConsent) {
				return;
			}

			blockedIframes.push({iframe: iframe, src: src});
			iframe.removeAttribute('src');
			iframe.setAttribute('srcdoc', iframePlaceholder());
		});
	}

	function unblockIframes(cc) {
		if (!cc.allowedCategory('analytics') || !cc.allowedCategory('marketing')) {
			return;
		}

		blockedIframes.forEach(function (blocked) {
			blocked.iframe.removeAttribute('srcdoc');
			// YouTube rovnou přes youtube-nocookie.com, stejně jako po reloadu (blockIframes)
			blocked.iframe.setAttribute('src', blocked.src.replace('youtube.com', 'youtube-nocookie.com'));
		});
		blockedIframes = [];
	}

	// --- Doplňky UI nad rámec knihovny ---

	function showConsentId(cc) {
		var title = document.getElementById('s-ttl');
		var consentId = cc.get('consent_uuid');

		if (!title || !consentId) {
			return;
		}

		var field = document.getElementById('cc-id-field');

		if (!field) {
			field = document.createElement('div');
			field.id = 'cc-id-field';
			field.className = 'cc-id-field';
			title.appendChild(field);
		}

		field.textContent = 'ID: ' + consentId;
	}

	function createSettingsButton(cc) {
		var button = document.createElement('button');
		button.type = 'button';
		button.id = 'cc-show-settings-button';
		button.textContent = TEXTS[lang].floatingButton;
		button.addEventListener('click', function () {
			cc.showSettings(0);
		});
		document.body.appendChild(button);
	}

	function createCloseButton(cc) {
		var modal = document.getElementById('cm');

		if (!modal) {
			return;
		}

		var button = document.createElement('button');
		button.type = 'button';
		button.className = 'cc-close-button';
		button.setAttribute('aria-label', TEXTS[lang].close);
		button.innerHTML = '&times;';
		button.addEventListener('click', function () {
			cc.hide();
		});
		modal.appendChild(button);
	}

	function acceptAll(cc) {
		cc.accept('all');
		cc.hide();
		cc.hideSettings();
	}

	function rejectAll(cc) {
		cc.accept([]);
		cc.hide();
		cc.hideSettings();
	}

	// --- Start ---

	migrateLegacyCookie();

	var cc = window.initCookieConsent();
	window.cookieconsent = cc;
	window.accept_all_cookies = function () {
		acceptAll(cc);
	};
	window.reject_all_cookies = function () {
		rejectAll(cc);
	};

	cc.run({
		current_lang: lang,
		autorun: true,
		page_scripts: true,
		autoclear_cookies: true,
		force_consent: false,
		hide_from_robots: true,
		cookie_name: COOKIE_NAME,
		cookie_expiration: COOKIE_EXPIRATION,
		cookie_domain: cookieDomain,
		cookie_path: '/',
		cookie_same_site: 'Lax',
		use_rfc_cookie: false,
		revision: REVISION,
		gui_options: {
			consent_modal: {layout: 'cloud', position: 'bottom center', transition: 'slide'},
			settings_modal: {layout: 'box', transition: 'slide'},
		},
		onFirstAction: function () {
			pushConsentUpdate(cc);
			logConsent(cc, 'first');
			showConsentId(cc);
		},
		onAccept: function () {
			removeLeftoverCookies(cc);
			unblockIframes(cc);
		},
		onChange: function () {
			removeLeftoverCookies(cc);
			pushConsentUpdate(cc);
			// Po změně volby stránku načteme znovu, aby se skripty zapnuly/vypnuly podle nového nastavení.
			logConsent(cc, 'change').then(function () {
				window.location.reload();
			});
		},
		languages: {
			cs: buildLanguage('cs'),
			en: buildLanguage('en'),
		},
	});

	['cm', 's-cnt'].forEach(function (id) {
		var element = document.getElementById(id);

		if (element) {
			element.setAttribute('data-nosnippet', 'data-nosnippet');
		}
	});

	// Roboti: knihovna se pro ně nespustí (hide_from_robots), žádné UI tedy nepřidáváme.
	if (!document.getElementById('cc--main')) {
		return;
	}

	createSettingsButton(cc);
	createCloseButton(cc);
	showConsentId(cc);
	removeLeftoverCookies(cc);
	blockIframes(cc);

	window.addEventListener('message', function (event) {
		var fromBlockedIframe = blockedIframes.some(function (blocked) {
			return blocked.iframe.contentWindow === event.source;
		});

		if (!fromBlockedIframe) {
			return;
		}

		if (event.data === 'cc_show_settings') {
			cc.showSettings(0);
		} else if (event.data === 'cc_accept_all') {
			acceptAll(cc);
		}
	});
})();
