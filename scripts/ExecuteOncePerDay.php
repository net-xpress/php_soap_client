<?php
require_once realpath( dirname( __FILE__ ).'/../' ).'/config/basic.inc.php';
require_once ROOT.'lib/soap/example_loader/PlentymarketsSoapExampleLoader.class.php';
require_once ROOT.'scripts/calculation/CalculateDailyNeed.class.php';

// update order databases: OrderHead and OrderItem
PlentymarketsSoapExampleLoader::getInstance()->run( ['', 'SearchOrders',] );

// update item databases: ItemsBase, ItemAvailability, ItemCategories, ItemFreeTextFields, ItemTexts, ItemOthers, ItemStock
PlentymarketsSoapExampleLoader::getInstance()->run( ['', 'GetItemsBase',] );

// calculate daily need
(new CalculateDailyNeed())->execute();
