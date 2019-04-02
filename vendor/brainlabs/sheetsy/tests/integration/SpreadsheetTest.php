<?php

namespace Brainlabs\Sheetsy\Test;

use Brainlabs\Sheetsy\Rect;
use Brainlabs\Sheetsy\Sheetsy;
use Brainlabs\Sheetsy\Spreadsheet;
use Brainlabs\Sheetsy\Sheet;

use Brainlabs\Sheetsy\Test\SheetsyTestCase;
use Exception;

class SpreadsheetTest extends SheetsyTestCase
{
    const ANOTHER_SPREADSHEET_ID_ENV = 'SHEETSY_ANOTHER_SPREADSHEET_ID';

    /** @var Spreadsheet $anotherSpreadsheet */
    private static $anotherSpreadsheet;

    /** @var Sheet[] $testSheets */
    private $testSheets;

    /** @var Sheet[] $testSheetsForAnotherSpreadsheet */
    private $testSheetsForAnotherSpreadsheet;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::setUpTest();

        $anotherSpreadsheetId = getenv(self::ANOTHER_SPREADSHEET_ID_ENV);
        if (!$anotherSpreadsheetId) {
            throw new Exception(
                "Please setup environment variable: "
                    . self::ANOTHER_SPREADSHEET_ID_ENV
            );
        }

        self::$anotherSpreadsheet = self::$sheetsy
            ->getSpreadsheetById($anotherSpreadsheetId);
    }

    /**
     * @return void
     */
    public function setUp()
    {
        $this->testSheets = [];
        $this->testSheetsForAnotherSpreadsheet = [];
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        foreach ($this->testSheets as $testSheet) {
            self::$spreadsheet->deleteSheet($testSheet);
        }

        foreach ($this->testSheetsForAnotherSpreadsheet as $testSheet) {
            self::$anotherSpreadsheet->deleteSheet($testSheet);
        }
    }

    /**
     * @return void
     */
    public function testGetSheets()
    {
        $sheets = self::$spreadsheet->getSheets();
        $this->assertTrue(is_array($sheets));
        $this->assertTrue(1 <= count($sheets));
        $sheet = array_pop($sheets);
        $this->assertTrue($sheet instanceof Sheet);
    }

    /**
     * @return void
     */
    public function testDuplicateSheet()
    {
        $sheetToCopy = self::$spreadsheet->makeSheet('sheetToCopy');
        $this->testSheets[] = $sheetToCopy;
        $expectedValues = [[uniqid()]];
        $range = new Rect(0, 0, 1, 1);
        $sheetToCopy->setValues($expectedValues, $range);
        $copiedSheet = self::$spreadsheet->duplicateSheet($sheetToCopy);
        $this->testSheets[] = $copiedSheet;
        $actualValues = $copiedSheet->getValues($range);
        $this->assertEquals($expectedValues, $actualValues);
    }

    public function testDuplicateSheetToAnotherSpreadsheet()
    {
        $sheetToCopy = self::$spreadsheet->makeSheet('sheetToCopy');
        $this->testSheets[] = $sheetToCopy;

        $expectedValues = [[uniqid()]];
        $range = new Rect(0, 0, 1, 1);
        $sheetToCopy->setValues($expectedValues, $range);

        $copiedSheet = self::$spreadsheet->duplicateSheet(
            $sheetToCopy,
            self::$anotherSpreadsheet
        );
        $this->testSheetsForAnotherSpreadsheet[] = $copiedSheet;

        $actualValues = $copiedSheet->getValues($range);

        $this->assertEquals($expectedValues, $actualValues);
    }

    public function testSheetIsCaseInsenstive()
    {
        $sheet = self::$spreadsheet->makeSheet('caseInsensitive');
        $this->testSheets[] = $sheet;
        $result = self::$spreadsheet->getSheetByName('caseinsensitive');
        $this->assertInstanceOf(Sheet::class, $result);
    }
}
