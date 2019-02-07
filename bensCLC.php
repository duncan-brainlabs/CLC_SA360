<?php
namespace Brainlabs\SA360ConversionLagCalculator;
use Brainlabs\Gmailer\Gmailer;
use Brainlabs\Sheetsy\Sheetsy;
use Brainlabs\Adwordy\Adwordy;
use Brainlabs\Tracking\Tracker;
class ConversionLagCalculator
{
    const MAINTAINER = [
        'duncan@brainlabsdigital.com'
    ];
    private $ssId;
    private $gmailer;
    private $sheetsy;
    private $adwordy;
    public function __construct(
        $ssId,
        Gmailer $gmailer,
        Sheetsy $sheetsy,
        AdWordy $adwordy
    ) {
        $this->ssId = $ssId;
        $this->gmailer = $gmailer;
        $this->sheetsy = $sheetsy;
        $this->adwordy = $adwordy;
    }
    /**
     * @return void
     */
    public function run()
    {
        $ss = $this->sheetsy->getSpreadsheetById($this->ssId);
        $sheet = $ss->getSheetByName("Accounts");
        $dbParser = new DashboardParser();
        $rows = $dbParser->parse($sheet);
        foreach ($rows as $row) {
            $status = $row->getStatus();
            if ($status === "Paused") {
                continue;
            }
            $this->runRow($row);
        }
    }
    private function runRow(Row $row)
    {
        $accName = $row->getAccountName();
        $ssId = $row->getSpreadsheetId();
        echo "\nRunning for account " . $accName . "\n\n";
        $ss = $this->sheetsy->getSpreadsheetById($ssId);
        $sheets = $ss->getSheets();
        $inputParser = new InputParser();
        foreach ($sheets as $sheet) {
            if (strpos($sheet->getName(), 'Inputs') === false) {
                continue;
            }
            echo "Running for sheet: " . $sheet->getName() . "\n";
            try {
                $settings = $inputParser->parse($sheet);
                $inputSheet = new InputSheet($sheet->getName(), $ss, $settings, $this->adwordy);
                $outputSheets = $inputSheet->generateOutputSheets();
                foreach ($outputSheets as $outputSheet) {
                    $outputSheet->writeToSheet();
                }
                $toolName = "Conversion Lag Calculator";
                $version = "v0.3.5";
                $platform = Tracker::ADWORDS;
                $accountIds = $settings->getClientIds();
                Tracker::updateDatabase($toolName, $version, $accountIds, $platform);
            } catch (AMException $e) {
                $sheet->setValuesFromPoint(
                    [["Error On Sheet", $e->getMessage()]],
                    $sheet->getLastRow() + 1,
                    0
                );
                $this->notifyAM($sheet->getName(), $row, $e);
            } catch (\Throwable $e) {
                echo "Error in account " . $accName . "\n";
                echo "Caught exception: " . $e->getMessage() . "\n";
                $this->gmailer->sendEmail(
                    self::MAINTAINER,
                    'Error in conversion lag tracker',
                    'Error for account with name ' . $accName . ': ' .
                        $e->getMessage() . "\n",
                    []
                );
            }
        }
    }
    private function notifyAM(string $sheetName, Row $row, AMException $e)
    {
        $contactEmails = $this->parseEmails($row->getContactEmails());
        $this->gmailer->send(
            $contactEmails,
            "BL Tools | Conversion Lag Calculator | {$row->getAccountName()} | Error",
            "Error running Conversion Lag Calculator on account {$row->getAccountName()}: <br>" .
            "{$e->getMessage()} <br><br>" .
            "Please can you fix {$sheetName} in {$row->getSpreadsheetUrl()}",
            []
        );
    }
    private function parseEmails(string $contactEmails): array
    {
        $contactEmails = trim($contactEmails);
        if (!$contactEmails) {
            return self::MAINTAINER;
        }
        $partialEmailList = explode(",", $contactEmails);
        $partialEmailList = array_map('trim', $partialEmailList);
        $emails = [];
        foreach ($partialEmailList as $emailAddress) {
            $atPos = strpos($emailAddress, "@");
            if ($atPos === false) {
                $emails[] = $emailAddress . "@brainlabsdigital.com";
            } elseif ($atPos == strlen($emailAddress) - 1) {
                $emails[] = $emailAddress . "brainlabsdigital.com";
            } else {
                $emails[] = $emailAddress;
            }
        }
        return $emails;
    }
}