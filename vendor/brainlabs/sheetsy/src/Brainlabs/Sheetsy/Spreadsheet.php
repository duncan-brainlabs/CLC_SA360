<?php
/**
 * @author ryutaro@brainlabsdigital.com
 */
namespace Brainlabs\Sheetsy;

use Google_Service_Sheets_AddSheetRequest;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_CopySheetToAnotherSpreadsheetRequest;
use Google_Service_Sheets_DeleteSheetRequest;
use Google_Service_Sheets_GridProperties;
use Google_Service_Sheets_Sheet;
use Google_Service_Sheets_SheetProperties;
use Google_Service_Sheets_Spreadsheet;
use Google_Service_Sheets_Request;

class Spreadsheet
{
    /** @var Sheetsy $sheetsy */
    private $sheetsy;

    /** @var string $spreadsheetId */
    private $spreadsheetId;

    /** @var Google_Service_Sheets_Spreadsheet $spreadsheet */
    private $spreadsheet;

    /**
     * @param Sheetsy $sheetsy
     * @param string $spreadsheetId
     * @param Google_Service_Sheets_Spreadsheet $spreadsheet
     * @return void
     */
    private function __construct(
        $sheetsy,
        $spreadsheetId,
        $spreadsheet
    ) {
        $this->sheetsy = $sheetsy;
        $this->spreadsheetId = $spreadsheetId;
        $this->spreadsheet = $spreadsheet;
    }

    /**
     * Construct using the spreadsheet ID.
     * @param Sheetsy $sheetsy
     * @param string $id
     * @return Spreadsheet
     * @throws Exception
     */
    public static function fromId(Sheetsy $sheetsy, $id)
    {
        $opts = [
            'includeGridData' => false
        ];
        $spreadsheet = $sheetsy->callGetSpreadsheet($id, $opts);
        return new Spreadsheet($sheetsy, $id, $spreadsheet);
    }

    /**
     * Return all sheets
     * @return Sheet[]
     */
    public function getSheets()
    {
        $result = [];
        foreach ($this->getSpreadsheet()->getSheets() as $sheet) {
            $result[] = new Sheet(
                $this->sheetsy,
                $this->spreadsheetId,
                $sheet->getProperties()->getSheetId(),
                $sheet
            );
        }
        return $result;
    }

    /**
     * Return a Sheet by the given ID or null if none are found.
     * @param int $id
     * @return Sheet|null
     */
    public function getSheetById($id)
    {
        foreach ($this->getSpreadsheet()->getSheets() as $sheet) {
            if ($id === $sheet->getProperties()->getSheetId()) {
                return new Sheet(
                    $this->sheetsy,
                    $this->spreadsheetId,
                    $id,
                    $sheet
                );
            }
        }
    }

    /**
     * Return a Sheet by the given name, or null if none are found.
     * @param string $name
     * @return Sheet|null
     */
    public function getSheetByName($name)
    {
        foreach ($this->spreadsheet->getSheets() as $sheet) {
            $title = $sheet->getProperties()->getTitle();
            if (mb_strtolower($name) === mb_strtolower($title)) {
                return new Sheet(
                    $this->sheetsy,
                    $this->spreadsheetId,
                    $sheet->getProperties()->getSheetId(),
                    $sheet
                );
            }
        }
        return null;
    }

    /**
     * Duplicate the given sheet and put it into this spreadsheet by default
     * or a specified spreadsheet.
     * Return the duplicated sheet.
     * @param Sheet $sheet
     * @param Spreadsheet $spreadsheet
     * @return Sheet
     */
    public function duplicateSheet(Sheet $sheet, Spreadsheet $spreadsheet = null)
    {
        if (is_null($spreadsheet)) {
            $destinationSpreadsheetId = $this->spreadsheetId;
            $destinationSpreadsheet = $this;
        } else {
            $destinationSpreadsheetId = $spreadsheet->getSpreadsheetId();
            $destinationSpreadsheet = $spreadsheet;
        }

        $request =
            new Google_Service_Sheets_CopySheetToAnotherSpreadsheetRequest();
        $request->setDestinationSpreadsheetId($destinationSpreadsheetId);

        $duplicatedSheetProperties = $this->sheetsy->callCopyToSpreadsheet(
            $sheet->getSpreadsheetId(),
            $sheet->getSheetId(),
            $request
        );

        $destinationSpreadsheet->pull();
        return $destinationSpreadsheet
            ->getSheetById($duplicatedSheetProperties->sheetId);
    }

