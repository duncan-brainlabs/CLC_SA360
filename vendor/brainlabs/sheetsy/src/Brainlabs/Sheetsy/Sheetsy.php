<?php
/**
 * @author ryutaro@brainlabsdigital.com
 */
namespace Brainlabs\Sheetsy;

use Google_Client;
use Google_Service_Exception;
use Google_Service_Sheets;
use Google_Service_Sheets_BatchUpdateSpreadsheetResponse;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_CopySheetToAnotherSpreadsheetRequest;
use Google_Service_Sheets_Spreadsheet;
use Google_Service_Sheets_ValueRange;
use Google_Service_Sheets_SheetProperties;

use Exception;

/**
 * This tries to be analogous to SpreadsheetApp, from Google scripts.
 */
class Sheetsy
{
    const CODE_RATE_LIMIT_EXCEEDED = 429;

    /** @var Array $credentials Associative array containing credentials json */
    private $credentials;

    /** @var Google_Client|null $client */
    private $client;

    /** @var Google_Service_Sheets|null $service */
    private $service;

    /**
     * @param string $creds
     * @return void
     */
    public function __construct($creds = null)
    {
        $this->credentials = $creds ? $this::parseCredentialsFromJsonFile($creds) : [];
        $this->client = null;
        $this->service = null;
    }

    /**
     * @param Array $credentials
     * @return Sheetsy
     */
    public static function fromArray($credentials)
    {
        $result = new Sheetsy;
        $result->credentials = $credentials;
        return $result;
    }

    /**
     * Gets JSON contents from file as associative array
     * Pass in the file path
     * @param string $jsonFilePath
     * @return Array
     */
    public static function parseCredentialsFromJsonFile($jsonFilePath)
    {
        if (!is_readable($jsonFilePath)) {
            throw new Exception('no such file ' . $jsonFilePath);
        }
        $contents = json_decode(file_get_contents($jsonFilePath), true);
        if ($contents === null) {
            throw new Exception(
                "Failed to open JSON ({$jsonFilePath}): " . json_last_error_msg()
            );
        }
        return Sheetsy::denormalize($contents, "installed");
    }

    /**
     * @param string $id
     * @return Spreadsheet
     */
    public function getSpreadsheetById($id)
    {
        return Spreadsheet::fromId($this, $id);
    }

    /**
     * Extract the ID from the spreadsheet URL using method from
     * https://developers.google.com/sheets/api/guides/concepts#spreadsheet_id
     * Does not work with links of form https://drive.google.com/open?id=SPREADSHEET_ID
     * @param string $url
     * @return Spreadsheet
     */
    public function getSpreadsheetByUrl($url)
    {
        $id = $this->parseIdFromUrl($url);
        if (!is_string($id)) {
            throw new Exception('url should be of the form ' .
                'https://docs.google.com/spreadsheets/d/some-id/edit#gid=some-id');
        }
        return $this->getSpreadsheetById($id);
    }

    /**
     * @param string $url
     * @return string|false
     */
    private function parseIdFromUrl($url)
    {
        $regex = "/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/";
        $matches = [];

        $result = preg_match($regex, $url, $matches);

        if (!$result) {
            return false;
        }

        return $matches[1];
    }

    /**
     * Wrapper around the spreadsheets.get call. Users should not call this
     * directly.
     * @param string $spreadsheetId
     * @param bool[] $opts
     * @return Google_Service_Sheets_Spreadsheet
     */
    public function callGetSpreadsheet($spreadsheetId, array $opts)
    {
        return $this->callSheetsAPI(function ($service) use (
            $spreadsheetId,
            $opts
) {
            return $service->spreadsheets->get($spreadsheetId, $opts);
        });
    }

    /**
     * Wrapper around the spreadsheets.append call. Users should not call this
     * directly.
     * @param string $spreadsheetId
     * @param string $range
     * @param Google_Service_Sheets_ValueRange $body
     * @param array $params
     * @return Google_Service_Sheets_Spreadsheet
     */
    public function callAppendSpreadsheetValues(
        $spreadsheetId,
        $range,
        $body,
        $params
    ) {
        return $this->callSheetsAPI(function ($service) use (
            $spreadsheetId,
            $range,
            $body,
            $params
) {
            return $service->spreadsheets_values->append(
                $spreadsheetId,
                $range,
                $body,
                $params
            );
        });
    }

    /**
     * Wrapper around the spreadsheet_values.get call. Users should not call
     * this directly.
     * @param string $spreadsheetId
     * @param string $range
     * @return Google_Service_Sheets_ValueRange
     */
    public function callGetSpreadsheetValues($spreadsheetId, $range)
    {
        return $this->callSheetsAPI(function ($service) use (
            $spreadsheetId,
            $range
) {
            return $service->spreadsheets_values->get($spreadsheetId, $range);
        });
    }

