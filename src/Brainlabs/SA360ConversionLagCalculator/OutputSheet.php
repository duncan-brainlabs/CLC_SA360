<?php

namespace Brainlabs\SA360ConversionLagCalculator;

use Brainlabs\Sheetsy\Spreadsheet;
use Brainlabs\Sheetsy\Sheet;
use Brainlabs\Sheetsy\Rect;

class OutputSheet
{

    private $inputSheet;
    private $sheetsy;

    public function __construct(
        InputSheet $inputSheet,
        Sheetsy $sheetsy
    ){
        $this->inputSheet = $inputSheet;
        $this->sheetsy = $sheetsy;
    }

    /**
    * @return Sheet
    */
    public function createOutputSheet()
    {
        $outputSheetName = $this->getName();
        if ($this->sheetsy->getSheetByName($outputSheetName) === null) {
            $this->makeSheet($outputSheetName);
        }
        
        $outputSheet = $sheetsy->getSheetByName($outputSheetName);

        // Write first headers row based on conversion window
        $headersVals = [];
        $headersVals[] = ["Date", "Number of days later"];
        $headersRect = new Rect(0, 0, count($headersVals), count($headersVals[0]));
        $outputSheet->setValues($headersVals, $headersRect);
        
        $daysLaterVals = [];
        $daysLaterValsRow = [];
        $daysLaterValsRow[] = "";
        $convWindow = $this->inputSheet->getConversionWindow();
        for ($i=1; $i<=$convWindow; $i++) {
            $daysLaterValsRow[] = $i;
        }
        $daysLaterVals[] = $daysLaterValsRow;
        $daysLaterRect = new Rect(1, 0, count($daysLaterVals), count($daysLaterVals[0]));
        $outputSheet->setValues($daysLaterVals, $daysLaterRect);
        echo "Updating " . $outputSheetName . " values...\n";
        $datesStartRow = 2;
        $datesStartCol = 0;
        $dateRows = $this->readExistingDateRows(
        $datesStartRow,
        $datesStartCol,
        $outputSheet
        );
        // If no row for yesterday, add it in
        $yesterday = date('Ymd', strtotime("-1 days"));
        if (array_search([$yesterday], $dateRows) === false) {
        echo "Can't find yesterday so adding new row\n";
        $vals = [[$yesterday]];
        $rect = new Rect($datesStartRow + count($dateRows), $datesStartCol, 1, 1);
        $outputSheet->setValues($vals, $rect);
        $dateRows[] = [$yesterday];
        }
                //This bit should get the conversion value for each date in $dateRows and pass it to fillCell
                                foreach ($dateRows as $index => $dateRow) {
                                $date = $dateRow[0];
                                $datePicker = new MatchesValueRule(new DateSegment(), $date);
                                $segmentRules = array_merge([$datePicker], $this->segmentRules);
                                $reportProvider = new AdWordsReportProvider($this->reportIterators, $segmentRules);
                                $totalMetric = $this->metric->calculateValueFromReports($reportProvider);
                                echo $this->metric->getName() . ": " . $totalMetric . "\n";
                                $dayDiff = $this->getDayDifference($date);
                                if ($this->settings->differenceIsWithinWindowSize($dayDiff)) {
                                $this->fillCell($outputSheet, $datesStartRow, $index, $dayDiff, $totalMetric);
                                }
                                }
                                echo "\nFinished updating sheet " . $outputSheetName . "\n\n\n";
                                }
        /**
        * @return void
        */
        public function getName()
        {
        return $inputSheet->getRowName() . " - Output Sheet";
        }                            
                                
        private function makeSheet(string $sheetName)
        {
        try {
        $this->sheetsy->makeSheet($);
        } catch (\Google_Service_Exception $e) {
        throw new SSTooLargeException("The spreadsheet has too many cells.");
        }
        }

        /**
        * @param int $startRow
        * @param int $startCol
        * @param Sheet $outputSheet
        * @return mixed[]
        */
        private function readExistingDateRows($startRow, $startCol, $outputSheet)
        {
        $numDates = $outputSheet->getLastRow() - $startRow + 1;
        if ($numDates == 0) {
        // For new sheet, start new row for yesterday
        $newRowVals = [[date('Ymd', strtotime("-1 days"))]];
        $newRowRect = new Rect($startRow, $startCol, 1, 1);
        $outputSheet->setValues($newRowVals, $newRowRect);
        return $newRowVals;
        } else {
        $dateColRect = new Rect($startRow, $startCol, $numDates, 1);
        return $outputSheet->getValues($dateColRect);
        }
        }
        /**
        * @param string $date
        * @return int
        */
        private function getDayDifference($date)
        {
        $todaySeconds = time();
        $dateSeconds = strtotime($date);
        $secondsDiff = $todaySeconds - $dateSeconds;
        $dayDiff = floor($secondsDiff / 86400);
        return $dayDiff;
        }
        /**
        * @param Sheet $sheet
        * @param int $datesStartRow
        * @param int $index
        * @param int $dayDiff
        * @param mixed $totalConvs Some numerical type.
        * @return void
        */
        private function fillCell($sheet, $datesStartRow, $index, $dayDiff, $totalConvs)
        {
        $sheetRow = $datesStartRow + $index;
        $sheetCol = $dayDiff;
        $rect = new Rect($sheetRow, $sheetCol, 1, 1);
        $sheet->setValues([[$totalConvs]], $rect);
        }
    }
}
?>
