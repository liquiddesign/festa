-- Evidence souhlasů s cookies (docs/cookie-consent.md)
CREATE TABLE IF NOT EXISTS `web_cookie_consent` (
  `uuid` varchar(36) NOT NULL,
  `consentId` varchar(255) NOT NULL,
  `categories` varchar(255) NOT NULL,
  `revision` int(11) NOT NULL DEFAULT 0,
  `action` varchar(16) NOT NULL,
  `createdTs` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uuid`),
  KEY `web_cookie_consent_consentid` (`consentId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
