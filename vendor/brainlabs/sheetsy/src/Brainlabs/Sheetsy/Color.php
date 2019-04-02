<?php
/**
 * @author david.o@brainlabsdigital.com
 */

namespace Brainlabs\Sheetsy;

use Google_Service_Sheets_Color;
use \InvalidArgumentException;

class Color
{
    private $alpha;
    private $blue;
    private $green;
    private $red;

    /**
     * @param float $red
     * @param float $green
     * @param float $blue
     * @param float $alpha
     */
    public function __construct(
        $red,
        $green,
        $blue,
        $alpha
    ) {
        if ($red) {
            if ($red < 0 || $red > 1) {
                throw new InvalidArgumentException('Red in interval [0,1] only');
            }
            $this->red = $red;
        } else {
            $this->red = 0;
        }
        if ($blue) {
            if ($blue < 0 || $blue > 1) {
                throw new InvalidArgumentException('Blue in interval [0,1] only');
            }
            $this->blue = $blue;
        } else {
            $this->blue = 0;
        }
        if ($green) {
            if ($green < 0 || $green > 1) {
                throw new InvalidArgumentException('Green in interval [0,1] only');
            }
            $this->green = $green;
        } else {
            $this->green = 0;
        }
        if ($alpha) {
            if ($alpha < 0 || $alpha > 1) {
                throw new InvalidArgumentException('Alpha in interval [0,1] only');
            }
            $this->alpha = $alpha;
        } else {
            $this->alpha = 0;
        }
    }

    /**
     * @param float $alpha
     */
    public function setAlpha($alpha)
    {
        if ($alpha < 0 || $alpha > 1) {
            throw new InvalidArgumentException('Alpha in interval [0,1] only');
        }
        $this->alpha = $alpha;
    }
    public function getAlpha()
    {
        return $this->alpha;
    }

    /**
     * @param float $blue
     */
    public function setBlue($blue)
    {
        if ($blue < 0 || $blue > 1) {
            throw new InvalidArgumentException('Blue in interval [0,1] only');
        }
        $this->blue = $blue;
    }
    public function getBlue()
    {
        return $this->blue;
    }

    /**
     * @param float $green
     */
    public function setGreen($green)
    {
        if ($green < 0 || $green > 1) {
            throw new InvalidArgumentException('Green in interval [0,1] only');
        }
        $this->green = $green;
    }
    public function getGreen()
    {
        return $this->green;
    }

    /**
     * @param float $red
     */
    public function setRed($red)
    {
        if ($red < 0 || $red > 1) {
            throw new InvalidArgumentException('Red in interval [0,1] only');
        }
        $this->red = $red;
    }
    public function getRed()
    {
        return $this->red;
    }

    public static function wrap(
        Google_Service_Sheets_Color $raw
    ) {
        return new Color(
            $raw->getRed(),
            $raw->getGreen(),
            $raw->getBlue(),
            $raw->getAlpha()
        );
    }

    /**
     * @return Google_Service_Sheets_Color
     */
    public function unwrap()
    {
        $googleColor = new Google_Service_Sheets_Color();
        $googleColor->setRed($this->getRed());
        $googleColor->setGreen($this->getGreen());
        $googleColor->setBlue($this->getBlue());
        $googleColor->setAlpha($this->getAlpha());
        return $googleColor;
    }
}
