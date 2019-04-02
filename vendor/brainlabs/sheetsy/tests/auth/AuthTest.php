<?php
/**
 * @author andrew.p@brainlabsdigital.com
 */

namespace Brainlabs\Sheetsy\Test;

use Brainlabs\Sheetsy\Rect;
use Brainlabs\Sheetsy\Test\SheetsyTestCase;

class AuthTest extends SheetsyTestCase
{
    const TEST_SHEET = 'test';
    const TEST_SHEET_HEIGHT = 10;
    const TEST_SHEET_WIDTH = 10;

    private $sheet;

    public static function setUpBeforeClass()
    {
        self::setUpTest();
    }

    public function setUp()
    {
        $this->sheet = self::$spreadsheet->makeSheet(
            self::TEST_SHEET,
            self::TEST_SHEET_HEIGHT,
            self::TEST_SHEET_WIDTH
        );
    }

    public function tearDown()
    {
        self::$spreadsheet->deleteSheet($this->sheet);
        $this->sheet = null;
    }

    /**
     * This checks we can refresh the access token more than once
     * @see https://github.com/brainlabs-digital/sheetsy/pull/88
     */
    public function testAuthenticateAfterOneHour()
    {
        $values = [['something']];
        $rect = new Rect(0, 0, 1, 1);

        // This was the only way I could reproduce the authentication error
        // Sleeping doesn't seem to produce an error
        $start = time();
        while (time() - $start < 65 * 60) {
            $this->sheet->setValues(
                $values,
                $rect
            );
        }

        $this->assertEquals(
            $values,
            $this->sheet->getValues($rect)
        );
    }
}
