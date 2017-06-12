<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SearchOrders.class.php';

/*
 * there's a plenty bug when an order contains some non-utf-8 characters, soap will refuse to respond for the whole package of 25 orders
 * in order to work unattended we will workaroud this bug. code regarding this workaround is marked with /* workaround */

class SoapCall_SearchOrders extends PlentySoapCall 
{
	private static $SAVE_AFTER_PAGES = 1;
	
	private $page								=	0;
	private $pages								=	-1;
	private $oPlentySoapRequest_SearchOrders	=	null;
	
	private $startAtPage     = 0;
	private $lastSavedPage   = 0;
	private $lastOrderID     = -1;
	private $caughtUTF8Error = false;

	private $aOrderHeads = [];
	private $aOrderItems = [];

	public function __construct()
	{
		parent::__construct(__CLASS__);
	}
	
	public function execute()
	{
		$this->getLogger()->debug(__FUNCTION__);
		
		list( $lastUpdate, $currentTime, $this->startAtPage ) = DBUtils::lastUpdateStart( __CLASS__ );

		if( $this->pages == -1 )
		{
			try
			{
				$this->oPlentySoapRequest_SearchOrders	=	Request_SearchOrders::getRequest( $lastUpdate, $currentTime, $this->startAtPage );
				
				
				if( $this->startAtPage > 0 )
				{
					$this->getLogger()->debug( __FUNCTION__." Starting at page {$this->startAtPage}" );
				}
				/*
				 * do soap call
				 */
				$response		=	$this->getPlentySoap()->SearchOrders( $this->oPlentySoapRequest_SearchOrders );
				
				
				if( $response->Success == true )
				{
					$ordersFound	=	count($response->Orders->item);
					$pagesFound		=	$response->Pages;
					
					$this->getLogger()->debug(__FUNCTION__.' Request Success - orders found : '.$ordersFound .' / pages : '.$pagesFound );
					
					// auswerten
					$this->responseInterpretation( $response );
					
					if( $pagesFound > $this->page )
					{
						$this->page 	= 	$this->startAtPage + 1;
						$this->pages 	=	$pagesFound;
						
						$this->executePages();
					}
										
				}
				else
				{
					$this->getLogger()->debug(__FUNCTION__.' Request Error');
				}
			}
			catch(Exception $e)
			{
				$this->onExceptionAction($e);
			}
		}
		else
		{
			$this->executePages();
		}

		$this->storeToDB();
		DBUtils::lastUpdateFinish( $currentTime );
	}
	
	
	
	public function executePages()
	{
		while( $this->pages > $this->page )
		{
			$this->oPlentySoapRequest_SearchOrders->Page = $this->page;
			try
			{
				$response		=	$this->getPlentySoap()->SearchOrders( $this->oPlentySoapRequest_SearchOrders );
				
				if( $response->Success == true )
				{
					$ordersFound	=	count($response->Orders->item);
					$this->getLogger()->debug(__FUNCTION__.' Request Success - orders found : '.$ordersFound .' / page : '.$this->page );
					
					// auswerten
					$this->responseInterpretation( $response );
				}
				
				$this->page++;
				
			}	
			catch(Exception $e)
			{
				$this->onExceptionAction($e);
			}
			if( $this->page - $this->lastSavedPage > self::$SAVE_AFTER_PAGES )
			{
				$this->storeToDB();
				$this->lastSavedPage = $this->page;
				DBUtils::lastUpdatePageUpdate( __CLASS__, $this->page );
			}
		}
	}

	/**
	 * @param PlentySoapResponse_SearchOrders $response
	 */
	private function responseInterpretation(PlentySoapResponse_SearchOrders $response)	{
		if( is_array( $response->Orders->item ) )
		{
			foreach( $response->Orders->item AS $order )
			{
				$this->processOrder( $order );
			}
		}
		else
		{
			$this->processOrder( $response->Orders->item );
		}
	}

	/**
	 * @param PlentySoapObject_SearchOrders $orders
	 */
	private function processOrder($orders)
	{
		$this->processOrderHead( $orders->OrderHead );

		if( isset( $orders->OrderItems->item ) && is_array( $orders->OrderItems->item ) )
		{
			foreach( $orders->OrderItems->item AS $oitem )
			{
				$this->processOrderItem( $oitem );
			}
		}
		else
		{
			if( isset( $orders->OrderItems->item ) )
			{
				$this->processOrderItem( $orders->OrderItems->item );
			}
		}
	}

