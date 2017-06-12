<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_SearchOrders.class.php';


class SoapCall_SearchOrders extends PlentySoapCall 
{
	private static $SAVE_AFTER_PAGES = 1;
	
	private $page								=	0;
	private $pages								=	-1;
	private $oPlentySoapRequest_SearchOrders	=	null;
	
	private $startAtPage     = 0;
	private $lastSavedPage   = 0;

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
	}

	/**
	 * @param PlentySoapObject_OrderItem $item
	 */
	private function processOrderItem($item)
	{
	}

	private function storeToDB()
	{
	}
}

?>

