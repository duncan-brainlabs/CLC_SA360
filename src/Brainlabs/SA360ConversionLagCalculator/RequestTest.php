<?php

require_once '../../../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Brainlabs\SA360ConversionLagCalculator\Configuration;
use Brainlabs\SA360ConversionLagCalculator\InputSheet;

$config = new Configuration('../../../config/config.json');
$inputSheet = new InputSheet($config);
$downloadDir = __DIR__ . '/reports/';
$url = "https://www.googleapis.com";
$resource = "/doubleclicksearch/v2/reports";
$guzzleClient = new Client([
    'base_uri' => $url,
    'timeout'  => 0,
]);
$body = [
'reportType' => 'account',
"reportScope" => [
    "agencyId" => $config->getAgencyId(),
    "advertiserId" => $inputSheet->getAdvertiserId()
],
"columns" => [
    ["columnName" => $inputSheet->getColumnName()]
],
'timeRange' => [
    "startDate" => $inputSheet->getStartDate(),
    "endDate" => $inputSheet->getStartDate()
],
'downloadFormat' => 'csv',
'statisticsCurrency' => 'usd',
'maxRowsPerFile' => 1000000
];
$headers =  [
    'Authorization' => 'Bearer '.$client->getAccessToken()['access_token'],
    'Content-type' => 'application/json'
];
$response = [];
try {
$response = $guzzleClient->request('POST', $resource, ['headers' => $headers, 'body' => json_encode($body)]);
} catch(Exception $e) {
var_dump($e->getResponse()->getBody()->getContents());
}
$responseString = '';
$responseBody = $response->getBody();
$responseBody->seek(0);
while(!$responseBody->eof()){
$responseString .= $responseBody->read(128);
}
$responseAsArray = json_decode($responseString,true);
$reportId = $responseAsArray['id'];
$isReportReady = $responseAsArray['isReportReady'];
$numAttempts = 0;
$maxNumAttempts = 5;
while(!$isReportReady && $numAttempts < $maxNumAttempts){
try {
    $response = $guzzleClient->request('GET', $resource.'/'.$reportId, ['headers' => $headers]);
} catch(Exception $e) {
    var_dump($e->getResponse()->getBody()->getContents());
}
$responseString = '';
$responseBody = $response->getBody();
$responseBody->seek(0);
while(!$responseBody->eof()){
    $responseString .= $responseBody->read(128);
}
$responseAsArray = json_decode($responseString,true);
$isReportReady = $responseAsArray['isReportReady'];
if(!$isReportReady){
    $numAttempts++;
    echo 'Attempt '.$numAttempts." failed. Waiting...\r\n";
    sleep(15);
}
}
$responseString = '';
$responseBody = $response->getBody();
$responseBody->seek(0);
while(!$responseBody->eof()){
$responseString .= $responseBody->read(128);
}
$responseAsArray = json_decode($responseString,true);
$numberOfFiles = count($responseAsArray['files']);
$reportFilesAsArray = [];
$allFilesString ='';
for($i=0; $i<$numberOfFiles; $i++) {
$byteCount = $responseAsArray['files'][$i]['byteCount'];
try {
    $response = $guzzleClient->request('GET', $resource.'/'.$reportId.'/files/'.$i, ['headers' => $headers]);
} catch(Exception $e) {
    $var_dump($e->getResponse()->getBody()->getContents());
}
$responseString = '';
$responseBody = $response->getBody();
$responseBody->seek(0);
$responseString .= $responseBody->read($byteCount);
$responseAsArray = [];
$responseRows = str_getcsv($responseString, "\n");
$keysRow = $responseRows[0];
$keys = str_getcsv($keysRow,',');
$rows = array_slice($responseRows,1);
foreach($rows as $row){
    $rowEntries = str_getcsv($row, ",");
    if(count($rowEntries) != count($keys)){
    throw new Exception("Number of keys provided does not match entries from report");
    }else{
    $responseAsArray[] = array_combine($keys,$rowEntries);
    }
}
$reportFilesAsArray = array_merge_recursive($reportFilesAsArray, $responseAsArray);

echo($reportFilesAsArray);
}
?>
