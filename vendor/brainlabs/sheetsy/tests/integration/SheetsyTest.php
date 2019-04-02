<?php
/**
 * @author ryutaro@brainlabsdigital.com
 */

namespace Brainlabs\Sheetsy\Test;

use Google_Service_Sheets_UpdateValuesResponse;

use Brainlabs\Sheetsy\Chart;
use Brainlabs\Sheetsy\ChartAxis;
use Brainlabs\Sheetsy\ChartLegend;
use Brainlabs\Sheetsy\ChartType;
use Brainlabs\Sheetsy\Sheetsy;
use Brainlabs\Sheetsy\Sheet;
use Brainlabs\Sheetsy\Spreadsheet;
use Brainlabs\Sheetsy\Rect;

use Brainlabs\Sheetsy\Test\SheetsyTestCase;

/**
 * Most of this should move to SheetTest
 */
class SheetsyTest extends SheetsyTestCase
{
    const TEST_SHEET = 'test';

    const TEST_SHEET_HEIGHT = 10;

    const TEST_SHEET_WIDTH = 10;

    /** @var Sheet|null $sheet */
    private $sheet;

    /**
     * @return void
     */
    public static function setUpBeforeClass()
    {
        self::setUpTest();
    }

    /**
     * @return void
     */
    public function setUp()
    {
        $this->sheet = self::$spreadsheet->makeSheet(
            self::TEST_SHEET,
            self::TEST_SHEET_HEIGHT,
            self::TEST_SHEET_WIDTH
        );
    }

    /**
     * @return void
     */
    public function tearDown()
    {
        self::$spreadsheet->deleteSheet($this->sheet);
        $this->sheet = null;
    }

    /**
     * @return void
     */
    public function testFromArrayConstructorCreatesSheetsyWithoutErrors()
    {
        $sheetsy = Sheetsy::fromArray(
            Sheetsy::parseCredentialsFromJsonFile(self::$credsPath)
        );
        $spreadsheet = $sheetsy->getSpreadsheetById(self::$spreadsheetId);
        $this->assertTrue($spreadsheet instanceof Spreadsheet);
    }

    /**
     * Make sure the size of the sheet is as expected.
     * @return void
     */
    public function testGetDimensions()
    {
        $this->assertEquals(
            self::TEST_SHEET_HEIGHT,
            $this->sheet->getHeight()
        );
        $this->assertEquals(self::TEST_SHEET_WIDTH, $this->sheet->getWidth());
    }

    /**
     * Make sure we can retrieve sheets by name.
     * @return void
     */
    public function testGetSheetByName()
    {
        $this->assertTrue($this->sheet instanceof Sheet);
        $sheet = self::$spreadsheet->getSheetByName(self::TEST_SHEET);
        $this->assertTrue($sheet instanceof Sheet);
        $this->assertEquals(self::TEST_SHEET, $sheet->getName());
    }

    /**
     * @return void
     */
    public function testAppendDimension()
    {
        $this->sheet->appendRows(2);
        $this->sheet->appendColumns(1);
        $this->assertEquals(
            2 + self::TEST_SHEET_HEIGHT,
            $this->sheet->getHeight()
        );
        $this->assertEquals(
            1 + self::TEST_SHEET_WIDTH,
            $this->sheet->getWidth()
        );
    }

    /**
     * @return void
     */
    public function testSetValues()
    {
        $rect = new Rect(0, 0, 2, 2);
        $expectedValues = [
        [1, 'hello'],
        ['world', 2]
        ];
        $this->sheet->setValues($expectedValues, $rect);
        $values = $this->sheet->getValues($rect);

        foreach ($expectedValues as $rowNum => $row) {
            foreach ($row as $columnNum => $expectedValue) {
                $this->assertTrue(isset($values[$rowNum][$columnNum]));
                $this->assertEquals(
                    $expectedValue,
                    $values[$rowNum][$columnNum]
                );
            }
        }
    }

    /**
     * Make sure the sheet is resized when the values don't fit.
     * @return void
     */
    public function testSetValuesResizesSheet()
    {
        $values = [[0]];
        $this->sheet->setValues($values, new Rect(
            self::TEST_SHEET_HEIGHT,
            self::TEST_SHEET_WIDTH,
            1,
            1
        ));
        $this->assertEquals(
            self::TEST_SHEET_HEIGHT + 1,
            $this->sheet->getHeight()
        );
        $this->assertEquals(
            self::TEST_SHEET_WIDTH + 1,
            $this->sheet->getWidth()
        );
        self::$spreadsheet->pull();
        $this->assertEquals(
            self::TEST_SHEET_HEIGHT + 1,
            $this->sheet->getHeight()
        );
        $this->assertEquals(
            self::TEST_SHEET_WIDTH + 1,
            $this->sheet->getWidth()
        );
    }

