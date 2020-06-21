CREATE TABLE `genetmap` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `chr` int(2) unsigned NOT NULL,
  `pos` int(10) NOT NULL,
  `cm` decimal(9,6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `genetmap_chr_index` (`chr`)
) ENGINE=InnoDB AUTO_INCREMENT=32121 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;