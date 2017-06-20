<?php

class CalculateWriteBackPermissions
{
	private $verbose = true;

	private $writeBackPermissions = [];

	public function execute()
	{
		$this->getLogger()->debug( __FUNCTION__.' : determine write permissions' );

		$dbResult = DBQuery::getInstance()->select( $this->getQuery() );

		// for every item variant ...
		while( $currentArticleVariant = $dbResult->fetchAssoc() )
		{
			// ... store ItemID, AVSID, Marking1ID and corresponding WritePermission
			$result = [
				'ItemID'              => $currentArticleVariant['ItemID'],
				'AttributeValueSetID' => $currentArticleVariant['AttributeValueSetID'],
			];

			// if item variant is no bundle and has black/green marking or yellow marking with positive reorder level ...
			if( ($currentArticleVariant['BundleType'] !== 'bundle') && ((intval( $currentArticleVariant['Marking1ID'] ) == 16) || (intval( $currentArticleVariant['Marking1ID'] ) == 20) || (intval( $currentArticleVariant['Marking1ID'] ) == 9) && (intval( $currentArticleVariant['ReorderLevel'] ) > 0)) )
			{
				// ... then it has write permission
				$result['WritePermission'] = 1;
			}
			else
			{
				// ... otherwise not
				$result['WritePermission'] = 0;
			}

			// if write permission given, but there's an error ... (like no supplier delivery time, no stock turnover, or it's a malformed article variant (SupplierMinimumPurchase != 0) or a bundle article)
			if( (intval( $result['WritePermission'] ) == 1) && ((intval( $currentArticleVariant['SupplierDeliveryTime'] ) <= 0) || (intval( $currentArticleVariant['StockTurnover'] ) <= 0)) || ((intval( $currentArticleVariant['AttributeValueSetID'] ) !== 0) && ((intval( $currentArticleVariant['SupplierMinimumPurchase'] ) !== 0))) )
			{
				// ... then revoke write permission and set error
				$result['WritePermission'] = 0;
				$result['Error']           = 1;
			}
			else
			{
				// ... otherwise everything's ok
				$result['Error'] = 0;
			}

			$this->writeBackPermissions[] = $result;
		}

		$this->storeToDB();
	}

	private function storeToDB()
	{
		$countWritePermissions = count( $this->writeBackPermissions );
		if( $countWritePermissions )
		{
			$this->debug( __FUNCTION__." storing $countWritePermissions records of write permissions" );
			DBQuery::getInstance()->insert( 'INSERT INTO `WriteBackPermissions`'.DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->writeBackPermissions ) );
		}
	}

	private function getQuery()
	{
		return "SELECT
  ItemsBase.ItemID,
  ItemsBase.Marking1ID,
  ItemsBase.BundleType,
  CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS NULL)
    THEN
      '0'
  ELSE
    ItemAttributeValueSets.AttributeValueSetID
  END AttributeValueSetID,
  CASE WHEN (ItemsWarehouseSettings.StockTurnover IS NULL)
    THEN
      '0'
  ELSE
    ItemsWarehouseSettings.StockTurnover
  END StockTurnover,
  CASE WHEN (ItemsWarehouseSettings.ReorderLevel IS NULL)
    THEN
      '0'
  ELSE
    ItemsWarehouseSettings.ReorderLevel
  END ReorderLevel,
  ItemsSuppliers.SupplierDeliveryTime,
  ItemsSuppliers.SupplierMinimumPurchase
FROM
  ItemsBase
  LEFT JOIN ItemAvailability ON ItemsBase.ItemID = ItemAvailability.ItemID
  LEFT JOIN ItemAttributeValueSets ON ItemsBase.ItemID = ItemAttributeValueSets.ItemID
  LEFT JOIN ItemsSuppliers ON ItemsBase.ItemID = ItemsSuppliers.ItemID
  LEFT JOIN ItemsWarehouseSettings
    ON ItemsBase.ItemID = ItemsWarehouseSettings.ItemID AND
       CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS NULL)
         THEN
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
