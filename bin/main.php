<?php

require_once __DIR__ . '/vendor/autoload.php';

use Brainlabs\SA360ConversionLagCalculator\Configuration;
use Brainlabs\Sheetsy\Sheetsy;
use Brainlabs\Sheetsy\Rect;

$startT = new DateTime();
echo $t->format('Y-m-d H:i:s') . PHP_EOL;
// Why sleep after timer is started?
sleep(10);

$credentials = __DIR__ . '/config/client_secret.json';

$sheetsy = new Sheetsy($credentials);

$config = new Configuration(__DIR__ . '/config/config.json');

$ssId = $config->getSsId();

// How to get this cell from SS?
$accounts = array(
    'example_account_name_1' => 'corresponding_acc_id_1',
    'example_account_name_2' => 'corresponding_acc_id_2'
);

// How to pull custom conversions from SS and convert to SA360 language?
$metrics = array(
    'METRIC_CONVERSIONS'
);




$endT = new DateTime();
echo "Duration: " . $startT->diff($endT)->format('%H:%i:%s');
?>
