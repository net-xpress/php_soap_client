<?php
require_once realpath( dirname( __FILE__ ).'/../' ).'/config/basic.inc.php';
require_once ROOT.'lib/soap/example_loader/PlentymarketsSoapExampleLoader.class.php';
require_once ROOT.'scripts/import/ImportJansenStock.class.php';
require_once ROOT.'scripts/import/MatchJansenToNx.class.php';

/**
 * This script performs the 'jansen stock difference update job': Read stock data from csv-file, match jansen articles
 * against net-xpress articles, write differences back to plenty. Remark: Just writing differences prevents updating all
 * matched articles
 */

// locally, read and evaluate csv file,
// update:	JansenTransactionHead, JansenTransactionItem, JansenStockData
// rely:	MetaConfig
(new ImportJansenStock( '/kunden/homepages/22/d66025481/htdocs/stock_jd/stock.csv' ))->execute();

// locally, match jansen articles against net-xpress articles ,
// update:	SetCurrentStocks, JansenStockUnmatched
// rely:	JansenTransactionHead, JansenTransactionItem, JansenStockData, ItemsBase, ItemAttributeValueSets, PriceSets, ItemAvailability
(new MatchJansenToNx())->execute();
