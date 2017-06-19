<?php

class Request_GetCurrentStocks
{

	/**
	 * @param int $lastUpdate
	 * @param int $page
	 * @param int $warehouseId
	 *
	 * @return PlentySoapRequest_GetCurrentStocks
	 */
	public static function getRequest($lastUpdate, $page, $warehouseId)
	{
		$request = new PlentySoapRequest_GetCurrentStocks();

		$request->CallItemsLimit        = null;
		$request->GetCurrentStocksByEAN = null;
		$request->Items                 = null;
		$request->LastUpdate            = $lastUpdate;
		$request->Page                  = $page;
		$request->WarehouseID           = $warehouseId;

		return $request;
	}
}
