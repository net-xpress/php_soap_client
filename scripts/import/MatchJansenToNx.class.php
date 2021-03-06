<?php
require_once realpath( dirname( __FILE__ ).'/../../' ).'/config/basic.inc.php';
require_once ROOT.'lib/db/DBQuery.class.php';
require_once ROOT.'lib/log/Logger.class.php';

class MatchJansenToNx
{
	private $verbose = true;

	const JANSEN_WAREHOUSE_ID = 2;
	const DEFAULT_REASON = 301;    // Warenkorrektur (WK)
	const DEFAULT_N_LATEST_TRANSACTIONS = 3;

	private $matched   = [];
	private $unmatched = [];

	public function execute()
	{
		/*
		 * get all item variants with jansen EAN already matched against
		 * jansen data which have been updated in the last N transactions
		 *
		 */
		$dbResult = DBQuery::getInstance()->select( $this->getMatchedQuery() );

		$this->getLogger()->info( __FUNCTION__.": found {$dbResult->getNumRows()} item variants with jansen ean that received an update..." );

		// for every item variant ...
		while( $itemVariant = $dbResult->fetchAssoc() )
		{
			// ... handle matched item variant
			$this->matched[] = [
				'ItemID'              => $itemVariant['ItemID'],
				'AttributeValueSetID' => $itemVariant['AttributeValueSetID'],
				'PriceID'             => $itemVariant['PriceID'],
				'WarehouseID'         => self::JANSEN_WAREHOUSE_ID,
				'StorageLocation'     => 0,
				'PhysicalStock'       => $itemVariant['PhysicalStock'],
				'Reason'              => self::DEFAULT_REASON,
			];
		}

		$dbResult = DBQuery::getInstance()->select( $this->getUnmatchedQuery() );

		$this->getLogger()->info( __FUNCTION__.": found {$dbResult->getNumRows()} item variants with jansen ean that didn't match..." );

		// for every item variant ...
		while( $itemVariant = $dbResult->fetchAssoc() )
		{
			// ... handle matched item variant
			$this->unmatched[] = [
				'ItemID'              => $itemVariant['ItemID'],
				'AttributeValueSetID' => $itemVariant['AttributeValueSetID'],
			];
		}

		$this->storeToDB();
	}

