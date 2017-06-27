<?php
require_once ROOT.'lib/soap/container/RequestContainer.abstract.php';

class RequestContainer_SetCurrentStocks extends RequestContainer
{
	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_SetCurrentStocks
	 */
	public function getRequest()
	{
		$request                = new PlentySoapRequest_SetCurrentStocks();
		$request->CurrentStocks = array();

		foreach( $this->getItems() as $stockData )
		{
			$stock = new PlentySoapObject_SetCurrentStocks();

			$stock->SKU             = $stockData['SKU'];
			$stock->EAN             = $stockData['EAN'];
			$stock->EAN2            = $stockData['EAN2'];
			$stock->EAN3            = $stockData['EAN3'];
			$stock->EAN4            = $stockData['EAN4'];
			$stock->PhysicalStock   = $stockData['PhysicalStock'];
			$stock->Reason          = $stockData['Reason'];
			$stock->StorageLocation = $stockData['StorageLocation'];
			$stock->WarehouseID     = $stockData['WarehouseID'];

			$request->CurrentStocks[] = $stock;
		}

		return $request;
	}
}
