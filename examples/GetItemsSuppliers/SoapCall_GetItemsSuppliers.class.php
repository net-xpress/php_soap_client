<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_GetItemsSuppliers.class.php';

class SoapCall_GetItemsSuppliers extends PlentySoapCall
{

	const MAX_SUPPLIERS_PER_PAGE = 50;

	private $aSuppliers     = [];
	private $noDataArticles = [];

	public function __construct()
	{
		parent::__construct( __CLASS__ );

		// clear ItemSuppliers db before start so there's no old leftover
		DBQuery::getInstance()->truncate( "TRUNCATE TABLE `ItemsSuppliers`" );
	}

	public function execute()
	{
		$this->getLogger()->info( __FUNCTION__." fetching items suppliers from plenty" );

		try
		{
			// get all possible ItemIDs
			$result = DBQuery::getInstance()->select( "SELECT ItemID FROM `ItemsBase` ORDER BY ItemID" );

			// for every 50 ItemIDs ...
			for( $page = 0; $page < ceil( $result->getNumRows() / self::MAX_SUPPLIERS_PER_PAGE ); $page++ )
			{
				// ... prepare a separate request ...
				$preparedRequest = new RequestContainer_GetItemsSuppliers( self::MAX_SUPPLIERS_PER_PAGE );
				while( !$preparedRequest->isFull() && $current = $result->fetchAssoc() )
				{
					$preparedRequest->add( $current['ItemID'] );
				}

				// ... then do soap call ...
				$response = $this->getPlentySoap()->GetItemsSuppliers( $preparedRequest->getRequest() );

				// ... if successful ...
				if( $response->Success == true )
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

	private function responseInterpretation(PlentySoapResponse_GetItemsSuppliers $response)
	{
		if( !is_null( $response->ItemsSuppliersList ) )
		{
			if( is_array( $response->ItemsSuppliersList->item ) )
			{
				/** @noinspection PhpParamsInspection */
				$countRecords = count( $response->ItemsSuppliersList->item );
				$this->debug( __FUNCTION__." fetched $countRecords supplier records from ItemID: {$response->ItemsSuppliersList->item[0]->ItemID} to {$response->ItemsSuppliersList->item[$countRecords - 1]->ItemID}" );

				foreach( $response->ItemsSuppliersList->item AS &$suppliersList )
				{
					$this->processSuppliersList( $suppliersList );
				}
			}
			else
			{
				if( !is_null( $response->ItemsSuppliersList->item ) )
				{
					$this->debug( __FUNCTION__." fetched supplier record for ItemID: {$response->ItemsSuppliersList->item->ItemID}" );

					$this->processSuppliersList( $response->ItemsSuppliersList->item );
				}
			}
		}

		// process potential response messages
		foreach( $response->ResponseMessages->item as $responseMessage )
		{
			$this->processResponseMessage( $responseMessage );
		}
	}

	/**
	 * @param PlentySoapObject_ItemsSuppliersList $suppliersList
	 */
	private function processSuppliersList($suppliersList)
	{
		if( is_array( $suppliersList->ItemsSuppliers->item ) )
		{
			foreach( $suppliersList->ItemsSuppliers->item as $itemsSupplier )
			{
				// prepare for storing
				$this->processSupplier( $itemsSupplier );
			}
		}
		else
		{
			// prepare for storing
			$this->processSupplier( $suppliersList->ItemsSuppliers->item );
		}
	}

	/**
	 * @param PlentySoapObject_ItemsSuppliers $supplier
	 */
	private function processSupplier($supplier)
	{
		$this->aSuppliers[] = [
			'ItemID'                  => $supplier->ItemID,
			'IsRebateAllowed'         => $supplier->IsRebateAllowed,
			'ItemSupplierPrice'       => $supplier->ItemSupplierPrice,
			'ItemSupplierRowID'       => $supplier->ItemSupplierRowID,
			'LastUpdate'              => $supplier->LastUpdate,
			'Priority'                => $supplier->Priority,
			'Rebate'                  => $supplier->Rebate,
			'SupplierDeliveryTime'    => $supplier->SupplierDeliveryTime,
			'SupplierID'              => $supplier->SupplierID,
			'SupplierItemNumber'      => $supplier->SupplierItemNumber,
			'SupplierMinimumPurchase' => $supplier->SupplierMinimumPurchase,
			'VPE'                     => $supplier->VPE,
		];
	}

	/**
	 * @param PlentySoapResponseMessage $responseMessage
	 */
	private function processResponseMessage($responseMessage)
	{
		switch( $responseMessage->Code )
		{
			case 100 :
				// everything ok
				break;
			case 110 :
				// no data warning
				$this->noDataArticles[] = $responseMessage->IdentificationValue;
				break;
			case 800 :
				// error
				if( $responseMessage->IdentificationKey == 'ItemID' )
				{
					$this->debug( __FUNCTION__." error 800: ItemID: {$responseMessage->IdentificationValue}" );
				}
				else
				{
					$this->debug( __FUNCTION__." error 800: An error occurred while retrieving item supplier list" );
				}
				break;
			case 810 :
				// limit error
				$this->debug( __FUNCTION__." error 810: Only 50 item supplier lists can be retrieved at the same time" );
				break;
			default :
				$this->debug( __FUNCTION__." unknown error: {$responseMessage->Code}" );
		}
	}

	/**
	 * bulk insert/update retrieved data except ItemSupplierPrice and LastUpdate
	 * this is a workaround for a plenty bug which prevents correct values of price and last update being sent
	 */
	private function storeToDB()
	{
		$countSuppliers = count( $this->aSuppliers );
		$countNoData    = count( $this->noDataArticles );

		if( $countSuppliers > 0 )
		{
			DBQuery::getInstance()->insert( "INSERT INTO `ItemsSuppliers`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->aSuppliers ) );

			$this->getLogger()->info( __FUNCTION__." storing $countSuppliers records of supplier data" );
		}

		if( $countNoData > 0 )
		{
			$this->getLogger()->info( __FUNCTION__." no data found for items: ".implode( ', ', $this->noDataArticles ) );
		}
	}
}
