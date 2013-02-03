<?php
require_once 'DBAbstractConnector.abstract.php';

require_once ROOT.'config/db.inc.php';
require_once ROOT.'lib/log/Logger.class.php';

/**
 * DBConnector
 * 
 * @author phileon
 */
class DBConnector extends DBAbstractConnector
{
	/**
	 * The global unique instance of DBConnector
	 * 
	 * @var DBConnector
	 */
	private static $aInstances = null;

	
	/**
	 * Constructor is disabled, use the static method DBConnector::getInstance()
	 * 
	 * @param string $sIncDir Base include path of current system. This param is used by GlobalSystemUpdate. 
	 * 
	 */
	protected function __construct()
	{
		parent::__construct();

		$this->initConnection()->openConnection();
	}
	
	/**
	 *
	 * @return Logger
	 */
	private function getLogger()
	{
		return Logger::instance(__CLASS__);
	}
	
	/**
	 * Return the global unique instance of DBConnector.
	 * 
	 * @param string $sIncDir The base include path of the system. [optional, default=INC_DIR]
	 * 
	 * @return DBConnector
	 * 
	 * @throws Exception
	 */
	public static function getInstance()
	{
		if(!isset(self::$aInstances) || !self::$aInstances instanceof DBConnector)
		{
			self::$aInstances = new DBConnector();
		}
		
		return self::$aInstances;
	}

	/**
	 * 
	 * @return DBConnector
	 */
	private function initConnection()
	{
		$this->getLogger()->debug(__FUNCTION__.' Initializing connection  '.SQL_DATA_SOURCE.' db '.SQL_DATA_BASE);
		
		$this->
			setDataBase(SQL_DATA_BASE)->
			setDataSource(SQL_DATA_SOURCE)->
			setUserName(SQL_USERNAME)->
			setPassword(SQL_PASSWORD);

		$this->getLogger()->debug(__FUNCTION__.' Connection initialized');
		
		return $this;
	}

	
}

?>