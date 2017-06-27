<?php
require_once realpath( dirname( __FILE__ ).'/../' ).'/config/basic.inc.php';
require_once ROOT.'/scripts/assembly/StockAssembly.class.php';

$page               = isset( $_POST['page'] ) ? $_POST['page'] : 1;
$rp                 = isset( $_POST['rp'] ) ? $_POST['rp'] : 10;
$sortName           = isset( $_POST['sortname'] ) && !empty( $_POST['sortname'] ) ? $_POST['sortname'] : 'ItemID';
$sortOrder          = isset( $_POST['sortorder'] ) && !empty( $_POST['sortorder'] ) ? $_POST['sortorder'] : 'ASC';
$query              = isset( $_POST['query'] ) ? $_POST['query'] : false;
$queryType          = isset( $_POST['qtype'] ) ? $_POST['qtype'] : false;
$filter_marking1ID  = (isset( $_POST['filterMarking1D'] ) && $_POST['filterMarking1D'] != '') ? explode( ',', $_POST['filterMarking1D'] ) : null;
$filter_items       = null;
$filter_itemNumbers = null;
$filter_itemNames   = null;

if( $query && $queryType )
{
	switch( $queryType )
	{
		case 'ItemID' :
			if( preg_match( '/(?:\\d+,)*\\d+/', trim( $query ) ) )
			{
				$filter_items = explode( ',', $query );
			}
			else
			{
				$filter_items = -1;
			}
			break;
		case 'ItemNo' :
			if( preg_match( '/(?:\\d+,)*\\d+/', trim( $query ) ) )
			{
				$filter_itemNumbers = explode( ',', $query );
			}
			else
			{
				$filter_itemNumbers = -1;
			}
			break;
		case 'ItemName' :
			$filter_itemNames = explode( ',', $query );
			break;
		default :
			throw new RuntimeException( "Invalid query type: $queryType" );
	}
}

header( 'Content-type: text/xml' );
$data   = StockAssembly::getStockData( $page, $rp, $sortName, $sortOrder, $filter_items, $filter_itemNumbers, $filter_itemNames, $filter_marking1ID );
$output = "<?xml version='1.0' encoding='utf-8'?>\n<rows>\n\t<page>{$data['page']}</page>\n\t<total>{$data['total']}</total>\n";
foreach( $data['rows'] as $itemVariant )
{
	$output .= "\t<row id='{$itemVariant['rowID']}'>\n";

	$rawData       = json_encode( $itemVariant['rawData'] );
	$writeBackData = json_encode( $itemVariant['writeBackData'] );

	$output .= "		<cell><![CDATA[{$itemVariant['itemID']}]]></cell>
		<cell><![CDATA[{$itemVariant['itemNo']}]]></cell>
		<cell><![CDATA[{$itemVariant['name']}]]></cell>
		<cell><![CDATA[RawDataA-Dummy]]></cell>
		<cell><![CDATA[$rawData]]></cell>
		<cell><![CDATA[MonthlyNeed-Dummy]]></cell>
		<cell><![CDATA[{$itemVariant['dailyNeed']}]]></cell>
		<cell><![CDATA[{$itemVariant['currentStock']}]]></cell>
		<cell><![CDATA[{$itemVariant['marking1ID']}]]></cell>
		<cell><![CDATA[ReorderLevel-Dummy]]></cell>
		<cell><![CDATA[MaxStockSugestion-Dummy]]></cell>
		<cell><![CDATA[$writeBackData]]></cell>
		<cell><![CDATA[{$itemVariant['vpe']}]]></cell>
		<cell><![CDATA[{$itemVariant['lastUpdate']}]]></cell>";

	$output .= "\t</row>\n";
}
$output .= "</rows>";

echo $output;