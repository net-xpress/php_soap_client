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


CREATE TABLE IF NOT EXISTS `db473835270`.`MetaConfig` (
  `ConfigKey`   VARCHAR(45) NOT NULL,
  `Domain`      VARCHAR(45) NOT NULL,
  `ConfigValue` VARCHAR(45) NOT NULL,
  `ConfigType`  VARCHAR(45)          DEFAULT NULL,
  `LastUpdate`  INT(11)              DEFAULT NULL,
  `Active`      TINYINT(1)  NOT NULL DEFAULT '0',
  PRIMARY KEY (`ConfigKey`, `Domain`)
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

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemsBase` (
  `ItemID`              INT(11) NOT NULL,
  `ItemNo`              VARCHAR(45) DEFAULT NULL,
  `ExternalItemID`      VARCHAR(45) DEFAULT NULL,
  `EAN1`                BIGINT(13)  DEFAULT NULL,
  `EAN2`                BIGINT(13)  DEFAULT NULL,
  `EAN3`                BIGINT(13)  DEFAULT NULL,
  `EAN4`                BIGINT(13)  DEFAULT NULL,
  `ISBN`                VARCHAR(45) DEFAULT NULL,
  `Type`                INT(11)     DEFAULT NULL,
  `Model`               VARCHAR(45) DEFAULT NULL,
  `ProducerID`          INT(11)     DEFAULT NULL,
  `ProducerName`        VARCHAR(45) DEFAULT NULL,
  `VATInternalID`       INT(11)     DEFAULT NULL,
  `Marking1ID`          INT(11)     DEFAULT NULL,
  `Marking2ID`          INT(11)     DEFAULT NULL,
  `CustomsTariffNumber` VARCHAR(45) DEFAULT NULL,
  `FSK`                 INT(11)     DEFAULT NULL,
  `Condition`           INT(11)     DEFAULT NULL,
  `Position`            VARCHAR(45) DEFAULT NULL,
  `StorageLocation`     INT(11)     DEFAULT NULL,
  `WebShopSpecial`      VARCHAR(45) DEFAULT NULL,
  `Published`           INT(11)     DEFAULT NULL,
  `LastUpdate`          INT(11)     DEFAULT NULL,
  `ItemURL`             VARCHAR(45) DEFAULT NULL,
  `ProducingCountryID`  INT(11)     DEFAULT NULL,
  `BundleType`          VARCHAR(45) DEFAULT NULL,
  `HasAttributes`       TINYINT(1)  DEFAULT NULL,
  `DeepLink`            VARCHAR(45) DEFAULT NULL,
  `Inserted`            INT(11)     DEFAULT NULL,
  PRIMARY KEY (`ItemID`),
  UNIQUE KEY `unique_key` (`ItemNo`, `EAN1`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemCategories` (
  `ItemID`                 INT(11) NOT NULL,
  `ItemCategoryID`         INT(11) DEFAULT NULL,
  `ItemCategoryLevel`      INT(11) DEFAULT NULL,
  `ItemCategoryPath`       TEXT    DEFAULT NULL,
  `ItemCategoryPathNames`  TEXT    DEFAULT NULL,
  `RemoveCategoryFromItem` INT(11) DEFAULT NULL,
  PRIMARY KEY (`ItemID`, `ItemCategoryID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemAttributeValueSets` (
  `ItemID`                INT(11)     NOT NULL,
  `AttributeValueSetID`   INT(11)     NOT NULL,
  `AttributeValueSetName` VARCHAR(45) NOT NULL,
  `EAN`                   BIGINT(13)    DEFAULT NULL,
  `EAN2`                  BIGINT(13)    DEFAULT NULL,
  `EAN3`                  BIGINT(13)    DEFAULT NULL,
  `EAN4`                  BIGINT(13)    DEFAULT NULL,
  `ASIN`                  VARCHAR(45)   DEFAULT NULL,
  `ColliNo`               VARCHAR(45)   DEFAULT NULL,
  `PriceID`               INT(11)       DEFAULT NULL,
  `Availability`          INT(11)       DEFAULT NULL,
  `PurchasePrice`         DECIMAL(8, 2) DEFAULT NULL,
  `UVP`                   DECIMAL(8, 2) DEFAULT NULL,
  `Oversale`              TINYINT(1)    DEFAULT NULL,
  PRIMARY KEY (`ItemID`, `AttributeValueSetID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemTexts` (
  `ItemID`                  INT(11) NOT NULL,
  `Lang`                    VARCHAR(45) DEFAULT 'de',
  `Name`                    TEXT        DEFAULT NULL,
  `Name2`                   TEXT        DEFAULT NULL,
  `Name3`                   TEXT        DEFAULT NULL,
  `ShortDescription`        TEXT        DEFAULT NULL,
  `LongDescription`         TEXT        DEFAULT NULL,
  `TechnicalData`           TEXT        DEFAULT NULL,
  `MetaDescription`         TEXT        DEFAULT NULL,
  `ItemDescriptionKeywords` TEXT        DEFAULT NULL,
  `UrlContent`              VARCHAR(45) DEFAULT NULL,
  PRIMARY KEY (`ItemID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemFreeTextFields` (
  `ItemID` INT(11) NOT NULL,
  `Free1`  TEXT DEFAULT NULL,
  `Free2`  TEXT DEFAULT NULL,
  `Free3`  TEXT DEFAULT NULL,
  `Free4`  TEXT DEFAULT NULL,
  `Free5`  TEXT DEFAULT NULL,
  `Free6`  TEXT DEFAULT NULL,
  `Free7`  TEXT DEFAULT NULL,
  `Free8`  TEXT DEFAULT NULL,
  `Free9`  TEXT DEFAULT NULL,
  `Free10` TEXT DEFAULT NULL,
  `Free11` TEXT DEFAULT NULL,
  `Free12` TEXT DEFAULT NULL,
  `Free13` TEXT DEFAULT NULL,
  `Free14` TEXT DEFAULT NULL,
  `Free15` TEXT DEFAULT NULL,
  `Free16` TEXT DEFAULT NULL,
  `Free17` TEXT DEFAULT NULL,
  `Free18` TEXT DEFAULT NULL,
  `Free19` TEXT DEFAULT NULL,
  `Free20` TEXT DEFAULT NULL,
  PRIMARY KEY (`ItemID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemAvailability` (
  `ItemID`                                INT(11) NOT NULL,
  `Allyouneeed`                           INT(11)     DEFAULT NULL,
  `AmazonFBA`                             INT(11)     DEFAULT NULL,
  `AmazonFEDAS`                           VARCHAR(45) DEFAULT NULL,
  `AmazonMultichannel`                    TINYINT(1)  DEFAULT NULL,
  `AmazonMultichannelCom`                 TINYINT(1)  DEFAULT NULL,
  `AmazonMultichannelDe`                  TINYINT(1)  DEFAULT NULL,
  `AmazonMultichannelEs`                  TINYINT(1)  DEFAULT NULL,
  `AmazonMultichannelFr`                  TINYINT(1)  DEFAULT NULL,
  `AmazonMultichannelIt`                  TINYINT(1)  DEFAULT NULL,
  `AmazonMultichannelUk`                  TINYINT(1)  DEFAULT NULL,
  `AmazonProduct`                         INT(11)     DEFAULT NULL,
  `AvailabilityID`                        INT(11)     DEFAULT NULL,
  `AvailableUntil`                        INT(11)     DEFAULT NULL,
  `Cdiscount`                             INT(11)     DEFAULT NULL,
  `CouchCommerce`                         INT(11)     DEFAULT NULL,
  `DaWanda`                               INT(11)     DEFAULT NULL,
  `Flubit`                                INT(11)     DEFAULT NULL,
  `Fruugo`                                INT(11)     DEFAULT NULL,
  `GartenXXL`                             INT(11)     DEFAULT NULL,
  `Gimahhot`                              INT(11)     DEFAULT NULL,
  `GoogleBase`                            INT(11)     DEFAULT NULL,
  `Grosshandel`                           INT(11)     DEFAULT NULL,
  `Hertie`                                INT(11)     DEFAULT NULL,
  `Hitmeister`                            INT(11)     DEFAULT NULL,
  `Hood`                                  INT(11)     DEFAULT NULL,
  `Inactive`                              INT(11)     DEFAULT NULL,
  `IntervalSalesOrderQuantity`            INT(11)     DEFAULT NULL,
  `LaRedoute`                             INT(11)     DEFAULT NULL,
  `Laary`                                 INT(11)     DEFAULT NULL,
  `MaximumSalesOrderQuantity`             INT(11)     DEFAULT NULL,
  `Mercateo`                              INT(11)     DEFAULT NULL,
  `MinimumSalesOrderQuantity`             INT(11)     DEFAULT NULL,
  `NeckermannAtCrossDocking`              INT(11)     DEFAULT NULL,
  `NeckermannAtCrossDockingProductType`   VARCHAR(45) DEFAULT NULL,
  `NeckermannAtCrossDockingProvisionType` VARCHAR(45) DEFAULT NULL,
  `NeckermannAtEnterprise`                INT(11)     DEFAULT NULL,
  `NeckermannAtEnterpriseProductType`     VARCHAR(45) DEFAULT NULL,
  `NeckermannAtEnterpriseProvisionType`   VARCHAR(45) DEFAULT NULL,
  `Otto`                                  INT(11)     DEFAULT NULL,
  `Play`                                  INT(11)     DEFAULT NULL,
  `PlusDe`                                INT(11)     DEFAULT NULL,
  `RakutenDe`                             INT(11)     DEFAULT NULL,
  `RakutenDeCategory`                     INT(11)     DEFAULT NULL,
  `RakutenUk`                             INT(11)     DEFAULT NULL,
  `Restposten`                            INT(11)     DEFAULT NULL,
  `Shopgate`                              INT(11)     DEFAULT NULL,
  `SumoScout`                             INT(11)     DEFAULT NULL,
  `Tracdelight`                           INT(11)     DEFAULT NULL,
  `Twenga`                                INT(11)     DEFAULT NULL,
  `WebAPI`                                INT(11)     DEFAULT NULL,
  `Webshop`                               INT(11)     DEFAULT NULL,
  `Yatego`                                INT(11)     DEFAULT NULL,
  `Zalando`                               INT(11)     DEFAULT NULL,
  `Zentralverkauf`                        INT(11)     DEFAULT NULL,
  PRIMARY KEY (`ItemID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemOthers` (
  `ItemID`              INT(11) NOT NULL,
  `AuctionTitleLinkage` VARCHAR(45)   DEFAULT NULL,
  `Coupon`              INT(11)       DEFAULT NULL,
  `CustomerClass`       INT(11)       DEFAULT NULL,
  `EbayAcceptValueMin`  DECIMAL(8, 2) DEFAULT NULL,
  `EbayCategory1`       INT(11)       DEFAULT NULL,
  `EbayCategory2`       INT(11)       DEFAULT NULL,
  `EbayDenyValueBelow`  DECIMAL(8, 2) DEFAULT NULL,
  `EbayPreset`          INT(11)       DEFAULT NULL,
  `EbayShopCategory1`   VARCHAR(45)   DEFAULT NULL,
  `EbayShopCategory2`   VARCHAR(45)   DEFAULT NULL,
  `ItemApiCondition`    INT(11)       DEFAULT NULL,
  `ItemCondition`       INT(11)       DEFAULT NULL,
  `ItemEvaluation`      INT(11)       DEFAULT NULL,
  `ItemLinkage`         INT(11)       DEFAULT NULL,
  `PornographicContent` INT(11)       DEFAULT NULL,
  `Position`            INT(11)       DEFAULT NULL,
  `RevenueAccount`      INT(11)       DEFAULT NULL,
  `SerialNumber`        INT(11)       DEFAULT NULL,
  `ShippingPackage`     INT(11)       DEFAULT NULL,
  `Subscription`        INT(11)       DEFAULT NULL,
  PRIMARY KEY (`ItemID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemStock` (
  `ItemID`                              INT(11) NOT NULL,
  `ChangeAvailablePositiveStock`        TINYINT(1) DEFAULT NULL,
  `ChangeAvailablePositiveStockVariant` TINYINT(1) DEFAULT NULL,
  `ChangeNotAvailableNoStock`           TINYINT(1) DEFAULT NULL,
  `ChangeNotAvailableNoStockVariant`    TINYINT(1) DEFAULT NULL,
  `Limitation`                          INT(11)    DEFAULT NULL,
  `MainWarehouseID`                     INT(11)    DEFAULT NULL,
  `StorageLocation`                     INT(11)    DEFAULT NULL,
  `WebshopInvisibleNoStock`             TINYINT(1) DEFAULT NULL,
  `WebshopVisiblePositiveStock`         TINYINT(1) DEFAULT NULL,
  PRIMARY KEY (`ItemID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemsWarehouseSettings` (
  /*`SKU`                 VARCHAR(45) NOT NULL, replaced with ItemID in combination with AttributeValueSetID */
  `ItemID`              INT(11) NOT NULL,
  `AttributeValueSetID` INT(11) NOT NULL,
  `ID`                  INT(11)     DEFAULT NULL,
  `MaximumStock`        INT(11)     DEFAULT NULL,
  `ReorderLevel`        INT(11)     DEFAULT NULL,
  `StockBuffer`         INT(11)     DEFAULT NULL,
  `StockTurnover`       INT(11)     DEFAULT NULL,
  `StorageLocation`     INT(11)     DEFAULT NULL,
  `StorageLocationType` VARCHAR(45) DEFAULT NULL,
  `WarehouseID`         INT(11)     DEFAULT NULL,
  `Zone`                INT(11)     DEFAULT NULL,
  PRIMARY KEY (`ItemID`, `AttributeValueSetID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`ItemsSuppliers` (
  `ItemID`                  INT(11) NOT NULL,
  `SupplierID`              INT(11) NOT NULL,
  `IsRebateAllowed`         VARCHAR(45) DEFAULT NULL,
  `ItemSupplierPrice`       DOUBLE      DEFAULT NULL,
  `ItemSupplierRowID`       INT(11)     DEFAULT NULL,
  `LastUpdate`              INT(11)     DEFAULT NULL,
  `Priority`                INT(11)     DEFAULT NULL,
  `Rebate`                  DOUBLE      DEFAULT NULL,
  `SupplierDeliveryTime`    INT(11)     DEFAULT NULL,
  `SupplierItemNumber`      VARCHAR(45) DEFAULT NULL,
  `SupplierMinimumPurchase` DOUBLE      DEFAULT NULL,
  `VPE`                     DOUBLE      DEFAULT NULL,
  PRIMARY KEY (`ItemID`, `SupplierID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`WarehouseList` (
  `WarehouseID` INT(11) NOT NULL,
  `Name`        VARCHAR(45) DEFAULT NULL,
  `Type`        INT(11)     DEFAULT NULL,
  PRIMARY KEY (`WarehouseID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`CurrentStocks` (
  `ItemID`               INT(11) NOT NULL,
  `PriceID`              INT(11) NOT NULL,
  `AttributeValueSetID`  INT(11) NOT NULL,
  `WarehouseID`          INT(11) NOT NULL,
  `AveragePrice`         DECIMAL(10, 4) DEFAULT NULL,
  `EAN`                  BIGINT(13)     DEFAULT NULL,
  `EAN2`                 BIGINT(13)     DEFAULT NULL,
  `EAN3`                 BIGINT(13)     DEFAULT NULL,
  `EAN4`                 BIGINT(13)     DEFAULT NULL,
  `NetStock`             DECIMAL(10, 4) DEFAULT NULL,
  `PhysicalStock`        DECIMAL(10, 4) DEFAULT NULL,
  `StorageLocationID`    INT(11)        DEFAULT NULL,
  `StorageLocationName`  VARCHAR(45)    DEFAULT NULL,
  `StorageLocationStock` VARCHAR(45)    DEFAULT NULL,
  `VariantEAN`           BIGINT(13)     DEFAULT NULL,
  `VariantEAN2`          BIGINT(13)     DEFAULT NULL,
  `VariantEAN3`          BIGINT(13)     DEFAULT NULL,
  `VariantEAN4`          BIGINT(13)     DEFAULT NULL,
  `WarehouseType`        VARCHAR(45)    DEFAULT NULL,
  PRIMARY KEY (`ItemID`, `PriceID`, `AttributeValueSetID`, `WarehouseID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

CREATE TABLE IF NOT EXISTS `db473835270`.`CalculatedDailyNeeds` (
  `ItemID`              INT(11) NOT NULL,
  `AttributeValueSetID` INT(11) NOT NULL,
  `DailyNeed`           DECIMAL(8, 2) DEFAULT NULL,
  `LastUpdate`          INT(11)       DEFAULT NULL,
  `SkippedA`            INT(11)       DEFAULT NULL,
  `QuantitiesA`         TEXT          DEFAULT NULL,
  `SkippedB`            INT(11)       DEFAULT NULL,
  `QuantitiesB`         TEXT          DEFAULT NULL,
  `New`                 TINYINT(1)    DEFAULT 0,
  PRIMARY KEY (`ItemID`, `AttributeValueSetID`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;