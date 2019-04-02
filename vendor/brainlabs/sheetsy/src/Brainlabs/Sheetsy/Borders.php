<?php

namespace Brainlabs\Sheetsy;

use Google_Service_Sheets_Borders;

class Borders
{
    private $top;
    private $left;
    private $bottom;
    private $right;

    public function __construct(
        Border $top,
        Border $left,
        Border $bottom,
        Border $right
    ) {
        $this->top = $top;
        $this->left = $left;
        $this->bottom = $bottom;
        $this->right = $right;
    }

    public function getTop(): Border
    {
        return $this->top;
    }

    public function getLeft(): Border
    {
        return $this->left;
    }

    public function getBottom(): Border
    {
        return $this->bottom;
    }

    public function getRight(): Border
    {
        return $this->right;
    }

    public static function wrap(
        Google_Service_Sheets_Borders $raw
    ) {
        return new Borders(
            Border::wrap($raw->getTop()),
            Border::wrap($raw->getLeft()),
            Border::wrap($raw->getBottom()),
            Border::wrap($raw->getRight())
        );
    }

    public function unwrap(): Google_Service_Sheets_Borders
    {
        $raw = new Google_Service_Sheets_Borders();
        $raw->setTop($this->top->unwrap());
        $raw->setLeft($this->left->unwrap());
        $raw->setBottom($this->bottom->unwrap());
        $raw->setRight($this->right->unwrap());
        return $raw;
    }
}
