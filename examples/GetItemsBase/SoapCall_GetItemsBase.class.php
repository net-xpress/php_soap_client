<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsBase.class.php';

class SoapCall_GetItemsBase extends PlentySoapCall
{
	private $page              = 0;
	private $pages             = -1;
	private $plentySoapRequest = null;

	private $startAtPage = 0;

	private $processedItemsBases         = [];
	private $processedAttributeValueSets = [];
	private $processedCategories         = [];
	private $processedAvailability       = [];
	private $processedTexts              = [];
	private $processedFreeTextFields     = [];
	private $processedStock              = [];
	private $processedOthers             = [];

	public function __construct()
	{
		parent::__construct( __CLASS__ );
	}

	public function execute()
	{
		list( $lastUpdate, $currentTime, $this->startAtPage ) = DBUtils::lastUpdateStart( __CLASS__ );

		if( $this->pages == -1 )
		{
			try
			{
				$this->plentySoapRequest = Request_GetItemsBase::getRequest( $lastUpdate, $currentTime, $this->startAtPage );

				if( $this->startAtPage > 0 )
				{
					$this->getLogger()->info( __FUNCTION__." Starting at page {$this->startAtPage}" );
				}

				/*
				 * do soap call
				 */
				$response = $this->getPlentySoap()->GetItemsBase( $this->plentySoapRequest );

				if( ($response->Success == true) && isset( $response->ItemsBase ) )
				{
					// request successful, processing data..

					/** @noinspection PhpParamsInspection */
					$articlesFound = is_array( $response->ItemsBase->item ) ? count( $response->ItemsBase->item ) : 1;
					$pagesFound    = $response->Pages;

					$this->getLogger()->info( __FUNCTION__." Request Success - articles found : $articlesFound / pages : $pagesFound" );

					// process response
					$this->responseInterpretation( $response );

					if( $pagesFound > $this->page )
					{
						$this->page  = $this->startAtPage + 1;
						$this->pages = $pagesFound;

						DBUtils::lastUpdatePageUpdate( __CLASS__, $this->page );
						$this->executePages();
					}
				}
				else
				{
					if( ($response->Success == true) && !isset( $response->ItemsBase ) )
					{
						// request successful, but no data to process
						$this->debug( __FUNCTION__." Request Success -  but no matching articles found" );
					}
					else
					{
						$this->debug( __FUNCTION__." Request Error" );
					}
				}
			} catch( Exception $e )
			{
				$this->onExceptionAction( $e );
			}
		}
		else
		{
			$this->executePages();
		}

		$this->storeToDB();
		DBUtils::lastUpdateFinish( $currentTime, __CLASS__ );

	}

	private function executePages()
	{
		while( $this->pages > $this->page )
		{
			$this->plentySoapRequest->Page = $this->page;
			try
			{
				$response = $this->getPlentySoap()->GetItemsBase( $this->plentySoapRequest );

				if( $response->Success == true )
				{
					/** @noinspection PhpParamsInspection */
					$articlesFound = is_array( $response->ItemsBase->item ) ? count( $response->ItemsBase->item ) : 1;
					$this->debug( __FUNCTION__." Request Success - articles found : $articlesFound / page : {$this->page}" );

					// auswerten
					$this->responseInterpretation( $response );
				}

				$this->page++;
				DBUtils::lastUpdatePageUpdate( __CLASS__, $this->page );

			} catch( Exception $e )
			{
				$this->onExceptionAction( $e );
			}
		}
	}

	private function responseInterpretation(PlentySoapResponse_GetItemsBase $response)
	{
		if( is_array( $response->ItemsBase->item ) )
		{

			foreach( $response->ItemsBase->item AS $itemsBase )
			{
				$this->processItemsBase( $itemsBase );
			}
		}
		else
		{
			$this->processItemsBase( $response->ItemsBase->item );
		}
	}