    /**
     * Google doesn't like being passed nulls when calling update values.
     * @return void
     */
    public function testSetValuesOnNull()
    {
        $rect = new Rect(0, 0, 1, 1);
        $values = [[null]];
        $response = $this->sheet->setValues($values, $rect);
        $this->assertTrue(
            $response instanceof Google_Service_Sheets_UpdateValuesResponse
        );
        // Make sure we don't change input
        $this->assertTrue(is_null($values[0][0]));
    }

    /**
     * @return void
     */
    public function testClearSheet()
    {
        $this->sheet->setValues([[1]], new Rect(8, 8, 1, 1));
        $this->sheet->clearSheet();
        $this->assertEquals("", $this->sheet->getValues(new Rect(
            8,
            8,
            1,
            1
        ))[0][0]);
    }

    /**
     * Add a chart
     * @return void
     */
    public function testAddChart()
    {
        $id = $this->sheet->getSheetId();
        $builder = $this->sheet->getChartBuilder();
        $builder->setTitle('my test chart')
        ->setPosition(5, 5, new Rect(0, 3, 640, 480))
        ->setChartType(ChartType::LINE)
        ->setLegendPosition(ChartLegend::TOP)
        ->setDomain($id, new Rect(0, 2, 6, 1))
        ->addSeries($id, new Rect(0, 0, 6, 1), ChartAxis::LEFT)
        ->addAxis('my test axis', ChartAxis::BOTTOM);
        $chart = $this->sheet->addChart($builder->build());
        $this->assertTrue($chart instanceof Chart);
        $this->assertEquals($id, $chart->getSheetId());
    }

    /**
     * Invalid requests can cause 500 which get treated as intermittent.
     * @return void
     */
    public function testAddChartBeyondSheetDimensions()
    {
        $id = $this->sheet->getSheetId();
        $builder = $this->sheet->getChartBuilder();
        $builder->setTitle('test chart')
        ->setPosition(
            self::TEST_SHEET_HEIGHT,
            self::TEST_SHEET_WIDTH,
            new Rect(1, 1, 100, 100)
        )
        ->setChartType(ChartType::LINE)
        ->setDomain($id, new Rect(0, 0, 1, 1))
        ->addSeries($id, new Rect(1, 1, 1, 1), ChartAxis::LEFT);
        $chart = $this->sheet->addChart($builder->build());
        $this->assertEquals(
            self::TEST_SHEET_HEIGHT + 1,
            $this->sheet->getHeight()
        );
        $this->assertEquals(
            self::TEST_SHEET_WIDTH + 1,
            $this->sheet->getWidth()
        );
    }

    /**
     * Make sure we can show and hide sheets.
     * @return void
     */
    public function testShowAndHideSheet()
    {
        $this->sheet->hideSheet();
        $this->assertEquals(true, $this->sheet->isHidden());
        self::$spreadsheet->pull();
        $this->assertEquals(true, $this->sheet->isHidden());

        $this->sheet->showSheet();
        $this->assertEquals(false, $this->sheet->isHidden());
        self::$spreadsheet->pull();
        $this->assertEquals(false, $this->sheet->isHidden());
    }

    /**
     * Ensure creating an instance of a spreadsheet
     * @return void
     */
    public function testCreateSpreadsheet()
    {
        $sheetsy = Sheetsy::fromArray(
            Sheetsy::parseCredentialsFromJsonFile(self::$credsPath)
        );
        $spreadsheet = $sheetsy->createSpreadsheet();
        $this->assertTrue($spreadsheet instanceof Spreadsheet);
    }

    /**
     * @return void
     * @dataProvider spreadsheetUrls
     */
    public function testGetSpreadsheetByUrl($url)
    {
        $spreadsheetId = self::$spreadsheetId;
        $url = str_replace("SPREADSHEET_ID", $spreadsheetId, $url);
        $this->assertEquals(
            self::$sheetsy->getSpreadsheetById($spreadsheetId),
            self::$sheetsy->getSpreadsheetByUrl($url)
        );
    }

    /**
     * @return array
     */
    public function spreadsheetUrls()
    {
        return [
            ["https://docs.google.com/spreadsheets/d/SPREADSHEET_ID/edit#123"],
            ["https://docs.google.com/spreadsheets/d/SPREADSHEET_ID"],
            ["docs.google.com/spreadsheets/d/SPREADSHEET_ID"],
            ["https://docs.google.com/a/brainlabsdigital.com/spreadsheets/d/SPREADSHEET_ID/edit"]
        ];
    }
}
