<?php

require_once ROOT.'lib/soap/call/PlentySoapCall.abstract.php';
require_once 'Request_GetItemsBase.class.php';

class SoapCall_GetItemsBase extends PlentySoapCall
{
	private $page              = 0;
	private $pages             = -1;
	private $plentySoapRequest = null;

	private $startAtPage = 0;

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
					$this->debug( __FUNCTION__." Starting at page {$this->startAtPage}" );
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

					$this->debug( __FUNCTION__." Request Success - articles found : $articlesFound / pages : $pagesFound" );

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
		DBUtils::lastUpdateFinish( __CLASS__ );

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
	 * @param PlentySoapObject_ItemBase $item
	 */
	private function processItemsBase($item)
	{
	}

	private function storeToDB()
	{
	}
}
