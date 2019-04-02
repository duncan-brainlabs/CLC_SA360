<?php

namespace Brainlabs\Sheetsy;

use PHPUnit_Framework_TestCase;

use Brainlabs\Sheetsy\Rect;

class RectTest extends PHPUnit_Framework_TestCase
{

    /**
     * @return void
     */
    public function testToAlphabet()
    {
        $testCases = [
            ['A', 0],
            ['Z', 25],
            ['AA', 26],
            ['AB', 27],
            ['AZ', 51],
            ['BA', 52],
            ['ZA', 25 + 26 ** 2 - 25],
            ['ZZ', 25 + 26 ** 2],
            ['AAA', 25 + 26 ** 2 + 1],
            ['BBB', 25 + 26 ** 2 + 26 ** 2 + 26 + 2],
            ['ZZZ', 25 + 26 ** 2 + 26 ** 3],
            ['AAAA', 25 + 26 ** 2 + 26 ** 3 + 1],
            ['BBBB', 25 + 26 ** 2 + 26 ** 3 + 26 ** 3 + 26 ** 2 + 26 + 2],
            ['ZZZZ', 25 + 26 ** 2 + 26 ** 3 + 26 ** 4],
            ['AAAAA', 25 + 26 ** 2 + 26 ** 3 + 26 ** 4 + 1]
        ];

        foreach ($testCases as $testCase) {
            $this->assertEquals($testCase[0], Rect::toAlphabet($testCase[1]));
        }
    }

    /**
     * @return void
     */
    public function testToA1Range()
    {
        $testCases = [
            ['A1:A1', new Rect(0, 0, 1, 1)],
            ['A1:ZZ1', new Rect(0, 0, 1, 26 * 26 + 26)],
            ['A1:ZZZ1', new Rect(0, 0, 1, 26 * 26 * 26 + 26 * 26 + 26)]
        ];

        foreach ($testCases as $testCase) {
            $this->assertEquals($testCase[0], $testCase[1]->toA1());
        }
    }
}