    /**
     * @param Sheet $sheet
     * @return void
     */
    public function deleteSheet(Sheet $sheet)
    {
        $deleteRequest = new Google_Service_Sheets_DeleteSheetRequest();
        $deleteRequest->setSheetId($sheet->getSheetId());

        $request = new Google_Service_Sheets_Request();
        $request->setDeleteSheet($deleteRequest);

        $batchRequest = new
            Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $batchRequest->setRequests([$request]);

        $this->sheetsy->callBatchUpdate($this->spreadsheetId, $batchRequest);

        // Don't forget to update the cache!
        $this->pull();
    }

    /**
     * @param string $sheetName
     * @param int $rowCount
     * @param int $columnCount
     * @param string $sheetType
     * @return Sheet
     */
    public function makeSheet(
        $sheetName,
        $rowCount = 1000,
        $columnCount = 26,
        $sheetType = "GRID"
    ) {
        // If the sheet already exists, return it.
        $sheet = $this->getSheetByName($sheetName);
        if (is_null($sheet)) {
            $sheet = $this->doMakeSheet(
                $sheetName,
                $rowCount,
                $columnCount,
                $sheetType
            );
        }
        return $sheet;
    }

    /**
     * @param string $sheetName
     * @param int $rowCount
     * @param int $columnCount
     * @param string $sheetType
     * @return Sheet
     * @throws Exception
     */
    private function doMakeSheet(
        $sheetName,
        $rowCount,
        $columnCount,
        $sheetType
    ) {
        $sheetProperty = new Google_Service_Sheets_SheetProperties();
        $sheetProperty->setTitle($sheetName);
        $sheetProperty->setSheetType($sheetType);

        $gridProperties = new Google_Service_Sheets_GridProperties();
        $gridProperties->setRowCount($rowCount);
        $gridProperties->setColumnCount($columnCount);
        $sheetProperty->setGridProperties($gridProperties);

        $addSheetRequest = new Google_Service_Sheets_AddSheetRequest();
        $addSheetRequest->setProperties($sheetProperty);
        $request = new Google_Service_Sheets_Request();
        $request->setAddSheet($addSheetRequest);

        $batchRequest = new
            Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $batchRequest->setRequests([$request]);

        $response = $this->sheetsy->callBatchUpdate(
            $this->spreadsheetId,
            $batchRequest
        );

        // Get the ID of the new sheet.
        $replies = $response->getReplies();
        $reply = array_shift($replies);
        $addSheetResponse = $reply->getAddSheet();
        $addedSheetId = $addSheetResponse->getProperties()->getSheetId();

        // Resync the spreadsheet. This will get us the new sheet in this
        // spreadsheet's list of sheets. This is lazy but it is simple.
        $this->pull();
        return $this->getSheetById($addedSheetId);
    }

    /**
     * Synchronize the spreadsheet.
     * @return Google_Service_Sheets_Spreadsheet
     */
    public function pull()
    {
        $opts = [
            'includeGridData' => false
        ];
        $this->spreadsheet =
            $this->sheetsy->callGetSpreadsheet($this->spreadsheetId, $opts);
        return $this->spreadsheet;
    }

    /**
     * @return Sheetsy
     */
    public function getSheetsy()
    {
        return $this->sheetsy;
    }

    /**
     * @return string
     */
    public function getSpreadsheetId()
    {
        return $this->spreadsheetId;
    }

    /**
     * @return Google_Service_Sheets_Spreadsheet
     */
    public function getSpreadsheet()
    {
        return $this->spreadsheet;
    }

    /**
     * Return the title/name of the current spreadsheet
     * @return String
     */
    public function getName()
    {
        return $this->getSpreadsheet()->getProperties()->getTitle();
    }
}
