<?php
/**
 * @author ryutaro@brainlabsdigital.com
 */
namespace Brainlabs\Sheetsy;

use Exception;

class Rect
{
    const ALPHABET_SIZE = 26;
    const FIRST_CHARACTER = 'A';

    /** @var int $row */
    private $row;

    /** @var int $column */
    private $column;

    /** @var int $height */
    private $height;

    /** @var int $width */
    private $width;

    /**
     * @param int $row
     * @param int $column
     * @param int $height
     * @param int $width
     * @return void
     */
    public function __construct(
        $row,
        $column,
        $height,
        $width
    ) {
        $this->row = $row;
        $this->column = $column;
        $this->height = $height;
        $this->width = $width;
    }

    /**
     * @return int
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @return int
     */
    public function getColumn()
    {
        return $this->column;
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->height;
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->width;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function toA1()
    {
        if (($this->height < 1) || ($this->width < 1)) {
            throw new Exception(
                '$this->height and $this->width must be greater than zero: got
                ' .  $this->height . ', ' . $this->width
            );
        }
        $topLeft = $this->toA1Notation($this->row, $this->column);
        $bottomRight = $this->toA1Notation(
            $this->row + $this->height- 1,
            $this->column + $this->width - 1
        );
        return $topLeft . ':' . $bottomRight;
    }

    /**
     * $rowOffset and $colOffset are zero-indexed.
     * @param int $rowOffset
     * @param int $colOffset
     * @return string Return null on error.
     * @throws Exception
     */
    private static function toA1Notation($rowOffset, $colOffset)
    {
        $alpha = Rect::toAlphabet($colOffset);
        return $alpha . ($rowOffset + 1);
    }

    /**
     * Converts a number to the number system used for columns in spreadsheet
     * applications.
     *
     * A = 0, Z = 25, AA = 26, AB = 27, BA = 52, etc.
     *
     * The idea is to
     * 1. calculate the length $length of the column name we want
     * 2. subtract the number of possible column names with strictly smaller length from $number
     * 3. convert this result to base-ALPHABET_SIZE
     * Step 2 is finding the index of $number among all column names with length $length
     *
     * @param int $number
     * @return string The alpha notation for the given number.
     */
    public static function toAlphabet(int $number): string
    {
        // Number of characters in the column name
        $length = 1;

        // Number of column names with length $length or smaller
        $possibilities = self::ALPHABET_SIZE;

        // Number of column names with length strictly smaller than $length
        $previous = 0;

        while ($possibilities < $number + 1) {
            $length += 1;
            $previous = $possibilities;
            $possibilities += self::ALPHABET_SIZE ** $length;
        }

        // We want this number in base-ALPHABET_SIZE
        $target = $number - $previous;

        $letters = [];
        for ($i = 0; $i < $length; $i++) {
            $offset = $target % self::ALPHABET_SIZE;
            $target = floor($target / self::ALPHABET_SIZE);

            $ascii = ord(self::FIRST_CHARACTER) + $offset;
            $letters[] = chr($ascii);
        }

        return implode(
            "",
            array_reverse($letters)
        );
    }
}
