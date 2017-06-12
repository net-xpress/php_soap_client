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

CREATE TABLE IF NOT EXISTS `db473835270`.`OrderHead` (
  `Currency`                VARCHAR(45)             DEFAULT NULL,
  `CustomerID`              INT(11)                 DEFAULT NULL,
  `DeliveryAddressID`       INT(11)                 DEFAULT NULL,
  `DoneTimestamp`           VARCHAR(45)             DEFAULT NULL,
  `DunningLevel`            INT(11)                 DEFAULT NULL,
  `EbaySellerAccount`       VARCHAR(45)             DEFAULT NULL,
  `EstimatedTimeOfShipment` VARCHAR(45)             DEFAULT NULL,
  `ExchangeRatio`           DECIMAL(8, 2)           DEFAULT NULL,
  `ExternalOrderID`         VARCHAR(45)             DEFAULT NULL,
  `Invoice`                 VARCHAR(45)             DEFAULT NULL,
  `IsNetto`                 TINYINT(1)              DEFAULT NULL,
  `LastUpdate`              INT(11)                 DEFAULT NULL,
  `Marking1ID`              INT(11)                 DEFAULT NULL,
  `MethodOfPaymentID`       INT(11)                 DEFAULT NULL,
  `StoreID`                 INT(11)                 DEFAULT NULL,
  `OrderDocumentNumbers`    INT(11)                 DEFAULT NULL,
  `OrderID`                 INT(11) NOT NULL        DEFAULT '0',
  `OrderInfos`              INT(11)                 DEFAULT NULL,
  `OrderStatus`             DECIMAL(8, 2)           DEFAULT NULL,
  `OrderTimestamp`          INT(11)                 DEFAULT NULL,
  `OrderType`               VARCHAR(45)             DEFAULT NULL,
  `PackageNumber`           VARCHAR(45)             DEFAULT NULL,
  `PaidTimestamp`           VARCHAR(45)             DEFAULT NULL,
  `ParentOrderID`           INT(11)                 DEFAULT NULL,
  `PaymentStatus`           INT(11)                 DEFAULT NULL,
  `ReferrerID`              DECIMAL(8, 2)           DEFAULT NULL,
  `RemoteIP`                VARCHAR(45)             DEFAULT NULL,
  `ResponsibleID`           INT(11)                 DEFAULT NULL,
  `SalesAgentID`            INT(11)                 DEFAULT NULL,
  `SellerAccount`           VARCHAR(45)             DEFAULT NULL,
  `ShippingCosts`           DECIMAL(8, 2)           DEFAULT NULL,
  `ShippingID`              INT(11)                 DEFAULT NULL,
  `ShippingMethodID`        INT(11)                 DEFAULT NULL,
  `ShippingProfileID`       INT(11)                 DEFAULT NULL,
  `TotalBrutto`             DECIMAL(10, 4)          DEFAULT NULL,
  `TotalInvoice`            DECIMAL(10, 4)          DEFAULT NULL,
  `TotalNetto`              DECIMAL(10, 4)          DEFAULT NULL,
  `TotalVAT`                DECIMAL(8, 2)           DEFAULT NULL,
  `WarehouseID`             INT(11)                 DEFAULT NULL,
  PRIMARY KEY (`OrderID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`OrderItem` (
  `id`                   INT(11) NOT NULL        AUTO_INCREMENT,
  `BundleItemID`         INT(11)                 DEFAULT NULL,
  `Currency`             VARCHAR(45)             DEFAULT NULL,
  `ExternalItemID`       VARCHAR(45)             DEFAULT NULL,
  `ExternalOrderItemID`  VARCHAR(45)             DEFAULT NULL,
  `ItemID`               INT(11)                 DEFAULT NULL,
  `ItemNo`               VARCHAR(45)             DEFAULT NULL,
  `ItemRebate`           DECIMAL(8, 2)           DEFAULT NULL,
  `ItemText`             VARCHAR(45)             DEFAULT NULL,
  `NeckermannItemNo`     VARCHAR(45)             DEFAULT NULL,
  `OrderID`              INT(11)                 DEFAULT NULL,
  `OrderRowID`           INT(11)                 DEFAULT NULL,
  `Price`                DECIMAL(10, 4)          DEFAULT NULL,
  `Quantity`             DECIMAL(8, 2)           DEFAULT NULL,
  `ReferrerID`           DECIMAL(8, 2)           DEFAULT NULL,
  `SKU`                  VARCHAR(45)             DEFAULT NULL,
  `SalesOrderProperties` INT(11)                 DEFAULT NULL,
  `VAT`                  DECIMAL(8, 2)           DEFAULT NULL,
  `WarehouseID`          INT(11)                 DEFAULT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;
