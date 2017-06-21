<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT.'lib/soap/tools/SKUHelper.php';
require_once 'RequestContainer_GetItemsWarehouseSettings.class.php';

class SoapCall_GetItemsWarehouseSettings extends PlentySoapCall
{
	const MAX_SKU_PER_PAGE = 100;

	private $aItemsWarehouseSettings = [];
	private $warehouseID             = 1;

	public function __construct()
	{
		parent::__construct( __CLASS__ );
	}

	public function execute()
	{
		$this->getLogger()->info( __FUNCTION__.' Fetching items warehouse settings from plenty' );

		try
		{
			// get all possible SKUs
			$oDBResult = DBQuery::getInstance()->select( $this->getSKUQuery() );

			// for every 100 SKUs ...
			for( $page = 0, $maxPages = ceil( $oDBResult->getNumRows() / self::MAX_SKU_PER_PAGE ); $page < $maxPages; $page++ )
			{
				// ... prepare a seperate request
				$preparedRequest = new RequestContainer_GetItemsWarehouseSettings( $this->warehouseID, self::MAX_SKU_PER_PAGE );
				while( !$preparedRequest->isFull() && $current = $oDBResult->fetchAssoc() )
				{
					$preparedRequest->add( $current['SKU'] );
				}

				// ... then do soap call ...
				$response = $this->getPlentySoap()->GetItemsWarehouseSettings( $preparedRequest->getRequest() );

				// ... if successfull ...
				if( ($response->Success == true) )
				{

					// ... then process response
					$this->responseInterpretation( $response );
				}
				else
				{

					// ... otherwise log error and try next request
					$this->debug( __FUNCTION__." Request Error" );
				}
			}

			// when done store all retrieved data to db
			$this->storeToDB();

		} catch( Exception $e )
		{
			$this->onExceptionAction( $e );
		}
	}

	/**
	 * @return string SQL-Query to get all pairs of ItemID -> AttributeValueSetID
	 */
	private function getSKUQuery()
	{
		return "SELECT CONCAT(
           i.ItemID,
           '-0-',
           CASE WHEN (avs.AttributeValueSetID IS NULL)
             THEN
               '0'
           ELSE
             avs.AttributeValueSetID
           END
       ) AS SKU
FROM
  ItemsBase AS i
  LEFT JOIN
  ItemAttributeValueSets AS avs
    ON
      i.ItemID = avs.ItemID
ORDER BY i.ItemID";
	}

	/**
	 * @param PlentySoapResponse_GetItemsWarehouseSettings $response
	 */
	private function responseInterpretation(PlentySoapResponse_GetItemsWarehouseSettings $response)
	{
		if( isset( $response->ItemList ) && is_array( $response->ItemList->item ) )
		{

			/** @noinspection PhpParamsInspection */
			$countRecords = count( $response->ItemList->item );
			$this->debug( __FUNCTION__." fetched $countRecords warehouse setting records from SKU: {$response->ItemList->item[0]->SKU} to {$response->ItemList->item[$countRecords - 1]->SKU}" );

			foreach( $response->ItemList->item as &$warehouseSetting )
			{
				$this->processWarehouseSetting( $warehouseSetting );
			}
		}
		else
		{
			if( isset( $response->ItemList ) )
			{
				$this->debug( __FUNCTION__." fetched warehouse setting records for SKU: {$response->ItemList->item->SKU}" );

				$this->processWarehouseSetting( $response->ItemList->item );
			}
			else
			{
				$this->debug( __FUNCTION__." fetched no warehouse setting records for current request" );
			}
		}
	}

	/**
	 * @param PlentySoapObject_ResponseGetItemsWarehouseSettings $warehouseSetting
	 */
	private function processWarehouseSetting($warehouseSetting)
	{
		list( $ItemID, , $AttributeValueSetID ) = SKU2Values( $warehouseSetting->SKU );

		$this->aItemsWarehouseSettings[] = array(
			'ID'                  => $warehouseSetting->ID,
			'MaximumStock'        => $warehouseSetting->MaximumStock,
			'ReorderLevel'        => $warehouseSetting->ReorderLevel,
			/*  'SKU'					=>	$oWarehouseSetting->SKU,	// replaced with ItemID in combination with AVSI */
			'ItemID'              => $ItemID,
			'AttributeValueSetID' => $AttributeValueSetID,
			/*
			 * 	End of SKU replacement
			 */
			'StockBuffer'         => $warehouseSetting->StockBuffer,
			'StockTurnover'       => $warehouseSetting->StockTurnover,
			'StorageLocation'     => $warehouseSetting->StorageLocation,
			'StorageLocationType' => $warehouseSetting->StorageLocationType,
			'WarehouseID'         => $warehouseSetting->WarehouseID,
			'Zone'                => $warehouseSetting->Zone,
		);
	}

	private function storeToDB()
	{
		$countItemsWarehouseSettings = count( $this->aItemsWarehouseSettings );

		if( $countItemsWarehouseSettings > 0 )
		{
			$this->getLogger()->info( __FUNCTION__." storing $countItemsWarehouseSettings records of ItemsWarehouseSettings" );
			DBQuery::getInstance()->insert( "INSERT INTO `ItemsWarehouseSettings`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->aItemsWarehouseSettings ) );
		}
	}

}
