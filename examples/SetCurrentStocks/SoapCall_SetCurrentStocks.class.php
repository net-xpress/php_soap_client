<?php
require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once ROOT.'lib/soap/tools/SKUHelper.php';
require_once 'RequestContainer_SetCurrentStocks.class.php';

class SoapCall_SetCurrentStocks extends PlentySoapCall
{
	const MAX_STOCK_RECORDS_PER_PAGE = 100;

	/**
	 * this call is used mostly to update stock of warehouse 2: 'JDD'
	 * stock data from 'JDD' is maintained externally by jansen display via csv-file and is updated very frequently
	 *
	 * to prevent accidental tampering with stock of warehouse 1: 'nx Lager' we introduce a constant to control updating
	 * of 'nx Lager'
	 *
	 * default: true, set to false manually if necessary
	 */
	const DISABLE_WAREHOUSE_1_UPDATE = true;

	private $currentTime;

	public function __construct()
	{
		parent::__construct( __CLASS__ );

		$this->currentTime = time();
	}

	public function execute()
	{
		$this->getLogger()->debug( __FUNCTION__.' writing stock updates ...' );
		try
		{
			// 1. get all stock updates
			$dbResult = DBQuery::getInstance()->select( $this->getQuery() );

			// 2. for every 100 updates ...
			for( $page = 0, $maxPage = ceil( $dbResult->getNumRows() / self::MAX_STOCK_RECORDS_PER_PAGE ); $page < $maxPage; $page++ )
			{
				// ... prepare a separate request ...
				$request = new RequestContainer_SetCurrentStocks( self::MAX_STOCK_RECORDS_PER_PAGE );

				$deleteAfterWrite       = [];
				$updateTimingAfterWrite = [];

				// ... for each item variant ...
				while( !$request->isFull() && ($unwritten = $dbResult->fetchAssoc()) )
				{
					// ... fill in data
					$request->add( [
						'SKU'             => Values2SKU( $unwritten['ItemID'], $unwritten['AttributeValueSetID'], $unwritten['PriceID'] ),
						'WarehouseID'     => $unwritten['WarehouseID'],
						'StorageLocation' => $unwritten['StorageLocation'],
						'PhysicalStock'   => $unwritten['PhysicalStock'],
						'Reason'          => $unwritten['Reason'],
					] );

					// ... prepare database cleaning
					$deleteAfterWrite[] = "({$unwritten['ItemID']},{$unwritten['AttributeValueSetID']},{$unwritten['PriceID']})";

					// ... prepare timing update
					$updateTimingAfterWrite[] = [
						'ItemID'              => $unwritten['ItemID'],
						'AttributeValueSetID' => $unwritten['AttributeValueSetID'],
						'WarehouseID'         => $unwritten['WarehouseID'],
						'Timestamp'           => $this->currentTime,
					];
				}

				// 3. write them back via soap
				$response = $this->getPlentySoap()->SetCurrentStocks( $request->getRequest() );

				// 4. if successful ...
				if( $response->Success == true )
				{
					// ... then delete specified elements from SetCurrentStocks
					DBQuery::getInstance()->delete( "DELETE FROM `SetCurrentStocks` WHERE (ItemID, AttributeValueSetID, PriceID) IN (".implode( ',', $deleteAfterWrite ).')' );

					// ... and update timing
					DBQuery::getInstance()->insert( "INSERT INTO `CurrentStocksTiming`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $updateTimingAfterWrite ) );
				}
				else
				{
					// ... otherwise log error and try next request
					$this->getLogger()->debug( __FUNCTION__.' Request Error' );
				}
			}
		} catch( Exception $e )
		{
			$this->onExceptionAction( $e );
		}
	}

	private function getQuery()
	{
		return "SELECT * FROM `SetCurrentStocks`".(self::DISABLE_WAREHOUSE_1_UPDATE ? " WHERE WarehouseID != 1" : "");

	}
}