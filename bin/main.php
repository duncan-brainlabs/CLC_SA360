<?php

// require_once __DIR__ . '/vendor/autoload.php';

use Brainlabs\SA360ConversionLagCalculator\Configuration;
use Brainlabs\Sheetsy\Sheetsy;
use Brainlabs\Sheetsy\Rect;
use Brainlabs\SA360ConversionLagCalculator\ReportRequester;
use Brainlabs\SA360ConversionLagCalculator\OutputSheet;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

class SA360ConversionLagCalculator
{
    const MAINTAINER = [
        'duncan@brainlabsdigital.com'
    ];

    private

    public function __construct(
// Do this?
}

$startT = new DateTime();
echo $t->format('Y-m-d H:i:s') . PHP_EOL;

$config = new Configuration(__DIR__ . '/config/config.json');
$credentials = __DIR__ . '/config/client_secret.json';

$client = new Google_Client();
if(!is_readable($credentials)){
  throw new Exception('No such file: ' . $credentials);
}
$client->setAuthConfig($credentials);
$token = file_get_contents($credentials);
$client->setAccessToken($token);
if($client->isAccessTokenExpired()){
  $refreshToken = $client->getRefreshToken();
  if (!(is_string($refreshToken))) {
        throw new Exception("No refresh token found.");
    }
    $client->refreshToken($refreshToken);
}

$requester = new ReportRequester();
$requester->get()
$outputSheet = new OutputSheet();
$outputSheet->ensureOutputSheet();

}

$endT = new DateTime();
echo "Duration: " . $startT->diff($endT)->format('%H:%i:%s');
?>
