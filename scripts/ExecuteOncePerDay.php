<?php
require_once realpath( dirname( __FILE__ ).'/../' ).'/config/basic.inc.php';
require_once ROOT.'lib/soap/example_loader/PlentymarketsSoapExampleLoader.class.php';
require_once ROOT.'scripts/calculation/CalculateDailyNeed.class.php';
require_once ROOT.'scripts/calculation/CalculateWriteBackSuggestions.class.php';
require_once ROOT.'scripts/calculation/CalculateWriteBackPermissions.class.php';

/**
 * This script performs the 'daily job': Update all necessary tables and calculate data to be displayed in the 'stock-tool'
 */

// via plenty soap,
// update:	OrderHead, OrderItem
// rely:	MetaLastUpdate, plenty_soap_token
PlentymarketsSoapExampleLoader::getInstance()->run( ['', 'SearchOrders',] );

// via plenty soap,
// update:	ItemsBase, ItemAvailability, ItemCategories, ItemAttributeValueSets, ItemFreeTextFields, ItemTexts, ItemOthers, ItemStock
// rely:	MetaLastUpdate, plenty_soap_token
PlentymarketsSoapExampleLoader::getInstance()->run( ['', 'GetItemsBase',] );

// locally, calculate daily need,
// update:	CalculatedDailyNeed
// rely:	OrderHead, ItemsBase, ItemFreeTextFields, ItemAvailability, MetaConfig
(new CalculateDailyNeed())->execute();

// via plenty soap,
// update:	ItemsWarehouseSettings
// rely:	ItemsBase, ItemAttributeValueSets, plenty_soap_token
PlentymarketsSoapExampleLoader::getInstance()->run( ['', 'GetItemsWarehouseSettings',] );

// via plenty soap,
// update:	ItemsSuppliers
// rely:	ItemsBase, plenty_soap_token
PlentymarketsSoapExampleLoader::getInstance()->run( ['', 'GetItemsSuppliers',] );

// via plenty soap,
// update:	WarehouseList
// rely:	plenty_soap_token
PlentymarketsSoapExampleLoader::getInstance()->run( ['', 'GetWarehouseList',] );

// via plenty soap:
// update:	CurrentStocks
// rely:	WarehouseList, MetaLastUpdate, plenty_soap_token
PlentymarketsSoapExampleLoader::getInstance()->run( ['', 'GetCurrentStocks',] );

// locally, calculate write back suggestions
// update:	WriteBackSuggestion
// rely:	ItemsBase, ItemFreeTextFields, ItemsSuppliers, CalculatedDailyNeed, ItemsWarehouseSettings, ItemAttributeValueSets, ItemAvailability, plenty_soap_token
(new CalculateWriteBackSuggestions())->execute();

// locally, calculate write back permissions
// update:	WriteBackPermissions
// rely:	ItemsBase, ItemAttributeValueSets, ItemsSuppliers, ItemsWarehouseSettings, ItemAvailability
(new CalculateWriteBackPermissions())->execute();

// via plenty soap:
// update:	ItemsPriceSets
// rely:	ItemsBase, ItemAttributeValueSets, plenty_soap_token
PlentymarketsSoapExampleLoader::getInstance()->run( ['', 'GetItemsPriceList',] );