    /**
     * Wrapper around the spreadsheet_values.update call. Users should not call
     * this directly.
     * @param string $spreadsheetId
     * @param string $range
     * @param Google_Service_Sheets_ValueRange $valueRange
     * @param string[] $opts
     * @return Google_Service_Sheets_ValueRange
     */
    public function callUpdateSpreadsheetValues(
        $spreadsheetId,
        $range,
        $valueRange,
        $opts
    ) {
        return $this->callSheetsAPI(function ($service) use (
            $spreadsheetId,
            $range,
            $valueRange,
            $opts
) {
            return $service->spreadsheets_values->update(
                $spreadsheetId,
                $range,
                $valueRange,
                $opts
            );
        });
    }

    /**
     * Wrapper around the spreadsheet.batchUpdate call. Users should not call
     * this directly.
     * @param string $spreadsheetId
     * @param Google_Service_Sheets_BatchUpdateSpreadsheetRequest $batchRequest
     * @return Google_Service_Sheets_BatchUpdateSpreadsheetResponse
     */
    public function callBatchUpdate($spreadsheetId, $batchRequest)
    {
        return $this->callSheetsAPI(function ($service) use (
            $spreadsheetId,
            $batchRequest
) {
            return $service->spreadsheets->batchUpdate(
                $spreadsheetId,
                $batchRequest
            );
        });
    }

    /**
     * @param string $spreadsheetId
     * @param string $sheetId
     * @param Google_Service_Sheets_CopySheetToAnotherSpreadsheetRequest $request
     * @return Google_Service_Sheets_SheetProperties
     */
    public function callCopyToSpreadsheet($spreadsheetId, $sheetId, $request)
    {
        return $this->callSheetsAPI(function ($service) use (
            $spreadsheetId,
            $sheetId,
            $request
) {
            return $service->spreadsheets_sheets->copyTo(
                $spreadsheetId,
                $sheetId,
                $request
            );
        });
    }

    /**
     * Try an API call with exponential backoff.
     * @param callable $payload
     * @return mixed
     * @throws Exception
     */
    private function callSheetsAPI($payload)
    {
        return self::tryWithExponentialBackoff(function () use ($payload) {
            try {
                $service = $this->getService();
                return $payload($service);
            } catch (Google_Service_Exception $e) {
                if (Sheetsy::CODE_RATE_LIMIT_EXCEEDED === $e->getCode()) {
                    throw new TransientException($e->getMessage());
                }
                if (500 <= $e->getCode()) {
                    throw new TransientException($e->getMessage());
                }
                throw $e;
            }
        });
    }

    /**
     * Create a client if it doesn't exist already. Refresh the access token if
     * needed.
     * @return Google_Client
     * @throws Exception
     */
    private function getClient()
    {
        if (!($this->client instanceof Google_Client)) {
            $this->client = new \Google_Client($this->credentials);
            $this->client->setAccessToken($this->credentials);
        }
        if ($this->client->isAccessTokenExpired()) {
            // Hack to prevent the old access token from being cached
            $this->client->getCache()->clear();

            // @cleanup exponential backoff
            $refreshToken = $this->credentials['refresh_token'];
            if (!(is_string($refreshToken))) {
                throw new Exception('no refresh token found');
            }
            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
        }
        return $this->client;
    }

    /**
     * Create a service if it doesn't exist already and return it.
     * This is made public for convenience of developers but should not be used
     * by users of this libarry.
     * @return Google_Service_Sheets
     * @throws Exception
     */
    public function getService()
    {
        try {
            $client = $this->getClient();
        } catch (Exception $e) {
            throw $e;
        }
        $this->service = new Google_Service_Sheets($client);
        return $this->service;
    }

    /**
     * Moves JSON nested keys under a field to the top level
     * @param Array $json
     * @param string $field
     */
    private static function denormalize($json, $field)
    {
        if (array_key_exists($field, $json)) {
            foreach ($json[$field] as $key => $value) {
                $json[$key] = $value;
            }
        }
        return $json;
    }

    /**
     * @param callable $payload
     * @param int $maxTries
     * @return mixed|null
     * @throws Exception
     */
    private static function tryWithExponentialBackoff($payload, $maxTries = 10)
    {
        $result = null;
        for ($tries = 0; $tries < $maxTries; $tries++) {
            $success = false;
            try {
                $result = $payload();
                $success = true;
            } catch (TransientException $e) {
                $wasLastAttempt = ($tries === ($maxTries - 1));
                if ($wasLastAttempt) {
                    throw $e;
                }
                $minimumSleep = max(1, (int) pow(2, $tries - 1));
                $maxSleep = (int) pow(2, $tries);
                $sleepTime = rand($minimumSleep, $maxSleep);
                sleep($sleepTime);
            }
            if ($success) {
                break;
            }
        }
        return $result;
    }


    /**
     * Creates a new spreadsheet
     * @return Sheetsy\Spreadsheet
     */
    public function createSpreadsheet()
    {
        $service = $this->getService();
        $requestBody = new Google_Service_Sheets_Spreadsheet();
        $response = $service->spreadsheets->create($requestBody);
        $id = $response->getSpreadsheetId();
        return $this->getSpreadsheetById($id);
    }
}
