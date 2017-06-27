<?php
require_once realpath( dirname( __FILE__ ).'/../../' ).'/config/basic.inc.php';
require_once ROOT.'/lib/db/DBQuery.class.php';
require_once ROOT.'lib/soap/tools/SKUHelper.php';

class StockAssembly
{
	const STOCK_DATA_SELECT_BASIC = "SELECT
    ItemsBase.ItemID,
	CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS null) THEN
		'0'
	ELSE
		ItemAttributeValueSets.AttributeValueSetID
	END AttributeValueSetID";

	const STOCK_DATA_SELECT_ADVANCED = ",
	CONCAT(CASE WHEN (CalculatedDailyNeeds.New = 1) THEN
			'[Neu] '
		ELSE
			''
		END,CASE WHEN (ItemsBase.BundleType = 'bundle') THEN
			'[Bundle] '
		WHEN (ItemsBase.BundleType = 'bundle_item') THEN
			'[Bundle Artikel] '
		ELSE
			''
		END, ItemTexts.Name, CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS NOT null) THEN
			CONCAT(', ', ItemAttributeValueSets.AttributeValueSetName)
		ELSE
			''
	END) AS Name,
	ItemTexts.Name AS SortName,
	ItemsBase.ItemNo,
	ItemsBase.Marking1ID,
	ItemFreeTextFields.Free4 AS VPE,
	ItemsBase.BundleType,
	CalculatedDailyNeeds.DailyNeed,
	CalculatedDailyNeeds.LastUpdate,
	CalculatedDailyNeeds.QuantitiesA,
	CalculatedDailyNeeds.SkippedA,
	CalculatedDailyNeeds.QuantitiesB,
	CalculatedDailyNeeds.SkippedB,
	CalculatedDailyNeeds.New,
	ItemsWarehouseSettings.ReorderLevel,
	ItemsWarehouseSettings.StockTurnover,
	ItemsWarehouseSettings.MaximumStock,
	ItemsSuppliers.SupplierDeliveryTime,
	ItemsSuppliers.SupplierMinimumPurchase,
	WriteBackPermissions.WritePermission,
	WriteBackPermissions.Error AS WritePermissionError,
	WriteBackSuggestions.Valid,
    WriteBackSuggestions.ReorderLevelError,
    WriteBackSuggestions.SupplierMinimumPurchaseError,
    WriteBackSuggestions.ReorderLevel AS ProposedReorderLevel,
    WriteBackSuggestions.SupplierMinimumPurchase AS ProposedSupplierMinimumPurchase,
    WriteBackSuggestions.MaximumStock  AS ProposedMaximumStock,
    CurrentStocks.NetStock";

	const STOCK_DATA_FROM_BASIC = "
FROM ItemsBase
LEFT JOIN ItemAvailability
	ON ItemsBase.ItemID = ItemAvailability.ItemID
LEFT JOIN ItemAttributeValueSets
	ON ItemsBase.ItemID = ItemAttributeValueSets.ItemID
LEFT JOIN ItemsWarehouseSettings
    ON ItemsBase.ItemID = ItemsWarehouseSettings.ItemID
    AND CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS null) THEN
        '0'
    ELSE
        ItemAttributeValueSets.AttributeValueSetID
    END = ItemsWarehouseSettings.AttributeValueSetID";

	const STOCK_DATA_FROM_ADVANCED = "
LEFT JOIN ItemTexts
	ON ItemsBase.ItemID = ItemTexts.ItemID
LEFT JOIN ItemFreeTextFields
	ON ItemsBase.ItemID = ItemFreeTextFields.ItemID
LEFT JOIN CalculatedDailyNeeds
    ON ItemsBase.ItemID = CalculatedDailyNeeds.ItemID
    AND CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS null) THEN
        '0'
    ELSE
        ItemAttributeValueSets.AttributeValueSetID
    END = CalculatedDailyNeeds.AttributeValueSetID
LEFT JOIN ItemsSuppliers
	ON ItemsBase.ItemID = ItemsSuppliers.ItemID
LEFT JOIN WriteBackPermissions
    ON ItemsBase.ItemID = WriteBackPermissions.ItemID
    AND CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS null) THEN
        '0'
    ELSE
        ItemAttributeValueSets.AttributeValueSetID
    END = WriteBackPermissions.AttributeValueSetID
LEFT JOIN WriteBackSuggestions
    ON ItemsBase.ItemID = WriteBackSuggestions.ItemID
    AND CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS null) THEN
        '0'
    ELSE
        ItemAttributeValueSets.AttributeValueSetID
    END = WriteBackSuggestions.AttributeValueSetID
