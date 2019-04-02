<?php

namespace Brainlabs\Sheetsy\Test;

use Brainlabs\Sheetsy\Sheetsy;
use Brainlabs\Sheetsy\Spreadsheet;

use PHPUnit\Framework\TestCase;
use Exception;

class SheetsyTestCase extends TestCase
{
    // Absolute path to the credentials we'll run the tests with.
    const CREDS_ENV = 'SHEETSY_CREDS';

    // Id of the spreadsheet we'll run the tests on.
    const SPREADSHEET_ID_ENV = 'SHEETSY_SPREADSHEET_ID';

    /** @var string $spreadsheetId */
    protected static $spreadsheetId;

    /** @var Sheetsy $sheetsy */
    protected static $sheetsy;

    /** @var Spreadsheet $spreadsheet */
    protected static $spreadsheet;

    /** @var string $credsPath */
    protected static $credsPath;


    protected static function setUpTest()
    {
        self::$credsPath = getenv(self::CREDS_ENV);
        if (!self::$credsPath) {
            throw new Exception(
                "Please setup environment variable: " . self::CREDS_ENV
            );
        }
        $spreadsheetId = getenv(self::SPREADSHEET_ID_ENV);
        if (!$spreadsheetId) {
            throw new Exception(
                "Please set up environment variable: " . self::SPREADSHEET_ID_ENV
            );
        }

        $sheetsy = new Sheetsy(self::$credsPath);
        self::$sheetsy = $sheetsy;

        self::$spreadsheetId = $spreadsheetId;
        self::$spreadsheet = $sheetsy->getSpreadsheetById($spreadsheetId);
    }
}
