<?php
require_once realpath( dirname( __FILE__ ).'/../../' ).'/config/basic.inc.php';
require_once ROOT.'lib/db/DBQuery.class.php';
require_once ROOT.'lib/log/Logger.class.php';
require_once ROOT.'lib/soap/tools/SKUHelper.php';

class CalculateDailyNeed
{
	private $verbose = true;

	private $currentTime;
	private $articleData = [];
	private $config      = [];

	public function __construct()
	{
		// init time
		$this->currentTime = time();

		// assemble config
		$dbResult = DBQuery::getInstance()->select( "SELECT ConfigKey, ConfigValue, ConfigType FROM `MetaConfig` WHERE Domain = 'stock'" );
		while( $currentConfig = $dbResult->fetchAssoc() )
		{
			if( $currentConfig['ConfigType'] === 'int' )
			{
				$this->config[$currentConfig['ConfigKey']] = intval( $currentConfig['ConfigValue'] );
			}
			else
			{
				$this->config[$currentConfig['ConfigKey']] = floatval( $currentConfig['ConfigValue'] );
			}
		}

		// ensure config contains all necessary values

		foreach( [
					 'CalculationActive',
					 'CalculationTimeA',
					 'CalculationTimeB',
					 'SpikeTolerance',
					 'StandardDeviationFactor',
					 'MinimumToleratedSpikesA',
					 'MinimumToleratedSpikesB',
					 'MinimumOrdersA',
					 'MinimumOrdersB',
				 ] as $requiredKey )
		{
			if( !array_key_exists( $requiredKey, $this->config ) )
			{
				$this->debug( __FUNCTION__." : stock-domain config['$requiredKey'] is missing" );
				die();
			}
		}

		// set group_concat_max_len to reasonable value to prevent cropping of article quantities list
		DBQuery::getInstance()->Set( "SET SESSION group_concat_max_len = 4096" );

		// clear daily need db before start so there's no old leftover
		DBQuery::getInstance()->truncate( "TRUNCATE TABLE CalculatedDailyNeeds" );
	}

	public function execute()
	{
		if( $this->config['CalculationActive'] != 1 )
		{
			$this->debug( __FUNCTION__." : skipping CalculateDailyNeed" );
			return;
		}

		$this->debug( __FUNCTION__." : calculation daily need" );

		// retrieve latest orders from db for calculation time A
		$articleResultA                   = DBQuery::getInstance()->select( $this->getIntervalQuery( $this->config['CalculationTimeA'] ) );
		$articleResultAWithActivationDate = DBQuery::getInstance()->select( $this->getIntervalQuery( $this->config['CalculationTimeA'], true ) );
		$this->getLogger()->info( __FUNCTION__.' : retrieved '.$articleResultA->getNumRows().' article variants for calculation time a'.($articleResultAWithActivationDate->getNumRows() > 0 ? ' + '.$articleResultAWithActivationDate->getNumRows().' with activation date' : '') );

		// calculate data for calculation time A
		while( $aCurrentArticle = $articleResultA->fetchAssoc() )
		{
			$this->processArticle( $aCurrentArticle, 'A' );
		}
		while( $aCurrentArticle = $articleResultAWithActivationDate->fetchAssoc() )
		{
			$this->processArticle( $aCurrentArticle, 'A', true );
		}

		// retrieve latest orders from db for calculation time B
		$articleResultB                   = DBQuery::getInstance()->select( $this->getIntervalQuery( $this->config['CalculationTimeB'] ) );
		$articleResultBWithActivationDate = DBQuery::getInstance()->select( $this->getIntervalQuery( $this->config['CalculationTimeB'], true ) );
		$this->getLogger()->info( __FUNCTION__.' : retrieved '.$articleResultB->getNumRows().' article variants for calculation time b'.($articleResultBWithActivationDate->getNumRows() > 0 ? ' + '.$articleResultBWithActivationDate->getNumRows().' with activation date' : '') );

		// for every article in calculation time B do:
		// combine A and B
		while( $aCurrentArticle = $articleResultB->fetchAssoc() )
		{
			$this->processArticle( $aCurrentArticle, 'B' );
		}
		while( $aCurrentArticle = $articleResultBWithActivationDate->fetchAssoc() )
		{
			$this->processArticle( $aCurrentArticle, 'B', true );
		}

		$this->storeToDB();
	}

