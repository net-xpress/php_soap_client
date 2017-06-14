<?php

require_once ROOT.'lib/soap/container/RequestContainer.abstract.php';


/**
 * Class RequestContainer_GetItemsWarehouseSettings
 */
class RequestContainer_GetItemsWarehouseSettings extends RequestContainer
{
	/**
	 * @var int
	 */
	private $warehouseId;

	/**
	 * RequestContainer_GetItemsWarehouseSettings constructor.
	 * @param int $warehouseId
	 * @param int $capacity
	 */
	public function __construct($warehouseId, $capacity)
	{
		parent::__construct( $capacity );

		$this->warehouseId = $warehouseId;
	}

	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_GetItemsWarehouseSettings
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_GetItemsWarehouseSettings();

		foreach( $this->getItems() as &$SKU )
		{
			$requestGetItemsWarehouseSettings              = new PlentySoapObject_RequestGetItemsWarehouseSettings();
			$requestGetItemsWarehouseSettings->WarehouseID = $this->warehouseId;
			$requestGetItemsWarehouseSettings->SKU         = $SKU;
			$request->ItemsList[]                          = $requestGetItemsWarehouseSettings;
		}

		return $request;
	}
}
