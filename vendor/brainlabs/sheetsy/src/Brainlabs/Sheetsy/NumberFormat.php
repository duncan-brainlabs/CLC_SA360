<?php
/**
 * @author ryutaro@brainlabsdigital.com
 */

namespace Brainlabs\Sheetsy;

class NumberFormat
{
    /** @var string $type */
    private $type;

    /** @var string $pattern */
    private $pattern;

    /**
     * @param string $type
     * @param string $pattern
     * @return void
     */
    public function __construct(
        $type,
        $pattern
    ) {
        $this->type = $type;
        $this->pattern = $pattern;
    }

    /**
     * @param mixed[] $source
     * @return NumberFormat
     */
    public static function fromArray($source)
    {
        if (!array_key_exists('type', $source)) {
            throw new \Exception('type missing from specification');
        }
        if (!array_key_exists('pattern', $source)) {
            throw new \Exception('pattern missing from specification');
        }
        return new NumberFormat(
            $source['type'],
            $source['pattern']
        );
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }
}
