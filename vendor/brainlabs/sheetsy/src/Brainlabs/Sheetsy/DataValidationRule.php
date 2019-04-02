<?php
/**
 * @author sam.d@brainlabsdigital.com
 * @author andrew.p@brainlabsdigital.com
 * @author kaushik@brainlabsdigital.com
 * @author andrew.t@brainlabsdigital.com
 */

namespace Brainlabs\Sheetsy;

use Google_Service_Sheets_BooleanCondition;
use Google_Service_Sheets_ConditionValue;
use Google_Service_Sheets_DataValidationRule;

class DataValidationRule
{
    /** @var Condition $condition */
    private $condition;

    /** @var string $inputMessage */
    private $inputMessage;

    /** @var bool $showCustomUi */
    private $showCustomUi;

    /** @var bool $strict */
    private $strict;

    /**
     * @param Condition $condition
     * @param string|null $inputMessage
     * @param bool $showCustomUi
     * @param bool $strict
     */
    public function __construct(
        Condition $condition,
        String $inputMessage = null,
        bool $showCustomUi = false,
        bool $strict = true
    ) {
        $this->condition = $condition;
        $this->inputMessage = $inputMessage;
        $this->showCustomUi = $showCustomUi;
        $this->strict = $strict;
    }

    public function getCondition(): Condition
    {
        return $this->condition;
    }

    public function setInputMessage($inputMessage)
    {
        $this->inputMessage = $inputMessage;
    }

    public function getInputMessage()
    {
        return $this->inputMessage;
    }

    public function setShowCustomUi(bool $showCustomUi)
    {
        $this->showCustomUi = $showCustomUi;
    }

    public function getShowCustomUi(): bool
    {
        return $this->showCustomUi;
    }

    public function setStrict(bool $strict)
    {
        $this->strict = $strict;
    }

    public function getStrict(): bool
    {
        return $this->strict;
    }

    /**
     * @param Google_Service_Sheets_DataValidationRule
     * @return DataValidationRule|null
     */
    public static function wrap(
        Google_Service_Sheets_DataValidationRule $googleDataValidationRule
    ) {
        $dataValidationRule = new DataValidationRule(
            Condition::wrap($googleDataValidationRule->getCondition())
        );
        $dataValidationRule->setInputMessage(
            $googleDataValidationRule->getInputMessage()
        );
        $dataValidationRule->setShowCustomUi(
            is_null($googleDataValidationRule->getShowCustomUi())
            ? false
            : $googleDataValidationRule->getShowCustomUi()
        );
        $dataValidationRule->setStrict(
            is_null($googleDataValidationRule->getStrict())
            ? true
            : $googleDataValidationRule->getStrict()
        );

        return $dataValidationRule;
    }

    /**
     * @return Google_Service_Sheets_DataValidationRule
     */
    public function unwrap(): Google_Service_Sheets_DataValidationRule
    {
        $googleDataValidationRule =
            new Google_Service_Sheets_DataValidationRule();
        $googleDataValidationRule->setCondition($this->getCondition()->unwrap());
        $googleDataValidationRule->setInputMessage($this->getInputMessage());
        $googleDataValidationRule->setShowCustomUi($this->getShowCustomUi());
        $googleDataValidationRule->setStrict($this->getStrict());
        return $googleDataValidationRule;
    }
}
