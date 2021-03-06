<?php


class Request_SearchOrders
{

	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 * @return PlentySoapRequest_SearchOrders
	 */
	public static function getRequest($lastUpdate, $currentTime, $page)
	{
		$oPlentySoapRequest_SearchOrders	=	new PlentySoapRequest_SearchOrders();

		// search params
		$oPlentySoapRequest_SearchOrders->GetOrderCustomerAddress		=	false;

		$oPlentySoapRequest_SearchOrders->GetOrderDeliveryAddress		=	false;

		$oPlentySoapRequest_SearchOrders->GetOrderInfo					=	false;

		$oPlentySoapRequest_SearchOrders->GetParcelService				=	false;

		$oPlentySoapRequest_SearchOrders->GetSalesOrderProperties		=	false;

		$oPlentySoapRequest_SearchOrders->CustomerCountryID				=	null;

		$oPlentySoapRequest_SearchOrders->ExternalOrderID				=	null;

		$oPlentySoapRequest_SearchOrders->InvoiceNumber					=	null;

		$oPlentySoapRequest_SearchOrders->LastUpdateFrom				=	$lastUpdate;

		$oPlentySoapRequest_SearchOrders->LastUpdateTill				=	$currentTime;

		$oPlentySoapRequest_SearchOrders->MultishopID					=	0;

		$oPlentySoapRequest_SearchOrders->OrderCompletedFrom			=	null;

		$oPlentySoapRequest_SearchOrders->OrderCompletedTill			=	null;

		$oPlentySoapRequest_SearchOrders->OrderCreatedFrom				=	null;

		$oPlentySoapRequest_SearchOrders->OrderCreatedTill				=	null;

		$oPlentySoapRequest_SearchOrders->OrderID						=	null;

		$oPlentySoapRequest_SearchOrders->OrderPaidFrom					=	null;

		$oPlentySoapRequest_SearchOrders->OrderPaidTill					=	null;

		$oPlentySoapRequest_SearchOrders->ReferrerID					=	null;

		$oPlentySoapRequest_SearchOrders->OrderStatus					=	null;

		$oPlentySoapRequest_SearchOrders->Page							=	$page;

		return $oPlentySoapRequest_SearchOrders;
	}

}

?>