	/**
	 * prepare query to get total quantities and spike-toleration-data for all articles from db
	 *
	 * @param int $daysBack # of days to consider when collecting order data
	 * @param bool $withActivationDate (default) false, when articles with activation date are to be ignored, true
	 *                                   otherwise
	 * @param string $sku (default) null to get all articles, a specific SKU to get just the specific
	 *                                   article's variants if $withActivationDate is true
	 *
	 * @return string query
	 */
	private function getIntervalQuery($daysBack, $withActivationDate = false, $sku = null)
	{

		/** holds SQL statement part to adjust query for activation date processing */
		$activationDateString = null;

		if( $withActivationDate && !isset( $sku ) )
		{
			// get all articles with given activation date
			$activationDateString = "ItemFreeTextFields.Free5 > \"\"";
		}
		else
		{
			if( $withActivationDate && isset( $sku ) )
			{
				// get a specific article (with all of it's variants) with given activation date
				$activationDateString = "OrderItem.SKU = \"$sku\"\nAND\n\tItemFreeTextFields.Free5 > \"\"";
			}

			else
			{
				// get all articles without activation date
				$activationDateString = "\n\tItemFreeTextFields.Free5 = \"\"";
			}
		}

		return "SELECT
	OrderItem.ItemID,
	OrderItem.SKU,
	SUM(CAST(OrderItem.Quantity AS SIGNED)) AS `quantity`,
	AVG(`quantity`) + STDDEV(`quantity`) * {$this->config['StandardDeviationFactor']} AS `range`,
	CAST(GROUP_CONCAT(IF(OrderItem.Quantity > 0 ,CAST(OrderItem.Quantity AS SIGNED),NULL) ORDER BY OrderItem.Quantity DESC SEPARATOR \",\") AS CHAR) AS `quantities`,
	ItemsBase.Marking1ID,
	ItemFreeTextFields.Free5 AS ActivationDate
FROM
	OrderItem
LEFT JOIN
	(OrderHead, ItemsBase, ItemFreeTextFields, ItemAvailability) ON (OrderHead.OrderID = OrderItem.OrderID AND OrderItem.ItemID = ItemsBase.ItemID AND ItemsBase.ItemID = ItemFreeTextFields.ItemID AND ItemsBase.ItemID = ItemAvailability.ItemID)
WHERE
	$activationDateString
AND
	(OrderHead.OrderTimestamp BETWEEN {$this->currentTime} -( 86400 *  $daysBack ) AND {$this->currentTime} )
AND
	(OrderHead.OrderStatus < 8 OR OrderHead.OrderStatus >= 9)
AND
	OrderType = 'order'
AND
	ItemAvailability.Inactive = 0
GROUP BY
	OrderItem.SKU
ORDER BY
	ItemID";
	}

	/**
	 * calculate spike cleared daily need and additional raw-data for current article combined from both calculation
	 * times as follows: dailyNeed = (dailyNeedA + dailyNeedB) / 2
	 *
	 * @param array $aCurrentArticle associative array of current article
	 * @param string $sAorB select calculation time a or b
	 * @param bool $withActivationDate (default) false, when articles with activation date are to be ignored, true otherwise
	 */
	private function processArticle(array $aCurrentArticle, $sAorB, $withActivationDate = false)
	{

		/** holds # of days to be considered during daily need computation */
		$daysBack     = $this->config['CalculationTime'.$sAorB];
		$isNewArticle = 0;

		// if activation date given ...
		if( $withActivationDate )
		{

			// ... potentially update article variant data (if date is in current period)
			$skipArticle = false;
			$this->updateArticleOnActivationDate( $aCurrentArticle, $daysBack, $isNewArticle, $skipArticle );

			if( $skipArticle )
			{
				return;
			}
		}

		/** current article's ItemID */
		$ItemID = null;

		/** current article's variant id (0 for non-variant articles) */
		$AttributeValueSetID = null;

		list( $ItemID, , $AttributeValueSetID ) = SKU2Values( $aCurrentArticle['SKU'] );

		/** holds # of skipped orders after adjusting quantity */
		$skippedIndex = null;

		/** spike-cleared total quantity */
		$adjustedQuantity = $this->getArticleAdjustedQuantity( explode( ',', $aCurrentArticle['quantities'] ), $aCurrentArticle['quantity'], $aCurrentArticle['range'], $this->config['MinimumToleratedSpikes'.$sAorB], $this->config['MinimumOrders'.$sAorB], $skippedIndex );

		// if processing period is A and article isn't new ...
		if( $sAorB === 'A' )
		{
			// ... add data for calculation time A
			$this->articleData[$aCurrentArticle['SKU']] = [
				'ItemID'              => $ItemID,
				'AttributeValueSetID' => $AttributeValueSetID,
				'DailyNeed'           => ($adjustedQuantity / $daysBack) / 2,
				'LastUpdate'          => $this->currentTime,
				'QuantitiesA'         => $aCurrentArticle['quantities'],
				'SkippedA'            => $skippedIndex,
				'QuantitiesB'         => '0',
				'SkippedB'            => '0',
				'New'                 => $isNewArticle,
			];
		} // ... or if period is B ...
		else
		{
			if( $sAorB === 'B' )
			{
				// ... add data for calculation time B

				// if there's an existing record ...
				if( array_key_exists( $aCurrentArticle['SKU'], $this->articleData ) )
				{
					// ... then use existing record
					$this->articleData[$aCurrentArticle['SKU']]['DailyNeed']   += ($adjustedQuantity / $daysBack) / 2;
					$this->articleData[$aCurrentArticle['SKU']]['QuantitiesB'] = $aCurrentArticle['quantities'];
					$this->articleData[$aCurrentArticle['SKU']]['SkippedB']    = $skippedIndex;
				}
				else
				{
					// ... otherwise create new one

					$this->articleData[$aCurrentArticle['SKU']] = [
						'ItemID'              => $ItemID,
						'AttributeValueSetID' => $AttributeValueSetID,
						'DailyNeed'           => $adjustedQuantity / $daysBack,
						'LastUpdate'          => $this->currentTime,
						'QuantitiesA'         => '0',
						'SkippedA'            => '0',
						'QuantitiesB'         => $aCurrentArticle['quantities'],
						'SkippedB'            => $skippedIndex,
						'New'                 => $isNewArticle,
					];
				}
			}
			else
			{
				$this->debug( __FUNCTION__." : wrong syntax of \$sAorB : .$sAorB, aborting" );
				die();
			}
		}
	}

	/**
	 * @param array $aCurrentArticle
	 * @param int $daysBack
	 * @param int $isNewArticle
	 * @param bool $skipArticle
	 */
	private function updateArticleOnActivationDate(&$aCurrentArticle, &$daysBack, &$isNewArticle, &$skipArticle)
	{
		// calculate (now - ActivationDate) in days
		$activationTimeDifference = $this->getActivationTimeDifference( $aCurrentArticle['ActivationDate'] );

		// check if a date is given which doesn't match ...
		if( is_null( $activationTimeDifference ) )
		{
			// ... report error and skip article
			$this->debug( __FUNCTION__." article {$aCurrentArticle['SKU']} has malformed activation date: {$aCurrentArticle['ActivationDate']}" );
			$skipArticle = true;
		} // ... or if activation date is in the future ...
		else
		{
			if( $activationTimeDifference < 0 )
			{
				// ... then no further processing is needed, skip article
				$skipArticle = true;
			} // ... or if date is in current calculation period ...
			else
			{
				if( $activationTimeDifference < $daysBack && $activationTimeDifference < $this->config['CalculationTimeA'] )
				{
					// ... then adjust calculation period
					$daysBack     = $activationTimeDifference;
					$isNewArticle = 1;

					$currentArticleResult = DBQuery::getInstance()->select( $this->getIntervalQuery( $daysBack, true, $aCurrentArticle['SKU'] ) );
					// ... if there's any data for the adjusted period
					if( $aSpecificArticle = $currentArticleResult->fetchAssoc() )
					{
						// ... then update the current article
						$aCurrentArticle = $aSpecificArticle;
					}
					else
					{
						// ... otherwise skip the article
						$skipArticle = true;
					}
				}
			}
		}
	}

	/**
	 * @param string $sActivationDate
	 *
	 * @return int|null
	 */
	private function getActivationTimeDifference($sActivationDate)
	{
		// check if activationdate given (match against regular german date format like dd.mm.yyyy, tolerating -:/. as delimiter) ...
		if( preg_match( '/(((?:[0-2]?\d{1})|(?:[3][01]{1}))[-:\/.]([0]?[1-9]|[1][012])[-:\/.]((?:[1]{1}\d{1}\d{1}\d{1})|(?:[2]{1}\d{3})))(?![\d])/', $sActivationDate, $matches ) )
		{
			// ... then return difference to current time in days
			$date = new DateTime();
			$date->setDate( $matches[4], $matches[3], $matches[2] );
			$date->setTime( 0, 0, 0 );

			return floor( ($this->currentTime - $date->format( 'U' )) / 86400 );
		}
		else
		{
			// ... otherwise return null
			return null;
		}
	}

	/**
	 * compute an adjusted total quantity for given quantities (current article) which is cleared of untolerated spikes
	 *
	 * @param array $aQuantities Array of quantities for current article to discard all spikes from
	 * @param int $quantity total quantity of the current article
	 * @param int $range range for quantities, quantities above this range have to be checked for
	 *                                  tolerated spikes
	 * @param int $minToleratedSpikes minimum # of spikes that can be tolerated
	 * @param int $minOrders minimum # of orders necessary to consider the current article
	 * @param int $index return value for the # of skipped orders
	 *
	 * @return int total quantity minus discarded spikes
	 */
	private function getArticleAdjustedQuantity(array $aQuantities, $quantity, $range, $minToleratedSpikes, $minOrders, &$index)
	{
		// skip all orders if # of orders is below given minimum ...
		if( count( $aQuantities ) < $minOrders )
		{
			$index = count( $aQuantities );

			return 0;
		}

		// ... otherwise check quantities in descending order
		for( $index = 0, $maxQuantities = count( $aQuantities ); $index < $maxQuantities; ++$index )
		{
			// if we are already below the confidence range ...
			if( $aQuantities[$index] <= $range )
			{
				// ... then stop the loop
				break;
			}
			else
			{
				// ... otherwise we need to check for tolerated spikes

				// get sub array
				$aSpikes = array_slice( $aQuantities, $index, $minToleratedSpikes );

				// assume all spikes are in tolerance range
				$tolerateSpikes = true;

				// check subarray
				for( $spikeIndex = 1, $maxSpikes = count( $aSpikes ); $spikeIndex < $maxSpikes; ++$spikeIndex )
				{

					// if at least one element is below spike tolerance range ...
					if( $aSpikes[$spikeIndex] < $aSpikes[0] * (1 - $this->config['SpikeTolerance']) )
					{
						// ... then skip spike and break off the loop to try the next one...
						$quantity       -= $aQuantities[$index];
						$tolerateSpikes = false;
						break;
					}
				}

				// found min. number of spike fitting in tolerance range, so all the rest is "in"
				if( $tolerateSpikes )
				{
					break;
				}
			}
		}

		return $quantity;
	}

	/**
	 * store article data to db
	 */
	private function storeToDB()
	{
		$countDailyNeed = count( $this->articleData );

		if( $countDailyNeed > 0 )
		{
			$this->debug( __FUNCTION__." storing $countDailyNeed daily need records" );
			DBQuery::getInstance()->insert( 'INSERT INTO `CalculatedDailyNeeds`'.DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->articleData ) );
		}
	}

	protected function debug($message)
	{
		if( $this->verbose === true )
		{
			$this->getLogger()->debug( $message );
		}
	}

	protected function getLogger()
	{
		return Logger::instance( __CLASS__ );
	}

}