LEFT JOIN CurrentStocks
	ON ItemsBase.ItemID = CurrentStocks.ItemID
	AND CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS null) THEN
        '0'
    ELSE
        ItemAttributeValueSets.AttributeValueSetID
    END = CurrentStocks.AttributeValueSetID
    AND CurrentStocks.WarehouseID = 1";

	const STOCK_DATA_WHERE = "
WHERE
	ItemAvailability.Inactive = 0";

	/**
	 * @param int $page
	 * @param int $rowsPerPage
	 * @param string $sortByColumn
	 * @param string $sortOrder
	 * @param mixed $aItemIDs
	 * @param mixed $aItemNos
	 * @param mixed $aItemNames
	 * @param mixed $aMarking1IDs
	 * @return array
	 */
	public static function getStockData($page = 1, $rowsPerPage = 10, $sortByColumn = 'ItemID', $sortOrder = 'ASC', $aItemIDs = null, $aItemNos = null, $aItemNames = null, $aMarking1IDs = null)
	{
		$aStockData = array('page' => $page, 'total' => null, 'rows' => array());

		// prepare where condition
		$filterCondition = self::prepareStockDataFilterCondition( $aItemIDs, $aItemNos, $aItemNames, $aMarking1IDs );

		ob_start();
		// get total
		$aStockData['total'] = DBQuery::getInstance()->select( self::STOCK_DATA_SELECT_BASIC.self::STOCK_DATA_FROM_BASIC.self::STOCK_DATA_WHERE.$filterCondition )->getNumRows();

		// prepare current page
		//TODO check for empty values to prevent errors!
		$sort  = 'ORDER BY '.self::sanitizeStockDataSortingColumn( $sortByColumn )." $sortOrder\n";
		$start = (($page - 1) * $rowsPerPage);
		$limit = "LIMIT $start,$rowsPerPage";

		//die(self::STOCK_DATA_SELECT_BASIC . self::STOCK_DATA_SELECT_ADVANCED . self::STOCK_DATA_FROM_BASIC . self::STOCK_DATA_FROM_ADVANCED . self::STOCK_DATA_WHERE . $filterCondition . $sort . $limit);
		$aStockDataDBResult = DBQuery::getInstance()->select( self::STOCK_DATA_SELECT_BASIC.self::STOCK_DATA_SELECT_ADVANCED.self::STOCK_DATA_FROM_BASIC.self::STOCK_DATA_FROM_ADVANCED.self::STOCK_DATA_WHERE.$filterCondition.$sort.$limit );
		ob_end_clean();

		while( $aStockDataRow = $aStockDataDBResult->fetchAssoc() )
		{
			$aStockData['rows'][] = self::processStockDataRow( $aStockDataRow );
		}

		return $aStockData;
	}

	/**
	 * @param mixed $aItemIDs
	 * @param mixed $aItemNos
	 * @param mixed $aItemNames
	 * @param mixed $aMarking1IDs
	 * @return string
	 */
	private static function prepareStockDataFilterCondition($aItemIDs, $aItemNos, $aItemNames, $aMarking1IDs)
	{
		$filterCondition = "\n";
		// prepare filter conditions
		if( !is_null( $aMarking1IDs ) )
		{
			if( !is_array( $aMarking1IDs ) )
			{
				$aMarking1IDs = array($aMarking1IDs);
			}
			$filterCondition .= "AND\n\tItemsBase.Marking1ID IN  (".implode( ',', $aMarking1IDs ).")\n";
		}

		if( !is_null( $aItemIDs ) )
		{
			if( !is_array( $aItemIDs ) )
			{
				$aItemIDs = array($aItemIDs);
			}
			$filterCondition .= "AND\n\tItemsBase.ItemID IN (".implode( ',', $aItemIDs ).")\n";
		}
		else if( !is_null( $aItemNos ) )
		{
			if( !is_array( $aItemNos ) )
			{
				$aItemNos = array($aItemNos);
			}
			$filterCondition .= "AND\n\tItemsBase.ItemNo REGEXP '^".implode( '|^', $aItemNos )."'\n";
		}
		else if( !is_null( $aItemNames ) )
		{
			if( !is_array( $aItemNames ) )
			{
				$aItemNames = array($aItemNames);
			}

			foreach( $aItemNames as $name )
			{
				$filterCondition .= "AND\n\tCONCAT(
		CASE WHEN (ItemsBase.BundleType = \"bundle\") THEN
			\"[Bundle] \"
		WHEN (ItemsBase.BundleType = \"bundle_item\") THEN
			\"[Bundle Artikel] \"
		ELSE
			\"\"
		END,
		ItemTexts.Name,
		CASE WHEN (ItemAttributeValueSets.AttributeValueSetID IS NOT null) THEN
			CONCAT(\", \", ItemAttributeValueSets.AttributeValueSetName)
		ELSE
			\"\"
		END\n\t) LIKE \"%$name%\"\n";
			}
		}

		return $filterCondition;
	}

	private static function sanitizeStockDataSortingColumn($sortByColumn)
	{
		switch( $sortByColumn )
		{
			case 'ItemID' :
			case 'ItemNo' :
			case 'DailyNeed' :
			case 'Marking1ID' :
				return $sortByColumn;
				break;
			case 'Name' :
				return 'Sortname';
			case 'MonthlyNeed' :
				return 'DailyNeed';
			case 'Date' :
				return 'LastUpdate';
			case 'CurrentStock' :
				return 'CASE
	WHEN
		WriteBackSuggestions.MaximumStock IS NOT NULL AND
		NetStock IS NOT NULL AND
		NetStock > WriteBackSuggestions.MaximumStock
	THEN
		5000000 + NetStock #means it`s a red value
	WHEN
		NetStock IS NOT NULL AND
		(DailyNeed IS NOT NULL AND NetStock > 30 * DailyNeed) OR
		(DailyNeed IS NULL AND NetStock > 0)
	THEN
		4000000 + NetStock
	WHEN
		NetStock IS NOT NULL AND
		NetStock != 0
	THEN
		3000000 + NetStock
	ELSE
		NetStock
	END';
			default :
				throw new RuntimeException( "Unknown sort name: $sortByColumn" );
		}
	}

	private static function processStockDataRow(array $aStockDataRow)
	{
		$sku = Values2SKU( $aStockDataRow['ItemID'], $aStockDataRow['AttributeValueSetID'] );
		return [
			'rowID'         => $sku,
			'itemID'        => intval( $aStockDataRow['ItemID'] ),
			'itemNo'        => $aStockDataRow['ItemNo'],
			'name'          => $aStockDataRow['Name'],
			'rawData'       => [
				'A' => [
					'isValid'    => isset( $aStockDataRow['QuantitiesA'] ) && $aStockDataRow['QuantitiesA'] !== '0',
					'skipped'    => intval( $aStockDataRow['SkippedA'] ),
					'quantities' => array_map( 'intval', explode( ',', $aStockDataRow['QuantitiesA'] ) ),
				],
				'B' => [
					'isValid'    => isset( $aStockDataRow['QuantitiesB'] ) && $aStockDataRow['QuantitiesB'] !== '0',
					'skipped'    => intval( $aStockDataRow['SkippedB'] ),
					'quantities' => array_map( 'intval', explode( ',', $aStockDataRow['QuantitiesB'] ) ),
				],
			],
			'dailyNeed'     => floatval( $aStockDataRow['DailyNeed'] ),
			'currentStock'  => intval( $aStockDataRow['NetStock'] ),
			'marking1ID'    => intval( $aStockDataRow['Marking1ID'] ),
			'writeBackData' => [
				'isWritingPermitted'      => intval( $aStockDataRow['WritePermission'] ) === 1,
				'reorderLevel'            => [
					'old'     => intval( $aStockDataRow['ReorderLevel'] ),
					'current' => isset( $aStockDataRow['ProposedReorderLevel'] ) ? intval( $aStockDataRow['ProposedReorderLevel'] ) : null,
					'error'   => isset( $aStockDataRow['ReorderLevelError'] ) ? 'Lieferzeit nicht konfiguriert' : null,
				],
				'maxStockSuggestion'      => [
					'old'     => intval( $aStockDataRow['MaximumStock'] ),
					'current' => isset( $aStockDataRow['ProposedMaximumStock'] ) ? intval( $aStockDataRow['ProposedMaximumStock'] ) : null,
					'error'   => isset( $aStockDataRow['SupplierMinimumPurchaseError'] ) ? 'Lagerreichweite nicht konfiguriert' : null,
				],
				'supplierMinimumPurchase' => [
					'old'     => intval( $aStockDataRow['SupplierMinimumPurchase'] ),
					'current' => isset( $aStockDataRow['ProposedSupplierMinimumPurchase'] ) ? intval( $aStockDataRow['ProposedSupplierMinimumPurchase'] ) : null,
					'error'   => isset( $aStockDataRow['SupplierMinimumPurchaseError'] ) ? 'Lagerreichweite nicht konfiguriert' : null,
				],
			],
			'vpe'           => $aStockDataRow['VPE'] == 0 ? 1 : intval( $aStockDataRow['VPE'] ),
			'lastUpdate'    => isset( $aStockDataRow['LastUpdate'] ) ? date( 'd.m.y, H:i:s', $aStockDataRow['LastUpdate'] ) : null,
		];
	}
}