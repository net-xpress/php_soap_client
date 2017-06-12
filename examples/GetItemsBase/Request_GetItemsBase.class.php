<?php

class Request_GetItemsBase
{
	/**
	 * @param int $lastUpdate
	 * @param int $currentTime
	 * @param int $page
	 * @return PlentySoapRequest_GetItemsBase
	 */
	public static function getRequest($lastUpdate, $currentTime, $page)
	{
		$request = new PlentySoapRequest_GetItemsBase();

		$request->Page           = $page;
		$request->LastUpdateFrom = $lastUpdate;
		$request->LastUpdateTill = $currentTime;

		$request->ItemID         = null;
		$request->ItemNo         = null;
		$request->EAN1           = null;
		$request->ExternalItemID = null;
		$request->Inactive       = null;
		$request->Lang           = null;

		$request->GetAttributeValueSets = true;
		$request->GetCategories         = true;
		$request->GetItemOthers         = true;
		$request->GetItemURL            = true;
		$request->GetLongDescription    = true;
		$request->GetMetaDescription    = true;
		$request->GetShortDescription   = true;
		$request->GetTechnicalData      = true;

		$request->GetCategoryNames       = false;
		$request->GetItemAttributeMarkup = false;
		$request->GetItemProperties      = false;
		$request->GetItemSuppliers       = false;

		$request->CategoriePath  = null;
		$request->CallItemsLimit = null;

		$request->CouchCommerce    = null;
		$request->Gimahhot         = null;
		$request->GoogleProducts   = null;
		$request->Grosshandel      = null;
		$request->Hitmeister       = null;
		$request->Hood             = null;
		$request->Laary            = null;
		$request->LastInsertedFrom = null;
		$request->LastInsertedTill = null;
		$request->MainWarehouseID  = null;
		$request->Marking1ID       = null;
		$request->Marking2ID       = null;
		$request->Moebelprofi      = null;
		$request->Otto             = null;
		$request->PlusDe           = null;
		$request->ProducerID       = null;
		$request->Referrer         = null;
		$request->Restposten       = null;
		$request->ShopShare        = null;
		$request->Shopgate         = null;
		$request->Shopperella      = null;
		$request->StockAvailable   = null;
		$request->StoreID          = null;
		$request->SumoScout        = null;
		$request->Tradoria         = null;
		$request->Twenga           = null;
		$request->WebAPI           = null;
		$request->Webshop          = null;
		$request->Yatego           = null;
		$request->Zalando          = null;

		return $request;
	}
}