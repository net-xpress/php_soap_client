CREATE DATABASE IF NOT EXISTS `db473835270` /*!40100 DEFAULT CHARACTER SET utf8 */;

/**
 * used by StoreToken.class.php
 */
CREATE TABLE IF NOT EXISTS `db473835270`.`plenty_soap_token` (
  `soap_token_user`     VARCHAR(64) NOT NULL,
  `soap_token_inserted` DATETIME    DEFAULT NULL,
  `soap_token`          VARCHAR(32) DEFAULT NULL,
  `soap_token_user_id`  INT(11)     DEFAULT NULL,
  PRIMARY KEY (`soap_token_user`),
  KEY `inserted` (`soap_token_inserted`)
)
  ENGINE = InnoDB
  DEFAULT CHARACTER SET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`MetaLastUpdate` (
  `Function`          VARCHAR(45) NOT NULL,
  `LastUpdate`        INT(11) DEFAULT NULL,
  `CurrentLastUpdate` INT(11) DEFAULT NULL,
  `CurrentPage`       INT(11) DEFAULT NULL,
  PRIMARY KEY (`Function`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
