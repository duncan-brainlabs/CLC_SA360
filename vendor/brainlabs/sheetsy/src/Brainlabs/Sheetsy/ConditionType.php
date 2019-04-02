<?php

/**
 * @author sam.d@brainlabsdigital.com
 * @author andrew.p@brainlabsdigital.com
 * @author kaushik@brainlabsdigital.com
 * @author andrew.t@brainlabsdigital.com
 */

namespace Brainlabs\Sheetsy;

abstract class ConditionType
{
    // These have to mirror the Google constants exactly, as defined in
    // Google_Service_Sheets_ConditionType.
    const NUMBER_GREATER = 'NUMBER_GREATER';
    const NUMBER_GREATER_THAN_EQ = 'NUMBER_GREATER_THAN_EQ';
    const NUMBER_LESS = 'NUMBER_LESS';
    const NUMBER_LESS_THAN_EQ = 'NUMBER_LESS_THAN_EQ';
    const NUMBER_EQ = 'NUMBER_EQ';
    const NUMBER_NOT_EQ = 'NUMBER_NOT_EQ';
    const NUMBER_BETWEEN = 'NUMBER_BETWEEN';
    const NUMBER_NOT_BETWEEN = 'NUMBER_NOT_BETWEEN';
    const TEXT_CONTAINS = 'TEXT_CONTAINS';
    const TEXT_NOT_CONTAINS = 'TEXT_NOT_CONTAINS';
    const TEXT_STARTS_WITH = 'TEXT_STARTS_WITH';
    const TEXT_ENDS_WITH = 'TEXT_ENDS_WITH';
    const TEXT_EQ = 'TEXT_EQ';
    const TEXT_IS_EMAIL = 'TEXT_IS_EMAIL';
    const TEXT_IS_URL = 'TEXT_IS_URL';
    const DATE_EQ = 'DATE_EQ';
    const DATE_BEFORE = 'DATE_BEFORE';
    const DATE_AFTER = 'DATE_AFTER';
    const DATE_ON_OR_BEFORE = 'DATE_ON_OR_BEFORE';
    const DATE_ON_OR_AFTER = 'DATE_ON_OR_AFTER';
    const DATE_BETWEEN = 'DATE_BETWEEN';
    const DATE_NOT_BETWEEN = 'DATE_NOT_BETWEEN';
    const DATE_IS_VALID = 'DATE_IS_VALID';
    const ONE_OF_RANGE = 'ONE_OF_RANGE';
    const ONE_OF_LIST = 'ONE_OF_LIST';
    const BLANK = 'BLANK';
    const NOT_BLANK = 'NOT_BLANK';
    const CUSTOM_FORMULA = 'CUSTOM_FORMULA';
}
