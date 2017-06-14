<?php

require_once ROOT.'lib/soap/container/RequestContainer.abstract.php';


/**
 * Class RequestContainer_GetItemsSuppliers
 */
class RequestContainer_GetItemsSuppliers extends RequestContainer
{
	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_GetItemsSuppliers
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_GetItemsSuppliers();

		$request->ItemIDList       = new ArrayOfPlentysoapobject_getitemssuppliers();
		$request->ItemIDList->item = array();

		foreach( $this->getItems() as $itemID )
		{
			$getItemsSuppliers         = new PlentySoapObject_GetItemsSuppliers();
			$getItemsSuppliers->ItemID = $itemID;

			$request->ItemIDList->item[] = $getItemsSuppliers;
		}

		return $request;
	}
}
