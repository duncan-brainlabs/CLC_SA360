<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Brainlabs\Sheetsy\Sheetsy;

$creds = __DIR__ . '/../credentials/google.json';
$spreadsheetId = '1Hq_EYu0745OGsoQ2lNw-bilEWGe1qsYSITc7AkPAPCw';
$sheetId = 0;
$title = 'my first chart';
$headerCount = 1;
$cellRow = 0;
$cellColumn = 0;
$offsetX = 0;
$offsetY = 0;
$width = 1024;
$height = 640;
$chartType = 'LINE';
$legendPosition = 'TOP_LEGEND';
$x_axis_position = 'BOTTOM_AXIS';
$x_axis_title = 'my x axis';

$sheetsy = new Sheetsy($creds);

$strategy = 'SHOW_ALL';

$x_axis = new Google_Service_Sheets_BasicChartAxis();
$x_axis->setPosition($x_axis_position);
$x_axis->setTitle($x_axis_title);
// TextFormat
// $x_axis->setFormat($format)
$axis = [
  $x_axis
]; 


$dom_range = new Google_Service_Sheets_GridRange();
$dom_range->setSheetId($sheetId);
$dom_range->setStartRowIndex(0);
$dom_range->setEndRowIndex(8);
$dom_range->setStartColumnIndex(2);
$dom_range->setEndColumnIndex(3);

$dom_sources = [
  $dom_range
];

$dom_sourceRange = new Google_Service_Sheets_ChartSourceRange();
$dom_sourceRange->setSources($dom_sources);
$dom_chartData = new Google_Service_Sheets_ChartData();
$dom_chartData->setSourceRange($dom_sourceRange);

$domain = new Google_Service_Sheets_BasicChartDomain();
$domain->setDomain($dom_chartData);
$domains = [
  $domain
];

$range1 = new Google_Service_Sheets_GridRange();
$range1->setSheetId($sheetId);
$range1->setStartRowIndex(0);
$range1->setEndRowIndex(8);
$range1->setStartColumnIndex(0);
$range1->setEndColumnIndex(1);

$range2 = new Google_Service_Sheets_GridRange();
$range2->setSheetId($sheetId);
$range2->setStartRowIndex(0);
$range2->setEndRowIndex(8);
$range2->setStartColumnIndex(1);
$range2->setEndColumnIndex(2);

$sources1 = [
  $range1
];

$sourceRange1 = new Google_Service_Sheets_ChartSourceRange();
$sourceRange1->setSources($sources1);
$chartData1 = new Google_Service_Sheets_ChartData();
$chartData1->setSourceRange($sourceRange1);

$series_1 = new Google_Service_Sheets_BasicChartSeries();
$series_1->setSeries($chartData1);
$series_1->setTargetAxis('LEFT_AXIS');
// type only valid for combo chart

$sources2 = [
  $range2
];

$sourceRange2 = new Google_Service_Sheets_ChartSourceRange();
$sourceRange2->setSources($sources2);
$chartData2 = new Google_Service_Sheets_ChartData();
$chartData2->setSourceRange($sourceRange2);

$series_2 = new Google_Service_Sheets_BasicChartSeries();
$series_2->setSeries($chartData2);
$series_2->setTargetAxis('RIGHT_AXIS');
// type only valid for combo chart

$series = [
  $series_1,
  $series_2
];

$basicChartSpec = new Google_Service_Sheets_BasicChartSpec();
$basicChartSpec->setChartType($chartType);
$basicChartSpec->setLegendPosition($legendPosition);
$basicChartSpec->setAxis($axis);
$basicChartSpec->setDomains($domains);
$basicChartSpec->setSeries($series);
$basicChartSpec->setHeaderCount($headerCount);

$spec = new Google_Service_Sheets_ChartSpec();
$spec->setTitle($title);
$spec->setHiddenDimensionStrategy($strategy);
$spec->setBasicChart($basicChartSpec);

$coordinate = new Google_Service_Sheets_GridCoordinate();
$coordinate->setSheetId($sheetId);
$coordinate->setRowIndex($cellRow);
$coordinate->setColumnIndex($cellColumn);

$overlayPosition = new Google_Service_Sheets_OverlayPosition();
$overlayPosition->setAnchorCell($coordinate);
$overlayPosition->setOffsetXPixels($offsetX);
$overlayPosition->setOffsetYPixels($offsetY);
$overlayPosition->setWidthPixels($width);
$overlayPosition->setHeightPixels($height);

$position = new Google_Service_Sheets_EmbeddedObjectPosition();
$position->setOverlayPosition($overlayPosition);

$chart = new Google_Service_Sheets_EmbeddedChart();
$chart->setPosition($position);
$chart->setSpec($spec);

//print_r($chart);

$request = new Google_Service_Sheets_AddChartRequest();
$request->setChart($chart);
$r = new Google_Service_Sheets_Request();
$r->setAddChart($request);

$batchRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
$batchRequest->setRequests([$r]);

$service = $sheetsy->getService();
$service->spreadsheets->batchUpdate($spreadsheetId, $batchRequest);
