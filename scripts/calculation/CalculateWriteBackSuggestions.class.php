<?php
require_once realpath( dirname( __FILE__ ).'/../../' ).'/config/basic.inc.php';
require_once ROOT.'lib/db/DBQuery.class.php';
require_once ROOT.'lib/log/Logger.class.php';

class CalculateWriteBackSuggestions
{
	private $verbose = true;

	private $writeBackSuggestions = [];

	public function execute()
	{
		$this->debug( __FUNCTION__.' : Calculating write back suggestions' );

		$dbResult = DBQuery::getInstance()->select( $this->getQuery() );

		// for every item variant ...
		while( $currentArticleVariant = $dbResult->fetchAssoc() )
		{
			$dailyNeed               = floatval( $currentArticleVariant['DailyNeed'] );
			$supplierDeliveryTime    = intval( $currentArticleVariant['SupplierDeliveryTime'] );
			$stockTurnover           = intval( $currentArticleVariant['StockTurnover'] );
			$vpe                     = intval( $currentArticleVariant['VPE'] );
			$vpe                     = $vpe == 0 ? 1 : $vpe;
			$supplierMinimumPurchase = ceil( $stockTurnover * $dailyNeed );
			$supplierMinimumPurchase = ($supplierMinimumPurchase % $vpe == 0) && ($supplierMinimumPurchase != 0) ? $supplierMinimumPurchase : $supplierMinimumPurchase + $vpe - $supplierMinimumPurchase % $vpe;

			$result = [
				'ItemID'              => $currentArticleVariant['ItemID'],
				'AttributeValueSetID' => $currentArticleVariant['AttributeValueSetID'],
				'Valid'               => 1,
			];

			// if supplier delivery time given ...
			if( $supplierDeliveryTime !== 0 )
			{
				// ... then calculate reorder level suggestion
				$result['ReorderLevel']      = round( $supplierDeliveryTime * $dailyNeed );
				$result['ReorderLevelError'] = 'NULL';
			}
			else
			{
				// ... otherwise invalidate record
				$result['Valid']             = 0;
				$result['ReorderLevel']      = 'NULL';
				$result['ReorderLevelError'] = 'liefer';
			}

			// if stock turnover given ...
			if( $stockTurnover !== 0 )
			{
				// ... then calculate supplier minimum purchase and maximum stock

				// ... but skip SupplierMinimumPurchase for article variants
				if( intval( $currentArticleVariant['AttributeValueSetID'] ) === 0 )
				{
					$result['SupplierMinimumPurchase'] = $supplierMinimumPurchase;
				}
				else
				{
					$result['SupplierMinimumPurchase'] = 0;
				}
				$result['MaximumStock']                 = 2 * $supplierMinimumPurchase;
				$result['SupplierMinimumPurchaseError'] = 'NULL';
			}
			else
			{
				// ... otherwise invalidate record
				$result['Valid']                        = 0;
				$result['SupplierMinimumPurchase']      = 'NULL';
				$result['MaximumStock']                 = 'NULL';
				$result['SupplierMinimumPurchaseError'] = 'lager';
			}
			$this->writeBackSuggestions[] = $result;
		}

		$this->storeToDB();
	}

	private function storeToDB()
	{
		$countWriteBackSuggestions = count( $this->writeBackSuggestions );

		if( $countWriteBackSuggestions > 0 )
		{
			$this->debug( __FUNCTION__." storing $countWriteBackSuggestions records of write back suggestions" );
			DBQuery::getInstance()->insert( 'INSERT INTO `WriteBackSuggestions`'.DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->writeBackSuggestions ) );
		}
	}

	private function getQuery()
	{
		return "SELECT
    ItemsBase.ItemID,
    ItemFreeTextFields.Free4 AS VPE,
    ItemsSuppliers.SupplierDeliveryTime,
    CalculatedDailyNeeds.DailyNeed,
    ItemsWarehouseSettings.StockTurnover,
    CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS NULL) THEN
        '0'
    ELSE
        ItemAttributeValueSets.AttributeValueSetID
    END AttributeValueSetID
FROM ItemsBase
LEFT JOIN ItemFreeTextFields
  ON ItemsBase.ItemID = ItemFreeTextFields.ItemID
LEFT JOIN ItemAvailability
  ON ItemsBase.ItemID = ItemAvailability.ItemID
LEFT JOIN ItemAttributeValueSets
    ON ItemsBase.ItemID = ItemAttributeValueSets.ItemID
LEFT JOIN ItemsSuppliers
    ON ItemsBase.ItemID = ItemsSuppliers.ItemID
LEFT JOIN CalculatedDailyNeeds
    ON ItemsBase.ItemID = CalculatedDailyNeeds.ItemID
    AND CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS NULL) THEN
        '0'
    ELSE
        ItemAttributeValueSets.AttributeValueSetID
    END = CalculatedDailyNeeds.AttributeValueSetID
LEFT JOIN ItemsWarehouseSettings
    ON ItemsBase.ItemID = ItemsWarehouseSettings.ItemID
    AND CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS NULL) THEN
        '0'
    ELSE
        ItemAttributeValueSets.AttributeValueSetID
    END = ItemsWarehouseSettings.AttributeValueSetID
WHERE
	ItemAvailability.Inactive = 0";
	}

	protected function debug($message)
	{
		if( $this->verbose === true )
		{
			$this->getLogger()->debug( $message );
		}
	}

	protected function getLogger()
	{
		return Logger::instance( __CLASS__ );
	}
}
