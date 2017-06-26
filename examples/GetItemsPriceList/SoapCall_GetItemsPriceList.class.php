<?php
require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'RequestContainer_GetItemsPriceLists.class.php';

class SoapCall_GetItemsPriceList extends PlentySoapCall
{
	const MAX_PRICE_SETS_PER_PAGE = 200;

	private $priceSets = [];

	public function __construct()
	{
		parent::__construct( __CLASS__ );

		DBQuery::getInstance()->truncate( "TRUNCATE TABLE `ItemsPriceSets`" );
	}

	public function execute()
	{
		$this->getLogger()->info( __FUNCTION__." fetching items price lists from plenty" );

		try
		{
			// get all possible Item Variants
			$dbResult = DBQuery::getInstance()->select( "SELECT ItemID FROM `ItemsBase`" );
			$maxPage  = ceil( $dbResult->getNumRows() / self::MAX_PRICE_SETS_PER_PAGE );

			$this->getLogger()->info( __FUNCTION__." found {$dbResult->getNumRows()} items, pages: $maxPage" );

			// for every 200 variants ...
			for( $page = 0; $page < $maxPage; $page++ )
			{
				// ... prepare a separate request
				$preparedRequest = new RequestContainer_GetItemsPriceLists( self::MAX_PRICE_SETS_PER_PAGE );
				while( !$preparedRequest->isFull() && $current = $dbResult->fetchAssoc() )
				{
					$preparedRequest->add( $current['ItemID'] );
				}

				// ... then do the soap call ...
				$response = $this->getPlentySoap()->GetItemsPriceLists( $preparedRequest->getRequest() );

				// ... if successful ...
				if( $response->Success == true )
				{
					$this->debug( __FUNCTION__." Request Success - page : ".($page + 1) );

					// ... then process response
					$this->responseInterpretation( $response );
				}
				else
				{
					// ... otherwise log error and try next request
					$this->getLogger()->debug( __FUNCTION__.' Request Error' );
				}
			}

			// when done store all retrieved data to db
			$this->storeToDB();

		} catch( Exception $e )
		{
			$this->onExceptionAction( $e );
		}
	}

	private function responseInterpretation(PlentySoapResponse_GetItemsPriceLists $response)
	{
		if( is_array( $response->ItemsPriceList->item ) )
		{
			foreach( $response->ItemsPriceList->item as $itemsPriceList )
			{
				if( is_array( $itemsPriceList->ItemPriceSets->item ) )
				{
					foreach( $itemsPriceList->ItemPriceSets->item as $itemPriceSet )
					{
						$this->processPriceSet( $itemsPriceList->ItemID, $itemPriceSet );
					}
				}
				else
				{
					$this->processPriceSet( $itemsPriceList->ItemID, $itemsPriceList->ItemPriceSets->item );
				}
			}
		}
		else
		{
			if( is_array( $response->ItemsPriceList->item->ItemPriceSets->item ) )
			{
				foreach( $response->ItemsPriceList->item->ItemPriceSets->item as $itemPriceSet )
				{
					$this->processPriceSet( $response->ItemsPriceList->item->ItemID, $itemPriceSet );
				}
			}
			else
			{
				$this->processPriceSet( $response->ItemsPriceList->item->ItemID, $response->ItemsPriceList->item->ItemPriceSets->item );
			}
		}
	}

	/**
	 * @param int $itemID
	 * @param PlentySoapObject_ItemPriceSet $itemPriceSet
	 */
	private function processPriceSet($itemID, $itemPriceSet)
	{
		$this->priceSets[] = [
			'ItemID'                       => $itemID,
			'PriceID'                      => $itemPriceSet->PriceID,
			'Price'                        => $itemPriceSet->Price,
			'Price1'                       => $itemPriceSet->Price1,
			'Price2'                       => $itemPriceSet->Price2,
			'Price3'                       => $itemPriceSet->Price3,
			'Price4'                       => $itemPriceSet->Price4,
			'Price5'                       => $itemPriceSet->Price5,
			'Price6'                       => $itemPriceSet->Price6,
			'Price7'                       => $itemPriceSet->Price7,
			'Price8'                       => $itemPriceSet->Price8,
			'Price9'                       => $itemPriceSet->Price9,
			'Price10'                      => $itemPriceSet->Price10,
			'Price11'                      => $itemPriceSet->Price11,
			'Price12'                      => $itemPriceSet->Price12,
			'RebateLevelPrice6'            => $itemPriceSet->RebateLevelPrice6,
			'RebateLevelPrice7'            => $itemPriceSet->RebateLevelPrice7,
			'RebateLevelPrice8'            => $itemPriceSet->RebateLevelPrice8,
			'RebateLevelPrice9'            => $itemPriceSet->RebateLevelPrice9,
			'RebateLevelPrice10'           => $itemPriceSet->RebateLevelPrice10,
			'RebateLevelPrice11'           => $itemPriceSet->RebateLevelPrice11,
			'PurchasePriceNet'             => $itemPriceSet->PurchasePriceNet,
			'RRP'                          => $itemPriceSet->RRP,
			'BestOfferAutoAcceptancePrice' => $itemPriceSet->BestOfferAutoAcceptancePrice,
			'CarryingCosts'                => $itemPriceSet->CarryingCosts,
			'HeightInMM'                   => $itemPriceSet->HeightInMM,
			'InventoryCost'                => $itemPriceSet->InventoryCost,
			'ItemShipping3'                => $itemPriceSet->ItemShipping3,
			'ItemShipping4'                => $itemPriceSet->ItemShipping4,
			'LengthInMM'                   => $itemPriceSet->LengthInMM,
			'Lot'                          => $itemPriceSet->Lot,
			'OperatingCostsPercental'      => $itemPriceSet->OperatingCostsPercental,
			'Package'                      => $itemPriceSet->Package,
			'PackagingUnit'                => $itemPriceSet->PackagingUnit,
			'Position'                     => $itemPriceSet->Position,
			'ScoMinimumPrice'              => $itemPriceSet->ScoMinimumPrice,
			'ShowOnly'                     => $itemPriceSet->ShowOnly,
			'TollPercental'                => $itemPriceSet->TollPercental,
			'TypeOfPackage'                => $itemPriceSet->TypeOfPackage,
			'Unit'                         => $itemPriceSet->Unit,
			'Unit1'                        => $itemPriceSet->Unit1,
			'Unit2'                        => $itemPriceSet->Unit2,
			'UnitLoadDevice'               => $itemPriceSet->UnitLoadDevice,
			'VAT'                          => $itemPriceSet->VAT,
			'WeightInGramm'                => $itemPriceSet->WeightInGramm,
			'WidthInMM'                    => $itemPriceSet->WidthInMM,
		];
	}

	private function storeToDB()
	{
		$countPriceSets = count( $this->priceSets );

		if( $countPriceSets > 0 )
		{
			$this->getLogger()->info( __FUNCTION__." storing $countPriceSets price sets" );
			DBQuery::getInstance()->insert( "INSERT INTO `ItemsPriceSets`".DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->priceSets ) );

		}
	}


}