	/**
	 * @param PlentySoapObject_ItemBase $itemsBase
	 */
	private function processItemsBase($itemsBase)
	{
		// prepare ItemsBase for persistent storage

		$itemID = intval( $itemsBase->ItemID );

		$this->processedItemsBases[$itemID] = [
			'ItemID'              => $itemID,
			'ItemNo'              => $itemsBase->ItemNo,
			'EAN1'                => $itemsBase->EAN1,
			'EAN2'                => $itemsBase->EAN2,
			'EAN3'                => $itemsBase->EAN3,
			'EAN4'                => $itemsBase->EAN4,
			'ExternalItemID'      => $itemsBase->ExternalItemID,
			'BundleType'          => $itemsBase->BundleType,
			'Condition'           => $itemsBase->Condition,
			'CustomsTariffNumber' => $itemsBase->CustomsTariffNumber,
			'DeepLink'            => $itemsBase->DeepLink,
			'FSK'                 => $itemsBase->FSK,
			'HasAttributes'       => $itemsBase->HasAttributes,
			'ISBN'                => $itemsBase->ISBN,
			'Inserted'            => $itemsBase->Inserted,
			'LastUpdate'          => $itemsBase->LastUpdate,
			'Marking1ID'          => $itemsBase->Marking1ID,
			'Marking2ID'          => $itemsBase->Marking2ID,
			'Model'               => $itemsBase->Model,
			'Position'            => $itemsBase->Position,
			'ProducerID'          => $itemsBase->ProducerID,
			'ProducerName'        => $itemsBase->ProducerName,
			'ProducingCountryID'  => $itemsBase->ProducingCountryID,
			'Published'           => $itemsBase->Published,
			'StorageLocation'     => $itemsBase->StorageLocation,
			'Type'                => $itemsBase->Type,
			'VATInternalID'       => $itemsBase->VATInternalID,
			'WebShopSpecial'      => $itemsBase->WebShopSpecial,
		];

		// process AttributeValueSets
		if( $itemsBase->HasAttributes )
		{
			if( is_array( $itemsBase->AttributeValueSets->item ) )
			{
				foreach( $itemsBase->AttributeValueSets->item as $attributeValueSet )
				{
					$this->processAttributeValueSet( $itemID, $attributeValueSet );
				}
			}
			else
			{
				$this->processAttributeValueSet( $itemID, $itemsBase->AttributeValueSets->item );
			}
		}

		// process Categories
		if( isset( $itemsBase->Categories ) )
		{
			if( is_array( $itemsBase->Categories->item ) )
			{
				foreach( $itemsBase->Categories->item as $category )
				{
					$this->processCategories( $itemID, $category );
				}
			}
			else
			{
				$this->processCategories( $itemID, $itemsBase->Categories->item );
			}
		}

		// process Availability
		$this->processAvailability( $itemID, $itemsBase->Availability );

		// process Texts & FreeTexts
		$this->processTexts( $itemID, $itemsBase->Texts, $itemsBase->FreeTextFields );

		// process stock & others
		$this->processStock( $itemID, $itemsBase->Stock );
		$this->processOthers( $itemID, $itemsBase->Others );
	}

