<?php

require_once ROOT.'lib/soap/container/RequestContainer.abstract.php';
require_once ROOT.'lib/soap/tools/SKUHelper.php';

class RequestContainer_GetItemsPriceLists extends RequestContainer
{
	/**
	 * returns the assembled request
	 *
	 * @return PlentySoapRequest_GetItemsPriceLists
	 */
	public function getRequest()
	{
		$request = new PlentySoapRequest_GetItemsPriceLists();

		$request->Items       = new ArrayOfPlentysoaprequestobject_getitemspricelists();
		$request->Items->item = array();

		foreach( $this->getItems() as $itemID )
		{
			$itemVariant      = new PlentySoapRequestObject_GetItemsPriceLists();
			$itemVariant->SKU = Values2SKU( $itemID );

			$request->Items->item[] = $itemVariant;
		}

		return $request;
	}
}