	private function getMatchedQuery()
	{
		return "SELECT
  nx.ItemID,
  nx.AttributeValueSetID,
  nx.PriceID,
  jsd.PhysicalStock
FROM JansenTransactionItem AS jti
  JOIN /* select last n transactions */
  (
    SELECT TransactionID
    FROM JansenTransactionHead
    ORDER BY TransactionID DESC
    LIMIT ".self::DEFAULT_N_LATEST_TRANSACTIONS."
  ) AS jth
    ON jti.TransactionID = jth.TransactionID
  JOIN JansenStockData AS jsd
    ON (jti.EAN = jsd.EAN)
       AND (jti.ExternalItemID = jsd.ExternalItemID)
  JOIN /* get all nx products (itemID, avsID, priceID, EAN, extID) with a jansen ean, which is active and not marked */
  (
    SELECT
      i.ItemID,
      CASE WHEN (avs.AttributeValueSetID IS NULL)
        THEN
          0
      ELSE
        avs.AttributeValueSetID
      END AS AttributeValueSetID,
      ps.PriceID,
      CASE WHEN (avs.AttributeValueSetID IS NULL)
        THEN
          i.EAN2
      ELSE
        avs.EAN2
      END AS EAN,
      i.ExternalItemID
    FROM
      ItemsBase AS i
      LEFT JOIN ItemAvailability AS avail
        ON i.ItemID = avail.ItemID
      LEFT JOIN ItemAttributeValueSets AS avs
        ON i.ItemID = avs.ItemID
      JOIN ItemsPriceSets AS ps
        ON i.ItemID = ps.ItemID
    WHERE
      CASE WHEN (avs.AttributeValueSetID IS NULL)
        THEN
          i.EAN2
      ELSE
        avs.EAN2
      END BETWEEN 8595578300000 AND 8595578399999
      AND
      i.Marking1ID != 4
      AND
      avail.Inactive = 0
  ) AS nx
    ON
      (jti.EAN = nx.EAN)
      AND
      LOWER(
          CASE WHEN (nx.AttributeValueSetID = 0)
            THEN
              nx.ExternalItemID
          ELSE
            CASE WHEN (nx.AttributeValueSetID = 1)
              THEN
                REPLACE(nx.ExternalItemID, ' [R/G] ', 'G')
            WHEN (nx.AttributeValueSetID = 2)
              THEN
                REPLACE(nx.ExternalItemID, ' [R/G] ', 'R')
            WHEN (nx.AttributeValueSetID = 23)
              THEN
                REPLACE(nx.ExternalItemID, '+[Color]', 'RED')
            WHEN (nx.AttributeValueSetID = 24)
              THEN
                REPLACE(nx.ExternalItemID, '+[Color]', 'YELLOW')
            WHEN (nx.AttributeValueSetID = 25)
              THEN
                REPLACE(nx.ExternalItemID, '+[Color]', 'PURPLE')
            WHEN (nx.AttributeValueSetID = 26)
              THEN
                REPLACE(nx.ExternalItemID, '+[Color]', 'WHITE')
            WHEN (nx.AttributeValueSetID = 27)
              THEN
                REPLACE(nx.ExternalItemID, '+[Color]', 'PINK')
            WHEN (nx.AttributeValueSetID = 28)
              THEN
                REPLACE(nx.ExternalItemID, '+[Color]', 'DARKBLUE')
            WHEN (nx.AttributeValueSetID = 29)
              THEN
                REPLACE(nx.ExternalItemID, '+[Color]', 'DARKGREEN')
            WHEN (nx.AttributeValueSetID = 30)
              THEN
                REPLACE(nx.ExternalItemID, '+[Color]', 'ORANGE')
            ELSE
              'xxx'
            END
          END
      ) = LOWER(jti.ExternalItemID)
GROUP BY /* skip duplicate entries */
  jti.EAN";
	}

	private function getUnmatchedQuery()
	{
		return "SELECT
  i.ItemID,
  CASE WHEN (avs.AttributeValueSetID IS NULL)
    THEN
      0
  ELSE
    avs.AttributeValueSetID
  END                 AS AttributeValueSetID,
  jsd.EAN IS NOT NULL AS IsEanMatched
FROM
  ItemsBase AS i
  LEFT JOIN ItemAvailability AS avail
    ON i.ItemID = avail.ItemID
  LEFT JOIN ItemAttributeValueSets AS avs
    ON i.ItemID = avs.ItemID
  JOIN ItemsPriceSets AS ps
    ON i.ItemID = ps.ItemID
  LEFT JOIN JansenStockData AS jsd
    ON
      CASE WHEN (avs.AttributeValueSetID IS NULL)
        THEN
          i.EAN2
      ELSE
        avs.EAN2
      END = jsd.EAN
WHERE
  CASE WHEN (avs.AttributeValueSetID IS NULL)
    THEN
      i.EAN2
  ELSE
    avs.EAN2
  END BETWEEN 8595578300000 AND 8595578399999
  AND i.Marking1ID != 4
  AND avail.Inactive = 0
  AND jsd.EAN IS NULL";
	}

	private function storeToDB()
	{
		$countMatched   = count( $this->matched );
		$countUnmatched = count( $this->unmatched );

		if( $countMatched > 0 )
		{
			DBQuery::getInstance()->truncate( 'TRUNCATE `SetCurrentStocks`' );
			DBQuery::getInstance()->insert( 'INSERT INTO `SetCurrentStocks`'.DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->matched ) );

			$this->getLogger()->debug( __FUNCTION__.": storing $countMatched matched stock records for update." );
		}

		if( $countUnmatched > 0 )
		{
			DBQuery::getInstance()->truncate( 'TRUNCATE JansenStockUnmatched' );
			DBQuery::getInstance()->insert( 'INSERT INTO `JansenStockUnmatched`'.DBUtils::buildMultipleInsertOnDuplicateKeyUpdate( $this->unmatched ) );

			$this->getLogger()->debug( __FUNCTION__.": storing $countUnmatched unmatched stock records." );
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