	private function storeToDB()
	{
		// insert itemsbase
		$countItemsBases         = count( $this->processedItemsBases );
		$countAttributeValueSets = count( $this->processedAttributeValueSets );
		$countCategoriesRecords  = count( $this->processedCategories );

		if( $countItemsBases > 0 )
		{
			$this->getLogger()->info( __FUNCTION__." : storing $countItemsBases items base records ..." );

			DBQuery::getInstance()->insert( "INSERT INTO `ItemsBase`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->processedItemsBases ) );
			DBQuery::getInstance()->insert( "INSERT INTO `ItemAvailability`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->processedAvailability ) );
			DBQuery::getInstance()->insert( "INSERT INTO `ItemTexts`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->processedTexts ) );
			DBQuery::getInstance()->insert( "INSERT INTO `ItemFreeTextFields`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->processedFreeTextFields ) );
			DBQuery::getInstance()->insert( "INSERT INTO `ItemStock`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->processedStock ) );
			DBQuery::getInstance()->insert( "INSERT INTO `ItemOthers`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->processedOthers ) );
		}

		if( $countAttributeValueSets > 0 )
		{
			$this->getLogger()->info( __FUNCTION__." : storing $countAttributeValueSets attribute value set records ..." );

			DBQuery::getInstance()->delete( "DELETE FROM ItemAttributeValueSets WHERE `ItemID` IN ('".implode( "','", array_keys( $this->processedItemsBases ) )."')" );
			DBQuery::getInstance()->insert( "INSERT INTO `ItemAttributeValueSets`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->processedAttributeValueSets ) );
		}

		if( $countCategoriesRecords > 0 )
		{
			$this->getLogger()->info( __FUNCTION__." : storing $countCategoriesRecords records of categories ..." );

			DBQuery::getInstance()->delete( "DELETE FROM `ItemCategories` WHERE `ItemID` IN ('".implode( "','", array_keys( $this->processedItemsBases ) )."')" );
			DBQuery::getInstance()->insert( "INSERT INTO `ItemCategories`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->processedCategories ) );
		}
	}

	/**
	 * @param int $itemID
	 * @param PlentySoapObject_ItemAttributeValueSet $attributeValueSet
	 */
	private function processAttributeValueSet($itemID, $attributeValueSet)
	{
		// prepare AttributeValueSet for persistent storage
		$this->processedAttributeValueSets[] = [
			'ItemID'                => $itemID,
			'ASIN'                  => $attributeValueSet->ASIN,
			'AttributeValueSetID'   => $attributeValueSet->AttributeValueSetID,
			'AttributeValueSetName' => $attributeValueSet->AttributeValueSetName,
			'Availability'          => $attributeValueSet->Availability,
			'ColliNo'               => $attributeValueSet->ColliNo,
			'EAN'                   => $attributeValueSet->EAN,
			'EAN2'                  => $attributeValueSet->EAN2,
			'EAN3'                  => $attributeValueSet->EAN3,
			'EAN4'                  => $attributeValueSet->EAN4,
			'Oversale'              => $attributeValueSet->Oversale,
			'PriceID'               => $attributeValueSet->PriceID,
			'PurchasePrice'         => $attributeValueSet->PurchasePrice,
			'UVP'                   => $attributeValueSet->UVP,
		];
	}

	/**
	 * @param int $itemID
	 * @param PlentySoapObject_ItemCategory $category
	 */
	private function processCategories($itemID, $category)
	{
		if( !is_null( $category ) )
		{
			// prepare Categories for persistent storage
			$this->processedCategories[] = [
				'ItemID'                 => $itemID,
				'ItemCategoryID'         => $category->ItemCategoryID,
				'ItemCategoryLevel'      => $category->ItemCategoryLevel,
				'ItemCategoryPath'       => $category->ItemCategoryPath,
				'ItemCategoryPathNames'  => $category->ItemCategoryPathNames,
				'RemoveCategoryFromItem' => $category->RemoveCategoryFromItem,
			];
		}
	}

	/**
	 * @param int $itemID
	 * @param PlentySoapObject_ItemAvailability $availability
	 */
	private function processAvailability($itemID, $availability)
	{
		if( !is_null( $availability ) )
		{
			// prepare availability for persistent storage
			$this->processedAvailability[] = [
				'ItemID'                                => $itemID,
				'Allyouneeed'                           => $availability->Allyouneeed,
				'AmazonFBA'                             => $availability->AmazonFBA,
				'AmazonFEDAS'                           => $availability->AmazonFEDAS,
				'AmazonMultichannel'                    => $availability->AmazonMultichannel,
				'AmazonMultichannelCom'                 => $availability->AmazonMultichannelCom,
				'AmazonMultichannelDe'                  => $availability->AmazonMultichannelDe,
				'AmazonMultichannelEs'                  => $availability->AmazonMultichannelEs,
				'AmazonMultichannelFr'                  => $availability->AmazonMultichannelFr,
				'AmazonMultichannelIt'                  => $availability->AmazonMultichannelIt,
				'AmazonMultichannelUk'                  => $availability->AmazonMultichannelUk,
				'AmazonProduct'                         => $availability->AmazonProduct,
				'AvailabilityID'                        => $availability->AvailabilityID,
				'AvailableUntil'                        => $availability->AvailableUntil,
				'Cdiscount'                             => $availability->Cdiscount,
				'CouchCommerce'                         => $availability->CouchCommerce,
				'DaWanda'                               => $availability->DaWanda,
				'Flubit'                                => $availability->Flubit,
				'Fruugo'                                => $availability->Fruugo,
				'GartenXXL'                             => $availability->GartenXXL,
				'Gimahhot'                              => $availability->Gimahhot,
				'GoogleBase'                            => $availability->GoogleBase,
				'Grosshandel'                           => $availability->Grosshandel,
				'Hertie'                                => $availability->Hertie,
				'Hitmeister'                            => $availability->Hitmeister,
				'Hood'                                  => $availability->Hood,
				'Inactive'                              => $availability->Inactive,
				'IntervalSalesOrderQuantity'            => $availability->IntervalSalesOrderQuantity,
				'LaRedoute'                             => $availability->LaRedoute,
				'Laary'                                 => $availability->Laary,
				'MaximumSalesOrderQuantity'             => $availability->MaximumSalesOrderQuantity,
				'Mercateo'                              => $availability->Mercateo,
				'MinimumSalesOrderQuantity'             => $availability->MinimumSalesOrderQuantity,
				'NeckermannAtCrossDocking'              => $availability->NeckermannAtCrossDocking,
				'NeckermannAtCrossDockingProductType'   => $availability->NeckermannAtCrossDockingProductType,
				'NeckermannAtCrossDockingProvisionType' => $availability->NeckermannAtCrossDockingProvisionType,
				'NeckermannAtEnterprise'                => $availability->NeckermannAtEnterprise,
				'NeckermannAtEnterpriseProductType'     => $availability->NeckermannAtEnterpriseProductType,
				'NeckermannAtEnterpriseProvisionType'   => $availability->NeckermannAtEnterpriseProvisionType,
				'Otto'                                  => $availability->Otto,
				'Play'                                  => $availability->Play,
				'PlusDe'                                => $availability->PlusDe,
				'RakutenDe'                             => $availability->RakutenDe,
				'RakutenDeCategory'                     => $availability->RakutenDeCategory,
				'RakutenUk'                             => $availability->RakutenUk,
				'Restposten'                            => $availability->Restposten,
				'Shopgate'                              => $availability->Shopgate,
				'SumoScout'                             => $availability->SumoScout,
				'Tracdelight'                           => $availability->Tracdelight,
				'Twenga'                                => $availability->Twenga,
				'WebAPI'                                => $availability->WebAPI,
				'Webshop'                               => $availability->Webshop,
				'Yatego'                                => $availability->Yatego,
				'Zalando'                               => $availability->Zalando,
				'Zentralverkauf'                        => $availability->Zentralverkauf,
			];
		}
	}

	/**
	 * @param int $itemID
	 * @param PlentySoapObject_ItemTexts $texts
	 * @param PlentySoapObject_ItemFreeTextFields $freeTextFields
	 */
	private function processTexts($itemID, $texts, $freeTextFields)
	{
		if( !is_null( $texts ) )
		{
			// prepare texts & free texts for persistent storage
			$this->processedTexts[] = [
				'ItemID'                  => $itemID,
				'Lang'                    => $texts->Lang,
				'Name'                    => $texts->Name,
				'Name2'                   => $texts->Name2,
				'Name3'                   => $texts->Name3,
				'ShortDescription'        => $texts->ShortDescription,
				'LongDescription'         => $texts->LongDescription,
				'TechnicalData'           => $texts->TechnicalData,
				'MetaDescription'         => $texts->MetaDescription,
				'ItemDescriptionKeywords' => $texts->Keywords,
			];
		}

		if( !is_null( $freeTextFields ) )
		{
			$this->processedFreeTextFields[] = [
				'ItemID' => $itemID,
				'Free1'  => $freeTextFields->Free1,
				'Free2'  => $freeTextFields->Free2,
				'Free3'  => $freeTextFields->Free3,
				'Free4'  => $freeTextFields->Free4,
				'Free5'  => $freeTextFields->Free5,
				'Free6'  => $freeTextFields->Free6,
				'Free7'  => $freeTextFields->Free7,
				'Free8'  => $freeTextFields->Free8,
				'Free9'  => $freeTextFields->Free9,
				'Free10' => $freeTextFields->Free10,
				'Free11' => $freeTextFields->Free11,
				'Free12' => $freeTextFields->Free12,
				'Free13' => $freeTextFields->Free13,
				'Free14' => $freeTextFields->Free14,
				'Free15' => $freeTextFields->Free15,
				'Free16' => $freeTextFields->Free16,
				'Free17' => $freeTextFields->Free17,
				'Free18' => $freeTextFields->Free18,
				'Free19' => $freeTextFields->Free19,
				'Free20' => $freeTextFields->Free20,
			];
		}
	}


	/**
	 * @param int $itemID
	 * @param PlentySoapObject_ItemStock $stock
	 */
	private function processStock($itemID, $stock)
	{
		if( !is_null( $stock ) )
		{
			// prepare stock for persistent storage
			$this->processedStock[] = [
				'ItemID'                              => $itemID,
				'ChangeAvailablePositiveStock'        => $stock->ChangeAvailablePositiveStock,
				'ChangeAvailablePositiveStockVariant' => $stock->ChangeAvailablePositiveStockVariant,
				'ChangeNotAvailableNoStock'           => $stock->ChangeNotAvailableNoStock,
				'ChangeNotAvailableNoStockVariant'    => $stock->ChangeNotAvailableNoStockVariant,
				'Limitation'                          => $stock->Limitation,
				'MainWarehouseID'                     => $stock->MainWarehouseID,
				'StorageLocation'                     => $stock->StorageLocation,
				'WebshopInvisibleNoStock'             => $stock->WebshopInvisibleNoStock,
				'WebshopVisiblePositiveStock'         => $stock->WebshopVisiblePositiveStock,
			];
		}
	}

	/**
	 * @param int $itemID
	 * @param PlentySoapObject_ItemOthers $others
	 */
	private function processOthers($itemID, $others)
	{
		if( !is_null( $others ) )
		{
			// prepare others for persistent storage
			$this->processedOthers[] = [
				'ItemID'              => $itemID,
				'AuctionTitleLinkage' => $others->AuctionTitleLinkage,
				'Coupon'              => $others->Coupon,
				'CustomerClass'       => $others->CustomerClass,
				'EbayAcceptValueMin'  => $others->EbayAcceptValueMin,
				'EbayCategory1'       => $others->EbayCategory1,
				'EbayCategory2'       => $others->EbayCategory2,
				'EbayDenyValueBelow'  => $others->EbayDenyValueBelow,
				'EbayPreset'          => $others->EbayPreset,
				'EbayShopCategory1'   => $others->EbayShopCategory1,
				'EbayShopCategory2'   => $others->EbayShopCategory2,
				'ItemApiCondition'    => $others->ItemApiCondition,
				'ItemCondition'       => $others->ItemCondition,
				'ItemEvaluation'      => $others->ItemEvaluation,
				'ItemLinkage'         => $others->ItemLinkage,
				'PornographicContent' => $others->PornographicContent,
				'Position'            => $others->Position,
				'RevenueAccount'      => $others->RevenueAccount,
				'SerialNumber'        => $others->SerialNumber,
				'ShippingPackage'     => $others->ShippingPackage,
				'Subscription'        => $others->Subscription,
			];
		}
	}
}
