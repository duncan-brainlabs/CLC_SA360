<?php

namespace Brainlabs\Sheetsy;

use Google_Service_Sheets_Border;

class Border
{
    private $style;
    private $color;

    public function __construct(
        string $style,
        Color $color = null
    ) {
        $this->style = $style;
        $this->color = $color;
    }

    public function getStyle(): string
    {
        return $this->style;
    }

    public function getColor(): Color
    {
        return $this->color;
    }

    public static function wrap(
        Google_Service_Sheets_Border $raw = null
    ) {
        if (!$raw) {
            return new Border(
                Style::NONE
            );
        }

        return new Border(
            $raw->getStyle(),
            Color::wrap(
                $raw->getColor()
            )
        );
    }

    public function unwrap(): Google_Service_Sheets_Border
    {
        $raw = new Google_Service_Sheets_Border();
        $raw->setStyle($this->style);
        $raw->setColor($this->color->unwrap());
        return $raw;
    }
}
