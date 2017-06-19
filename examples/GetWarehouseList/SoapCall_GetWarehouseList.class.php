<?php
require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';

class SoapCall_GetWarehouseList extends PlentySoapCall
{

	private $aWarehouseData = [];

	public function __construct()
	{
		parent::__construct( __CLASS__ );
		DBQuery::getInstance()->truncate( 'TRUNCATE TABLE `WarehouseList`' );
	}

	public function execute()
	{
		$this->debug( __FUNCTION__.' Fetching warehouse data from plenty' );

		try
		{
			$response = $this->getPlentySoap()->GetWarehouseList( new PlentySoapRequest_GetWarehouseList() );

			if( $response->Success == true )
			{
				$this->responseInterpretation( $response );
				$this->storeToDB();
			}
			else
			{
				$this->debug( __FUNCTION__.' Request Error' );
			}
		} catch( Exception $e )
		{
			$this->onExceptionAction( $e );
		}
	}

	private function responseInterpretation(PlentySoapResponse_GetWarehouseList $response)
	{
		if( is_array( $response->WarehouseList->item ) )
		{
			foreach( $response->WarehouseList->item as $oPlentySoapObject_GetWarehouseList )
			{
				$this->processWarehouse( $oPlentySoapObject_GetWarehouseList );
			}
		}
		else
		{
			$this->processWarehouse( $response->WarehouseList->item );
		}
	}

	/**
	 * @param PlentySoapObject_GetWarehouseList $warehouse
	 */
	private function processWarehouse($warehouse)
	{
		$this->aWarehouseData[] = [
			'WarehouseID' => $warehouse->WarehouseID,
			'Name'        => $warehouse->Name,
			'Type'        => $warehouse->Type,
		];
	}

	private function storeToDB()
	{
		$countWarehouseData = count( $this->aWarehouseData );

		if( $countWarehouseData > 0 )
		{
			$this->getLogger()->debug( __FUNCTION__." storing $countWarehouseData warehouse records to db" );
			DBQuery::getInstance()->insert( 'INSERT INTO `WarehouseList`'.DBUtils::buildMultipleInsert( $this->aWarehouseData ) );
		}
	}
}
