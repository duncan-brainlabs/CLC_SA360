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

class Condition
{
    private $conditionType;
    private $values;

    public function __construct(String $conditionType, array $values)
    {
        $this->conditionType = $conditionType;
        $this->values = $values;
    }

    public function unwrap(): Google_Service_Sheets_BooleanCondition
    {
        $conditionValues = array_map(function ($value) {
            $conditionValue = new Google_Service_Sheets_ConditionValue;
            $conditionValue->setUserEnteredValue((string) $value);
            return $conditionValue;
        }, $this->values);

        $condition = new Google_Service_Sheets_BooleanCondition;
        $condition->setType($this->conditionType);
        $condition->setValues($conditionValues);

        return $condition;
    }

    public static function wrap(Google_Service_Sheets_BooleanCondition $condition): Condition
    {
        $valueObjects = $condition->getValues();
        $values = array_map(function ($valueObject) {
            return $valueObject->getUserEnteredValue();
        }, $valueObjects);

        $wrapperCondition = new Condition(
            $condition->getType(),
            $values
        );
        return $wrapperCondition;
    }
}
