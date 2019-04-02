<?php
/*
 * @author ryutaro@brainlabsdigital.com
 */

foreach (array(__DIR__."/../../../autoload.php",
  __DIR__."/../vendor/autoload.php") as $file) {
    if (is_file($file)) {
        define("CREDULOUS_COMPOSER_INSTALL", $file);
        break;
    }
}

if (!defined("CREDULOUS_COMPOSER_INSTALL")) {
    echo "install dependencies with composer install\n";
    exit(1);
}

require_once CREDULOUS_COMPOSER_INSTALL;

const USAGE = <<<ENDUSAGE
credulous [-h][--secret secret] <scopes>

ENDUSAGE;

const HELP = <<<ENDHELP
Tool for generating API credentials. Google API is supported.
If --secret is not set, it will use the environment variable SECRET.
<scopes> is a json with a list of scope URL. The list should be under 
scopes.google

ENDHELP;

$me = array_shift($argv);

$args = [
  "secret" => null,
  "scopes" => null,
  "help" => false
];

$mandatoryArgs = [
  "scopes"
];

$nextArg = null;

foreach ($argv as $arg) {
    switch ($arg) {
        case "-h":
        case "--help":
            $args["help"] = true;
            break;
        case "--secret":
            $nextArg = "secret";
            break;
        default:
            if (is_null($nextArg)) {
                $nextArg = array_shift($mandatoryArgs);
            }
            if (!is_null($nextArg)) {
                $args[$nextArg] = $arg;
            } else {
                echo "unexpected argument {$arg}\n";
                echo USAGE;
                exit(1);
            }
              $nextArg = null;
    }
}

if ($args["help"]) {
    echo USAGE;
    echo HELP;
    exit(0);
}

if (is_null($args["scopes"])) {
    echo USAGE;
    exit(1);
}

if (is_null($args["secret"])) {
    $args["secret"] = getenv("SECRET");
}

clearstatcache();

if (!is_readable($args["secret"])) {
    echo "no such secret file {$args["secret"]}\n";
    exit(1);
}

if (!is_readable($args["scopes"])) {
    echo "no such scope file {$args["scopes"]}\n";
    exit(1);
}

if (false === ($config = file_get_contents($args["scopes"]))) {
    echo "error reading {$args["scopes"]}";
    exit(1);
}

if (null === ($jsonScopes = json_decode($config, true))) {
    echo "json error in {$args["scopes"]}: " . json_last_error_msg() . "\n";
    exit(1);
}

if (!isset($jsonScopes["scopes"])) {
    echo "missing required member \"scopes\" in {$args["scopes"]}\n";
    exit(1);
}

if (!isset($jsonScopes["scopes"]["google"])) {
    echo "missing required member \"scopes\"->\"google\" in {$args["scopes"]}\n";
    exit(1);
}

$googleScopes = $jsonScopes["scopes"]["google"];

$client = new Google_Client();
$client->setAuthConfig($args["secret"]);
$client->setIncludeGrantedScopes(true);
$client->setAccessType("offline");
$client->setConfig("prompt", "consent");
foreach ($googleScopes as $scope) {
    $client->addScope($scope);
}

$url = $client->createAuthUrl();
echo "open the following in your browser:\n{$url}\n";
echo "enter verification code: ";

$code = trim(fgets(STDIN));
if (0 === strlen($code)) {
    echo "invalid code\n";
    exit(1);
}

$accessToken = $client->fetchAccessTokenWithAuthCode($code);

$contents = file_get_contents($args["secret"]);
$creds = json_decode($contents, true);
$creds = array_merge($creds, $accessToken);

$output = json_encode($creds);
if (false === $output) {
    echo "failed to prepare json: " . json_last_error_msg() . "\n";
    exit(1);
}

if (false === file_put_contents($args["secret"], $output)) {
    echo "failed to write to {$args["secret"]}\n";
    exit(1);
}

echo "saved credentials in {$args["secret"]}\n";

exit(0);
