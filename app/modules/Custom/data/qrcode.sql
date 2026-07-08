-- QR Code redirect module
CREATE TABLE IF NOT EXISTS `custom_qrcode` (
  `uuid` varchar(36) NOT NULL,
  `sourceUrl` varchar(255) NOT NULL,
  `targetUrl` varchar(255) NOT NULL,
  `createdTs` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updatedTs` datetime DEFAULT NULL,
  PRIMARY KEY (`uuid`),
  UNIQUE KEY `sourceUrl` (`sourceUrl`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_czech_ci;
