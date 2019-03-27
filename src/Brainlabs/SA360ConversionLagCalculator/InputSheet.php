<?php

namespace Brainlabs\SA360ConversionLagCalculator;

use Brainlabs\Sheetsy\Sheetsy;
use Brainlabs\Sheetsy\Sheet;
use Brainlabs\Sheetsy\Rect;
use Brainlabs\SA360ConversionLagCalculator\Configuration;

class InputSheet
{
    private $inputSheet;

    /**
    * @param string $src Location of the config file
    * @return void
    */
    public function __construct($src)
    {
        $sheetsy = new Sheetsy();
        $configuration = new Configuration($src);
        $inputSheetUrl = $configuration->getInputSsUrl();
        $inputSheet = $sheetsy->getSpreadsheetByUrl($inputSheetUrl);
        $this->inputSheet = $inputSheet;
    }
    
    /**
    * @return Rect
    */
    public function getRect()
    {
        return new Rect(2, 0, 0, 11);
    }

    /**
    * @return string
    */    

    public function getRowStatus()
    {
        return $inputSheet->getValues($this->getRect()[0]);
    }

    public function getRowName()
    {
        return $inputSheet->getValues($this->getRect()[1]);
    }

    public function getAdvertiserId()
    {
        return $inputSheet->getValues($this->getRect()[2]);
    }

    public function getAccountId()
    {
        return $inputSheet->getValues($this->getRect()[3]);
    }

    public function getContactEmails()
    {
        return $inputSheet->getValues($this->getRect()[4]);
    }

    public function getCampaignStatus()
    {
        return $inputSheet->getValues($this->getRect()[5]);
    }

    public function getCampaignIncludesOperator()
    {
        return $inputSheet->getValues($this->getRect()[6]);
    }

    public function getCampaignIncludes()
    {
        return $inputSheet->getValues($this->getRect()[7]);
    }

    public function getCampaignExcludesOperator()
    {
        return $inputSheet->getValues($this->getRect()[8]);
    }

    public function getCampaignNameExcludes()
    {
        return $inputSheet->getValues($this->getRect()[9]);
    }

    public function getColumnName()
    {
        return $inputSheet->getValues($this->getRect()[10]);
    }
    
    public function getStartDate()
    {
        return $inputSheet->getValues($this->getRect()[11])." days";
    }

    public function getConversionWindow()
    {
        return $inputSheet->getValues($this->getRect()[12]);
    }
}
?>
