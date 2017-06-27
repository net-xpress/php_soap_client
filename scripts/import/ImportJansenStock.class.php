<?php
require_once realpath( dirname( __FILE__ ).'/../../' ).'/config/basic.inc.php';
require_once ROOT.'lib/db/DBQuery.class.php';
require_once ROOT.'lib/log/Logger.class.php';

class ImportJansenStock
{
	private $verbose = true;

	private $csvFilePath;
	private $currentTime;
	private $jansenStock            = [];
	private $jansenStockDifferences = [];

	/**
	 * ImportJansenStock constructor.
	 * @param string $csvFilePath
	 */
	public function __construct($csvFilePath)
	{
		$this->csvFilePath = $csvFilePath;
		$this->currentTime = filemtime( $csvFilePath );
	}

	public function execute()
	{

		$this->getLogger()->info( __FUNCTION__." reading jansen stock data from {$this->csvFilePath}" );

		list( $lastUpdate, , ) = DBUtils::lastUpdateStart( __CLASS__ );

		// if file modification date younger than last import ...
		if( $this->currentTime > $lastUpdate )
		{
			// ... then read the file ...
			$csvFile = fopen( $this->csvFilePath, 'r' );
			if( $csvFile )
			{
				// ... for every line ...
				while( ($csvData = fgetcsv( $csvFile, 1000, ';' )) !== false )
				{
					// ... eliminate dummy fields ...
					if( count( $csvData ) === 3 )
					{
						list( $externalItemID, $physicalStock, $ean, ) = [iconv( "Windows-1250", "UTF-8", $csvData[0] ), floatval( $csvData[1] ), $csvData[2],];

						// ... check ean for validity and for jansen origin
						if( $this->isValidEan( $ean ) && strpos( $ean, '85955783' ) === 0 )
						{
							// ... then store record
							$this->jansenStock[] = [
								'EAN'            => $ean,
								'ExternalItemID' => $externalItemID,
								'PhysicalStock'  => $physicalStock,
							];
						}
						else
						{
							// missing or malformed ean, do nothing...
						}
					}
				}
				// ... then persistently store all records in db
				$this->storeToDB();
				DBUtils::lastUpdateFinish( $this->currentTime, __CLASS__ );
			}
			else
			{
				//... or error
				$this->getLogger()->debug( __FUNCTION__." unable to read file {$this->csvFilePath}" );
			}
			fclose( $csvFile );
		}
		else
		{
			$this->getLogger()->info( __FUNCTION__." no new data" );
		}
	}

	/**
	 * @param string $ean
	 * @return bool
	 */
	private function isValidEan($ean)
	{
		if( !preg_match( "/^[0-9]{13}$/", $ean ) )
		{
			return false;
		}

		$sum = (
				$ean[1] + $ean[3] + $ean[5] +
				$ean[7] + $ean[9] + $ean[11]
			) * 3 + (
				$ean[0] + $ean[2] + $ean[4] +
				$ean[6] + $ean[8] + $ean[10]
			);

		$check = (ceil( $sum / 10 )) * 10 - $sum;

		return $check == $ean[12];
	}

	private function storeToDB()
	{
		$recordCount = count( $this->jansenStock );

		if( $recordCount > 0 )
		{
			$this->getLogger()->info( __FUNCTION__." storing $recordCount stock records from jansen" );

			$this->generateDifferenceSet();

			$differenceCount = count( $this->jansenStockDifferences );

			if( $differenceCount > 0 )
			{
				$this->getLogger()->info( __FUNCTION__." storing $differenceCount difference records from jansen" );

				DBQuery::getInstance()->insert( "INSERT INTO JansenTransactionHead".DBUtils::buildInsert( [
						'TransactionID' => null,
						'Timestamp'     => $this->currentTime,
					] ) );

				$transactionID = DBQuery::getInstance()->getInsertId();

				DBQuery::getInstance()->insert( "INSERT INTO JansenTransactionItem".DBUtils::buildMultipleInsert( array_map( function ($row) use ($transactionID)
					{
						$row['TransactionID'] = $transactionID;
						return $row;
					}, $this->jansenStockDifferences ) ) );
			}

			// delete old data
			DBQuery::getInstance()->truncate( "TRUNCATE JansenStockData" );

			DBQuery::getInstance()->insert( "INSERT INTO JansenStockData".DBUtils::buildMultipleInsert( $this->jansenStock ) );
		}
	}

	private function generateDifferenceSet()
	{
		DBQuery::getInstance()->create( "CREATE TEMPORARY TABLE `JansenStockDataNew` (
  `EAN`            BIGINT(13) NOT NULL,
  `ExternalItemID` VARCHAR(45)    DEFAULT NULL,
  `PhysicalStock`  DECIMAL(10, 4) DEFAULT NULL,
  PRIMARY KEY (`EAN`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci" );

		DBQuery::getInstance()->insert( "INSERT INTO `JansenStockDataNew`".DBUtils::buildMultipleInsert( $this->jansenStock ) );

		$dbResult = DBQuery::getInstance()->select( "SELECT
  oldStock.EAN,
  oldStock.ExternalItemID,
  newStock.PhysicalStock - oldStock.PhysicalStock AS Difference
FROM
  JansenStockData AS oldStock
  LEFT JOIN
  JansenStockDataNew AS newStock
    ON
      oldStock.EAN = newStock.EAN
      AND
      oldStock.ExternalItemID = newStock.ExternalItemID
WHERE
  newStock.PhysicalStock - oldStock.PhysicalStock != 0
" );
		while( $row = $dbResult->fetchAssoc() )
		{
			$this->jansenStockDifferences[] = $row;
		}

		DBQuery::getInstance()->drop( "DROP TABLE `JansenStockDataNew`" );
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
