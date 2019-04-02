<?php

namespace Brainlabs\Sheetsy\Test;

use Brainlabs\Sheetsy\Border;
use Brainlabs\Sheetsy\Borders;
use Brainlabs\Sheetsy\Chart;
use Brainlabs\Sheetsy\ChartAxis;
use Brainlabs\Sheetsy\ChartType;
use Brainlabs\Sheetsy\Color;
use Brainlabs\Sheetsy\Condition;
use Brainlabs\Sheetsy\ConditionType;
use Brainlabs\Sheetsy\DataValidationRule;
use Brainlabs\Sheetsy\NumberFormat;
use Brainlabs\Sheetsy\NumberFormatType;
use Brainlabs\Sheetsy\Rect;
use Brainlabs\Sheetsy\Sheet;
use Brainlabs\Sheetsy\Sheetsy;
use Brainlabs\Sheetsy\Spreadsheet;
use Brainlabs\Sheetsy\Style;

use Brainlabs\Sheetsy\Test\SheetsyTestCase;

class SheetTest extends SheetsyTestCase
{
    const TEST_SHEET = 'test';
    const TEST_SHEET_HEIGHT = 10;
    const TEST_SHEET_WIDTH = 10;

    /**
     * @var Sheet $sheet
     */
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
    }

    /**
     * @return void
     */
    public function testSetMissingValues()
    {
        $values = [['3', 'columns', 'here'], ['and', '4', 'columns', 'here']];
        $this->sheet->setValuesFromPoint($values, 0, 0);
        $fromSheet = $this->sheet->getValues(new Rect(0, 0, 2, 4));
        $this->assertEquals($values, $fromSheet);
    }

    public function testSetValuesWithAssociativeArray()
    {
        $values = [
            "stringy" => [1, 2, 3],
            12345 => [1, 2, 3]
        ];

        $rect = new Rect(0, 0, 2, 3);

        $this->sheet->setValues($values, $rect);
        $this->assertEquals(
            array_values($values),
            $this->sheet->getValues($rect)
        );
    }

    /**
     * @return void
     */
    public function testSetName()
    {
        $expectedName = 'my name';
        $this->sheet->setName($expectedName);
        $this->assertEquals($expectedName, $this->sheet->getName());
        self::$spreadsheet->pull();
        $sheet = self::$spreadsheet->getSheetById($this->sheet->getSheetId());
        $this->assertEquals($expectedName, $sheet->getName());
    }


    public function testAppendValues()
    {
        $values = [['1','4','3','Something']];
        $rect = new Rect(0, 0, 1, 4);
        $this->sheet->appendValues($values, $rect);
        $fromSheet = $this->sheet->getValues($rect);
        $this->assertEquals($values, $fromSheet);
    }

    public function testLastRowEmpty()
    {
        $lastRow = $this->sheet->getLastRow();
        $this->assertEquals(-1, $lastRow);
    }

    public function testLastRowBlock()
    {
        $values = [[1,2],[4,5],[1]];
        $rect = new Rect(0, 0, 1, 1);
        $this->sheet->appendValues($values, $rect);
        $lastRow = $this->sheet->getLastRow();
        $this->assertEquals(2, $lastRow);
    }

    public function testLastRowTwoBlocks()
    {
        $values = [[1,2],[4,5]];
        $rect = new Rect(0, 0, 2, 2);
        $this->sheet->setValues($values, $rect);
        $values = [[3,6],[7,8]];
        $rect = new Rect(3, 3, 2, 2);
        $this->sheet->setValues($values, $rect);
        $lastRow = $this->sheet->getLastRow();
        $this->assertEquals(4, $lastRow);
    }

    public function testLastColumnEmpty()
    {
        $lastCol = $this->sheet->getLastColumn();
        $this->assertEquals(-1, $lastCol);
    }

    public function testLastColumnBlock()
    {
        $values = [[1,2],[4,5],[1]];
        $rect = new Rect(0, 0, 1, 1);
        $this->sheet->appendValues($values, $rect);
        $lastCol = $this->sheet->getLastColumn();
        $this->assertEquals(1, $lastCol);
    }

    public function testLastColumnTwoBlocks()
    {
        $values = [[1,2],[4,5]];
        $rect = new Rect(0, 0, 2, 2);
        $this->sheet->setValues($values, $rect);
        $values = [[3,6],[7,8]];
        $rect = new Rect(3, 3, 2, 2);
        $this->sheet->setValues($values, $rect);
        $lastCol = $this->sheet->getLastColumn();
        $this->assertEquals(4, $lastCol);
    }


    public function testDeleteRows()
    {
        $heightBefore = $this->sheet->getHeight();
        $this->sheet->deleteRows(0, 1);
        $localHeightAfter = $this->sheet->getHeight();
        $this->assertEquals($heightBefore-1, $localHeightAfter);
        self::$spreadsheet->pull();
        $sheet = self::$spreadsheet->getSheetById($this->sheet->getSheetId());
        $heightAfter = $sheet->getHeight();
        $this->assertEquals($heightBefore-1, $heightAfter);
    }

    public function testDeleteColumns()
    {
        $widthBefore = $this->sheet->getWidth();
        $this->sheet->deleteColumns(0, 1);
        $localWidthAfter = $this->sheet->getWidth();
        $this->assertEquals($widthBefore-1, $localWidthAfter);
        self::$spreadsheet->pull();
        $sheet = self::$spreadsheet->getSheetById($this->sheet->getSheetId());
        $widthAfter = $sheet->getWidth();
        $this->assertEquals($widthBefore-1, $widthAfter);
    }

    public function testInsertRows()
    {
        $heightBefore = $this->sheet->getHeight();
        $this->sheet->insertRows(0, 1);
        $localHeightAfter = $this->sheet->getHeight();
        $this->assertEquals($heightBefore+1, $localHeightAfter);
        self::$spreadsheet->pull();
        $sheet = self::$spreadsheet->getSheetById($this->sheet->getSheetId());
        $heightAfter = $sheet->getHeight();
        $this->assertEquals($heightBefore+1, $heightAfter);
    }

    public function testInsertColumns()
    {
        $widthBefore = $this->sheet->getWidth();
        $this->sheet->insertColumns(0, 1);
        $localWidthAfter = $this->sheet->getWidth();
        $this->assertEquals($widthBefore+1, $localWidthAfter);
        self::$spreadsheet->pull();
        $sheet = self::$spreadsheet->getSheetById($this->sheet->getSheetId());
        $widthAfter = $sheet->getWidth();
        $this->assertEquals($widthBefore+1, $widthAfter);
    }

    /**
     * @return void
     */
    public function testSetFormat()
    {
        $expected = [
            [
                new NumberFormat(NumberFormatType::NUMBER, '#,##0.00'),
                new NumberFormat(NumberFormatType::PERCENT, '0.00%'),
            ],
            [
                new NumberFormat(NumberFormatType::CURRENCY, '"$"#,##0.00'),
                new NumberFormat(NumberFormatType::TEXT, '')
            ]
        ];

        $rect = new Rect(0, 0, 2, 2);
        $this->sheet->setNumberFormats($expected, $rect);

        $actual = $this->sheet->getNumberFormats($rect);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return void
     */
    public function testSetDataValidation()
    {
        $condition = new Condition(ConditionType::NUMBER_GREATER, [100]);
        $expectedRule = new DataValidationRule($condition);
        $rect = new Rect(0, 0, 2, 2);

        $this->sheet->setDataValidation($expectedRule, $rect);

        $sheetRules = $this->sheet->getDataValidation($rect);
        foreach ($sheetRules as $row) {
            foreach ($row as $rule) {
                $this->assertEquals($expectedRule, $rule);
            }
        }
    }

    /**
     * @return void
     */
    public function testCropSheet()
    {
        $rect = new Rect(1, 1, 1, 2);
        $this->sheet->cropSheet($rect);
        $this->assertEquals(2, $this->sheet->getHeight());
        $this->assertEquals(3, $this->sheet->getWidth());
        self::$spreadsheet->pull();
        $sheet = self::$spreadsheet->getSheetById($this->sheet->getSheetId());
        $this->assertEquals(2, $sheet->getHeight());
        $this->assertEquals(3, $sheet->getWidth());
    }

    /**
     * @return void
     */
    public function testClearRange()
    {
        $range = new Rect(1, 1, 2, 4);
        $values = [[1, 1, 1, 1], [1, 1, 1, 1]];
        $this->sheet->setValues($values, $range);

        $rangeToBeCleared = new Rect(1, 1, 1, 2);
        $this->sheet->clearRange($rangeToBeCleared);

        $expected = [["", "", 1, 1], [1, 1, 1, 1]];

        $this->assertEquals($expected, $this->sheet->getValues($range));
    }

    /**
     * @return void
     */
    public function testGetBackgroundColor()
    {
        $white = new Color(1, 1, 1, 0);
        $colors = [[$white]];
        $rect = new Rect(0, 0, 1, 1);
        $colorsFromSheet = $this->sheet->getBackgroundColor($rect);
        $this->assertEquals($colors, $colorsFromSheet);
    }

    /**
     * @return void
     */
    public function testSetBackgroundColor()
    {
        $yellow = new Color(0, 1, 1, 0);
        $red = new Color(1, 0, 0, 0);
        $green = new Color(0, 1, 0, 0);
        $blue = new Color(0, 0, 1, 0);
        $colors = [[$yellow, $red], [$blue, $green]];
        $rect = new Rect(0, 0, 2, 2);
        $this->sheet->setBackgroundColor($colors, $rect);
        $colorsFromSheet = $this->sheet->getBackgroundColor($rect);
        $this->assertEquals($colors, $colorsFromSheet);
    }

    public function testSingleSortAscending()
    {
        $unorderedList = [['David', 1],['Andrew', 3], ['Amy', 2]];
        $orderedListByColumnOne = [['David'], ['Amy'], ['Andrew']];
        $rect = new Rect(0, 0, 3, 2);
        $this->sheet->setValues($unorderedList, $rect);
        $sortingOperartion = ['column' => 1, 'ascending' => true];
        $this->sheet->sort([$sortingOperartion], $rect);
        $rect = new Rect(0, 0, 3, 1);
        $listFromSheet = $this->sheet->getValues($rect);
        $this->assertEquals($orderedListByColumnOne, $listFromSheet);
    }

    public function testSingleSortDescending()
    {
        $unorderedList = [['David', 1],['Andrew', 3], ['Amy', 2]];
        $orderedListByColumnOne = [['Andrew'], ['Amy'], ['David']];
        $rect = new Rect(0, 0, 3, 2);
        $this->sheet->setValues($unorderedList, $rect);
        $sortingOperartion = ['column' => 1, 'ascending' => false];
        $this->sheet->sort([$sortingOperartion], $rect);
        $rect = new Rect(0, 0, 3, 1);
        $listFromSheet = $this->sheet->getValues($rect);
        $this->assertEquals($orderedListByColumnOne, $listFromSheet);
    }

    public function testDoubleSort()
    {
        $unorderedList = [['David', 4, 1],['Andrew', 2,3], ['Amy', 2,2], ['Amy J', 1,4]];
        $orderedListByColumnOneThenTwo = [['Amy J'], ['Amy'], ['Andrew'], ['David']];
        $rect = new Rect(0, 0, 4, 3);
        $this->sheet->setValues($unorderedList, $rect);
        $sortByColumnOne = ['column' => 1, 'ascending' => true];
        $sortByColumnTwo = ['column'=> 2, 'ascending'=> true];
        $this->sheet->sort([$sortByColumnOne, $sortByColumnTwo], $rect);
        $rect = new Rect(0, 0, 4, 1);
        $listFromSheet = $this->sheet->getValues($rect);
        $this->assertEquals($orderedListByColumnOneThenTwo, $listFromSheet);
    }

    /**
     * @return void
     */
    public function testGetHorizontalAlignments()
    {
        $alignments = [['LEFT']];
        $rect = new Rect(0, 0, 1, 1);
        $alignmentsFromSheet = $this->sheet->getHorizontalAlignments($rect);
        $this->assertEquals($alignments, $alignmentsFromSheet);
    }

    /**
     * @return void
     */
    public function testSetHorizontalAlignments()
    {
        $alignments = [['LEFT', 'RIGHT'], ['CENTER', 'LEFT']];
        $rect = new Rect(0, 0, 2, 2);
        $this->sheet->setHorizontalAlignments($alignments, $rect);
        $alignmentsFromSheet = $this->sheet->getHorizontalAlignments($rect);
        $this->assertEquals($alignments, $alignmentsFromSheet);
    }

    /**
     * @return void
     */
    public function testGetVerticalAlignments()
    {
        $alignments = [['BOTTOM']];
        $rect = new Rect(0, 0, 1, 1);
        $alignmentsFromSheet = $this->sheet->getVerticalAlignments($rect);
        $this->assertEquals($alignments, $alignmentsFromSheet);
    }

    /**
     * @return void
     */
    public function testSetVerticalAlignments()
    {
        $alignments = [['TOP', 'MIDDLE'], ['MIDDLE', 'BOTTOM']];
        $rect = new Rect(0, 0, 2, 2);
        $this->sheet->setVerticalAlignments($alignments, $rect);
        $alignmentsFromSheet = $this->sheet->getVerticalAlignments($rect);
        $this->assertEquals($alignments, $alignmentsFromSheet);
    }

    /**
     * @group getAllPaddedValues
     */
    public function testGetAllPaddedValuesStandard()
    {
        $rect = new Rect(0, 0, 3, 3);

        $vals = [
            ['cell 0 0', 'cell 0 1', 'cell 0 2'],
            ['cell 1 0', 'cell 1 1', 'cell 1 2'],
            ['cell 2 0', 'cell 2 1', 'cell 2 2']
        ];
        $this->sheet->setValues($vals, $rect);
        $ans = $this->sheet->getAllPaddedValues();
        $this->assertEquals($vals, $ans);
    }

    /**
     * @group getAllPaddedValues
     */
    public function testGetAllPaddedValuesWithGaps()
    {
        $rect = new Rect(0, 0, 3, 3);

        $vals = [
            ['', '', 'cell 0 2'],
            ['', 'cell 1 1', ''],
            ['cell 2 0', '', '']
        ];
        $this->sheet->setValues($vals, $rect);
        $ans = $this->sheet->getAllPaddedValues();
        $this->assertEquals($vals, $ans);
    }

    /**
     * @group getAllPaddedValues
     */
    public function testGetAllPaddedValuesOffset()
    {
        $rect = new Rect(1, 1, 2, 2);

        $vals = [
            ['cell 1 1', 'cell 1 2'],
            ['cell 2 1', 'cell 2 2']
        ];

        $expected = [
            ['', '', ''],
            ['', 'cell 1 1', 'cell 1 2'],
            ['', 'cell 2 1', 'cell 2 2']
        ];

        $this->sheet->setValues($vals, $rect);
        $ans = $this->sheet->getAllPaddedValues();
        $this->assertEquals($expected, $ans);
    }

    /**
     * @group getAllPaddedValues
     */
    public function testGetAllPaddedValuesEmpty()
    {
        $ans = $this->sheet->getAllPaddedValues();
        $this->assertEquals([[]], $ans);
    }

    public function testGetColumnWidth()
    {
        $defaultColumnWidth = 100;
        $columnWidth = $this->sheet->getColumnWidth(0);
        $this->assertEquals($defaultColumnWidth, $columnWidth);
    }

    /**
     * @return void
     */
    public function testCopyAndPaste()
    {
        $testValue = [['copy and paste']];
        $copyRect = new Rect(0, 0, 1, 1);
        $pasteRect = new Rect(8, 8, 1, 1);
        $this->sheet->setValues($testValue, $copyRect);
        $this->sheet->copyAndPaste($copyRect, $pasteRect);
        $pasteValue = $this->sheet->getValues($pasteRect);
        $this->assertEquals($pasteValue, $testValue);
    }

    public function testCanGetDefaultBorders()
    {
        $none = new Border(Style::NONE);
        $default = new Borders($none, $none, $none, $none);

        $expected = [
            [$default, $default],
            [$default, $default]
        ];

        $rect = new Rect(0, 0, 2, 2);
        $actual = $this->sheet->getBorders($rect);

        $this->assertEquals($expected, $actual);
    }

    public function testCanSetInternalBorders()
    {
        $color = new Color(0, 0, 0, 0);
        $border = new Border(Style::SOLID, $color);
        $none = new Border(Style::NONE);

        $expected = [
            [
                new Borders($none, $none, $border, $border),
                new Borders($none, $border, $border, $none)
            ],
            [
                new Borders($border, $none, $none, $border),
                new Borders($border, $border, $none, $none)
            ]
        ];

        $rect = new Rect(0, 0, 2, 2);
        $this->sheet->setBorder(
            false,
            false,
            false,
            false,
            true,
            true,
            $color,
            Style::SOLID,
            $rect
        );
        $actual = $this->sheet->getBorders($rect);

        $this->assertEquals($expected, $actual);
    }

    public function testCanSetExternalBorders()
    {
        $color = new Color(0, 0, 0, 0);
        $border = new Border(Style::SOLID, $color);
        $none = new Border(Style::NONE);

        $expected = [
            [
                new Borders($border, $border, $none, $none),
                new Borders($border, $none, $none, $border)
            ],
            [
                new Borders($none, $border, $border, $none),
                new Borders($none, $none, $border, $border)
            ]
        ];

        $rect = new Rect(0, 0, 2, 2);
        $this->sheet->setBorder(
            true,
            true,
            true,
            true,
            false,
            false,
            $color,
            Style::SOLID,
            $rect
        );
        $actual = $this->sheet->getBorders($rect);

        $this->assertEquals($expected, $actual);
    }

    public function testCanRemoveBorders()
    {
        $color = new Color(0, 0, 0, 0);
        $border = new Border(Style::SOLID, $color);
        $none = new Border(Style::NONE);

        $expected = [
            [
                new Borders($none, $border, $border, $border),
                new Borders($none, $border, $border, $border)
            ],
            [
                new Borders($border, $border, $border, $border),
                new Borders($border, $border, $border, $border)
            ]
        ];

        $rect = new Rect(0, 0, 2, 2);
        $this->sheet->setBorder(
            true,
            true,
            true,
            true,
            true,
            true,
            $color,
            Style::SOLID,
            $rect
        );
        $this->sheet->setBorder(
            false,
            null,
            null,
            null,
            null,
            null,
            null,
            null,
            $rect
        );
        $actual = $this->sheet->getBorders($rect);

        $this->assertEquals($expected, $actual);
    }

    public function testCanAddAndUpdateChart()
    {
        $title = 'a brand new chart';

        $this->sheet->setValues(
            [
                [1, 1],
                [1, 1],
                [1, 1]
            ],
            new Rect(0, 0, 3, 2)
        );

        $this->assertNull(
            $this->sheet->getChartByTitle($title)
        );

        $chart = $this->sheet->getChartBuilder()
            ->setChartType(ChartType::LINE)
            ->setTitle($title)
            ->setPosition(
                0,
                0,
                new Rect(0, 0, 100, 100)
            )
            ->setDomain(
                $this->sheet->getSheetId(),
                new Rect(0, 0, 10, 1)
            )
            ->addAxis(
                'bottom',
                ChartAxis::BOTTOM
            )
            ->addSeries(
                $this->sheet->getSheetId(),
                new Rect(0, 1, 10, 1),
                ChartAxis::LEFT
            )
            ->addAxis(
                'left',
                ChartAxis::LEFT
            )
            ->build();

        $this->sheet->addChart($chart);

        $sheet = $this->sheet->refresh();
        $newChart = $sheet->getChartByTitle($title);

        $this->assertInstanceOf(
            Chart::class,
            $newChart
        );

        $newTitle = 'something different';
        $newChart = $newChart->modify()->setTitle($newTitle)->build();

        $this->sheet->updateChart($newChart);

        $sheet = $this->sheet->refresh();
        $updatedChart = $sheet->getChartByTitle($newTitle);

        $this->assertInstanceOf(
            Chart::class,
            $updatedChart
        );
    }
}