	/**
	 * @param PlentySoapObject_OrderHead $head
	 */
	private function processOrderHead($head)
	{
		/* workaround */
		if( $this->caughtUTF8Error )
		{
			$this->debug( __FUNCTION__." Stored failed OrderID-Range from ".($this->lastOrderID + 1)." for ".(intval( $head->OrderID ) - ($this->lastOrderID + 1))." orders, next working OrderID: {$head->OrderID}" );

			// ... and carry on in normal mode
			$this->caughtUTF8Error = false;
		}

		$this->lastOrderID = intval( $head->OrderID );
		$this->aOrderHeads[$head->OrderID] = [
			'Currency'                => $head->Currency,
			'CustomerID'              => $head->CustomerID,
			'DeliveryAddressID'       => $head->DeliveryAddressID,
			'DoneTimestamp'           => $head->DoneTimestamp,
			'DunningLevel'            => $head->DunningLevel,
			'EbaySellerAccount'       => $head->EbaySellerAccount,
			'EstimatedTimeOfShipment' => $head->EstimatedTimeOfShipment,
			'ExchangeRatio'           => $head->ExchangeRatio,
			'ExternalOrderID'         => $head->ExternalOrderID,
			'Invoice'                 => $head->Invoice,
			'IsNetto'                 => $head->IsNetto,
			'LastUpdate'              => $head->LastUpdate,
			'Marking1ID'              => $head->Marking1ID,
			'MethodOfPaymentID'       => $head->MethodOfPaymentID,
			'StoreID'                 => $head->StoreID,
			/*	'OrderDocumentNumbers'		=>	$head->OrderDocumentNumbers, ignored since not part of the request */
			'OrderID'                 => $head->OrderID,
			/*	'OrderInfos'				=>	$head->OrderInfos, ignored since not part of the request */
			'OrderStatus'             => $head->OrderStatus,
			'OrderTimestamp'          => $head->OrderTimestamp,
			'OrderType'               => $head->OrderType,
			'PackageNumber'           => $head->PackageNumber,
			'PaidTimestamp'           => $head->PaidTimestamp,
			'ParentOrderID'           => $head->ParentOrderID,
			'PaymentStatus'           => $head->PaymentStatus,
			'ReferrerID'              => $head->ReferrerID,
			'RemoteIP'                => $head->RemoteIP,
			'ResponsibleID'           => $head->ResponsibleID,
			'SalesAgentID'            => $head->SalesAgentID,
			'SellerAccount'           => $head->SellerAccount,
			'ShippingCosts'           => $head->ShippingCosts,
			'ShippingID'              => $head->ShippingID,
			'ShippingMethodID'        => $head->ShippingMethodID,
			'ShippingProfileID'       => $head->ShippingProfileID,
			'TotalBrutto'             => $head->TotalBrutto,
			'TotalInvoice'            => $head->TotalInvoice,
			'TotalNetto'              => $head->TotalNetto,
			'TotalVAT'                => $head->TotalVAT,
			'WarehouseID'             => $head->WarehouseID,
		];
	}

	/**
	 * @param PlentySoapObject_OrderItem $item
	 */
	private function processOrderItem($item)
	{
		$this->aOrderItems[] = [
			'BundleItemID'        => $item->BundleItemID,
			'Currency'            => $item->Currency,
			'ExternalItemID'      => $item->ExternalItemID,
			'ExternalOrderItemID' => $item->ExternalOrderItemID,
			'ItemID'              => $item->ItemID,
			'ItemNo'              => $item->ItemNo,
			'ItemRebate'          => $item->ItemRebate,
			'ItemText'            => $item->ItemText,
			'NeckermannItemNo'    => $item->NeckermannItemNo,
			'OrderID'             => $item->OrderID,
			'OrderRowID'          => $item->OrderRowID,
			'Price'               => $item->Price,
			'Quantity'            => $item->Quantity,
			'ReferrerID'          => $item->ReferrerID,
			'SKU'                 => $item->SKU,
			/*	'SalesOrderProperties'	=>	$item->SalesOrderProperties, ignored since not part of the request */
			'VAT'                 => $item->VAT,
			'WarehouseID'         => $item->WarehouseID,
		];
	}

	private function storeToDB()
	{
		// store orders to db
		$countOrderHeads = count( $this->aOrderHeads );
		$countOrderItems = count( $this->aOrderItems );

		if( $countOrderHeads > 0 )
		{
			$dbQuery = DBQuery::getInstance();

			$this->debug( __FUNCTION__." : storing $countOrderHeads order head and $countOrderItems order item records. Progress: {$this->page} / {$this->pages}" );

			$dbQuery->insert( "INSERT INTO `OrderHead`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->aOrderHeads ) );

			// delete old OrderItems to prevent duplicate insertion
			$dbQuery->delete( "DELETE FROM `OrderItem` WHERE `OrderID` IN ('".implode( "','", array_keys( $this->aOrderHeads ) )."')" );

			$dbQuery->insert( "INSERT INTO `OrderItem`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->aOrderItems ) );

			$this->aOrderHeads = [];
			$this->aOrderItems = [];
		}
	}

	public function onExceptionAction(Exception $e)
	{
		/* workaround */
		if( is_soap_fault( $e ) && $e->faultcode === 'SOAP-ENV:Server' )
		{
			// assume it's an utf-8 error
			$this->proceedAfterUTF8Error();
		}
		else
		{
			$this->storeToDB();
			parent::onExceptionAction( $e );
		}
	}

	private function proceedAfterUTF8Error()
	{
		$this->getLogger()->debug( __FUNCTION__.' Caught UTF-8 Error on page : '.$this->page.', last known working OrderID: '.$this->lastOrderID.', skipping to next page' );

		// remember we caught an UTF8-Error ...
		$this->caughtUTF8Error = true;

		// ... then skip to the next page
		$this->page++;
	}
}

?>

