<?php
require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT.'lib/soap/tools/SKUHelper.php';
require_once 'Request_GetCurrentStocks.class.php';

class SoapCall_GetCurrentStocks extends PlentySoapCall
{
	private $page              = 0;
	private $pages             = -1;
	private $plentySoapRequest = null;

	private $startAtPage  = 0;
	private $stockRecords = [];

	public function __construct()
	{
		parent::__construct( __CLASS__ );
	}

	public function execute()
	{
		$dbResult = DBQuery::getInstance()->select( $this->getQuery() );

		while( $warehouse = $dbResult->fetchAssoc() )
		{
			$this->page        = 0;
			$this->pages       = -1;
			$this->startAtPage = 0;
			$this->executeForWarehouse( $warehouse['WarehouseID'] );
		}
	}

	/**
	 * @param int $warehouseId
	 */
	private function executeForWarehouse($warehouseId)
	{
		list( $lastUpdate, $currentTime, $this->startAtPage ) = DBUtils::lastUpdateStart( __CLASS__."_for_wh_$warehouseId" );

		if( $this->pages == -1 )
		{
			try
			{
				$this->plentySoapRequest = Request_GetCurrentStocks::getRequest( $lastUpdate, $this->startAtPage, $warehouseId );

				if( $this->startAtPage > 0 )
				{
					$this->debug( __FUNCTION__." Starting at page ".$this->startAtPage );
				}

				/*
				 * do soap call
				 */
				$response = $this->getPlentySoap()->GetCurrentStocks( $this->plentySoapRequest );

				if( ($response->Success == true) && isset( $response->CurrentStocks ) )
				{
					// request successful, processing data..

					/** @noinspection PhpParamsInspection */
					$stocksFound = count( $response->CurrentStocks->item );
					$pagesFound  = $response->Pages;

					$this->debug( __FUNCTION__.' Request Success - stock records found : '.$stocksFound.' / pages : '.$pagesFound.', page : '.($this->page + 1) );

					// process response
					$this->responseInterpretation( $response );

					if( $pagesFound > $this->page )
					{
						$this->page  = $this->startAtPage + 1;
						$this->pages = $pagesFound;

						DBUtils::lastUpdatePageUpdate( __CLASS__."_for_wh_$warehouseId", $this->page );
						$this->executePagesForWarehouse( $warehouseId );
					}
				}
				else
				{
					if( ($response->Success == true) && !isset( $response->CurrentStocks ) )
					{
						// request successful, but no data to process
						$this->debug( __FUNCTION__.' Request Success -  but no matching stock records found' );
					}
					else
					{
						$this->debug( __FUNCTION__.' Request Error' );
					}
				}
			} catch( Exception $e )
			{
				$this->onExceptionAction( $e );
			}
		}
		else
		{
			$this->executePagesForWarehouse( $warehouseId );
		}

		$this->storeToDB();
		DBUtils::lastUpdateFinish( $currentTime, __CLASS__."_for_wh_$warehouseId" );
	}

	private function responseInterpretation(PlentySoapResponse_GetCurrentStocks $response)
	{
		if( is_array( $response->CurrentStocks->item ) )
		{
			foreach( $response->CurrentStocks->item AS $stockRecord )
			{
				$this->processStockRecord( $stockRecord );
			}
		}
		else
		{
			$this->processStockRecord( $response->CurrentStocks->item );
		}
	}

	/**
	 * @param PlentySoapObject_GetCurrentStocks $stockRecord
	 */
	private function processStockRecord($stockRecord)
	{
		list( $itemID, $priceId, $attributeValueSetId ) = SKU2Values( $stockRecord->SKU );

		$this->stockRecords[] = [
			'ItemID'               => $itemID,
			'PriceID'              => $priceId,
			'AttributeValueSetID'  => $attributeValueSetId,
			'WarehouseID'          => $stockRecord->WarehouseID,
			'EAN'                  => $stockRecord->EAN,
			'EAN2'                 => $stockRecord->EAN2,
			'EAN3'                 => $stockRecord->EAN3,
			'EAN4'                 => $stockRecord->EAN4,
			'VariantEAN'           => $stockRecord->VariantEAN,
			'VariantEAN2'          => $stockRecord->VariantEAN2,
			'VariantEAN3'          => $stockRecord->VariantEAN3,
			'VariantEAN4'          => $stockRecord->VariantEAN4,
			'WarehouseType'        => $stockRecord->WarehouseType,
			'StorageLocationID'    => $stockRecord->StorageLocationID,
			'StorageLocationName'  => $stockRecord->StorageLocationName,
			'StorageLocationStock' => $stockRecord->StorageLocationStock,
			'PhysicalStock'        => $stockRecord->PhysicalStock,
			'NetStock'             => $stockRecord->NetStock,
			'AveragePrice'         => $stockRecord->AveragePrice,
		];
	}

	/**
	 * @param int $warehouseId
	 */
	private function executePagesForWarehouse($warehouseId)
	{
		while( $this->pages > $this->page )
		{
			$this->plentySoapRequest->Page = $this->page;
			try
			{
				$response = $this->getPlentySoap()->GetCurrentStocks( $this->plentySoapRequest );

				if( $response->Success == true )
				{
					/** @noinspection PhpParamsInspection */
					$stocksFound = count( $response->CurrentStocks->item );
					$this->debug( __FUNCTION__.' Request Success - stock records found : '.$stocksFound.' / page : '.($this->page + 1) );

					// auswerten
					$this->responseInterpretation( $response );
				}

				$this->page++;
				DBUtils::lastUpdatePageUpdate( __CLASS__."_for_wh_$warehouseId", $this->page );

			} catch( Exception $e )
			{
				$this->onExceptionAction( $e );
			}
		}
	}

	private function storeToDB()
	{
		// insert stock records
		$countStockRecords = count( $this->stockRecords );

		if( $countStockRecords > 0 )
		{
			$this->getLogger()->info( __FUNCTION__." : storing $countStockRecords stock records ..." );
			DBQuery::getInstance()->insert( 'INSERT INTO `CurrentStocks`'.DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->stockRecords ) );
		}

		$this->stockRecords = array();
	}

	private function getQuery()
	{
		return "SELECT
  `WarehouseID`,
  `Name`
FROM `WarehouseList`
ORDER BY `WarehouseID` ASC";
	}
}
