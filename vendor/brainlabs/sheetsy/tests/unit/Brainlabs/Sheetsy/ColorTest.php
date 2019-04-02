<?php

namespace Brainlabs\Sheetsy;

use PHPUnit_Framework_TestCase;
use InvalidArgumentException;

use Brainlabs\Sheetsy\Color;

class ColorTest extends PHPUnit_Framework_TestCase
{

    /**
     * @return void
     */
    public function testColorCreationException()
    {
        $this->expectException(InvalidArgumentException::class);
        $color = new Color(0, 0, 2, 0);
    }
}
