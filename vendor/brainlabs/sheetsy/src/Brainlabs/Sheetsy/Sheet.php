<?php
/**
 * @author ryutaro@brainlabsdigital.com
 */
namespace Brainlabs\Sheetsy;

use Google_Model;
use Google_Service_Sheets_AddChartRequest;
use Google_Service_Sheets_AppendDimensionRequest;
use Google_Service_Sheets_AutoResizeDimensionsRequest;
use Google_Service_Sheets_BatchUpdateSpreadsheetRequest;
use Google_Service_Sheets_BatchUpdateSpreadsheetResponse;
use Google_Service_Sheets_BooleanCondition;
use Google_Service_Sheets_Border;
use Google_Service_Sheets_CellData;
use Google_Service_Sheets_CellFormat;
use Google_Service_Sheets_ConditionValue;
use Google_Service_Sheets_CopyPasteRequest;
use Google_Service_Sheets_DataValidationRule;
use Google_Service_Sheets_DeleteDimensionRequest;
use Google_Service_Sheets_DimensionProperties;
use Google_Service_Sheets_DimensionRange;
use Google_Service_Sheets_ExtendedValue;
use Google_Service_Sheets_GridData;
use Google_Service_Sheets_GridProperties;
use Google_Service_Sheets_GridRange;
use Google_Service_Sheets_InsertDimensionRequest;
use Google_Service_Sheets_NumberFormat;
use Google_Service_Sheets_NumberFormatType;
use Google_Service_Sheets_PivotTable;
use Google_Service_Sheets_Request;
use Google_Service_Sheets_RowData;
use Google_Service_Sheets_SetDataValidationRequest;
use Google_Service_Sheets_Sheet;
use Google_Service_Sheets_SheetProperties;
use Google_Service_Sheets_SortRangeRequest;
use Google_Service_Sheets_SortSpec;
use Google_Service_Sheets_TextFormatRun;
use Google_Service_Sheets_UpdateBordersRequest;
use Google_Service_Sheets_UpdateCellsRequest;
use Google_Service_Sheets_UpdateChartSpecRequest;
use Google_Service_Sheets_UpdateDimensionPropertiesRequest;
use Google_Service_Sheets_UpdateSheetPropertiesRequest;
use Google_Service_Sheets_UpdateValuesResponse;
use Google_Service_Sheets_ValueRange;

use Exception;

class Sheet
{
    /** @var Sheetsy $sheetsy */
    private $sheetsy;

    /** @var string $spreadsheetId */
    private $spreadsheetId;

    /** @var int $sheetId */
    private $sheetId;

    /** @var Google_Service_Sheets_Sheet $sheet Cache of the sheet */
    private $sheet;

    /**
     * @param Sheetsy $sheetsy
     * @param string $spreadsheetId
     * @param int $sheetId
     * @param Google_Service_Sheets_Sheet $sheet
     * @return void
     */
    public function __construct(
        Sheetsy $sheetsy,
        string $spreadsheetId,
        int $sheetId,
        Google_Service_Sheets_Sheet $sheet
    ) {
        $this->sheetsy = $sheetsy;
        $this->spreadsheetId = $spreadsheetId;
        $this->sheetId = $sheetId;
        $this->sheet = $sheet;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->getProperties()
            ->getTitle();
    }

    /**
     * @return int
     */
    public function getHeight()
    {
        return $this->getGridProperties()
            ->getRowCount();
    }

    /**
     * @return int
     */
    public function getWidth()
    {
        return $this->getGridProperties()
            ->getColumnCount();
    }

    /**
     * @return Google_Service_Sheets_SheetProperties
     */
    private function getProperties()
    {
        return $this->sheet->getProperties();
    }

    /**
     * @return Google_Service_Sheets_GridProperties
     */
    private function getGridProperties()
    {
        return $this->getProperties()
            ->getGridProperties();
    }

    /**
     * @param Rect $rect
     * @return Google_Service_Sheets_GridData
     */
    private function getGridData(Rect $rect)
    {
        $range = $this->getA1Range($rect);

        $params = [];
        $params['ranges'] = [$range];
        $params['includeGridData'] = true;

        $spreadsheet = $this->sheetsy->callGetSpreadsheet(
            $this->spreadsheetId,
            $params
        );

        return $spreadsheet
            ->getSheets()[0]
            ->getData()[0];
    }

    /**
     * @param Rect $rect
     * @return Google_Service_Sheets_RowData
     */
    private function getRowData(Rect $rect)
    {
        return $this->getGridData($rect)
            ->getRowData();
    }

    /**
     * @param Rect $rect
     * @return Google_Service_Sheets_GridRange
     */
    private function getGridRange(Rect $rect)
    {
        $range = new Google_Service_Sheets_GridRange();
        $range->setSheetId($this->sheetId);

        $row = $rect->getRow();
        $height = $rect->getHeight();
        $range->setStartRowIndex($row);
        $range->setEndRowIndex($row + $height);

        $column = $rect->getColumn();
        $width = $rect->getWidth();
        $range->setStartColumnIndex($column);
        $range->setEndColumnIndex($column + $width);

        return $range;
    }

    /**
     * @param string $dimension ROWS|COLUMNS
     * @param int $start
     * @param int $end
     * @return Google_Service_Sheets_DimensionRange
     */
    private function getDimensionRange(
        string $dimension,
        int $start,
        int $end
    ) {
        $range = new Google_Service_Sheets_DimensionRange();
        $range->setSheetId($this->sheetId);
        $range->setDimension($dimension);
        $range->setStartIndex($start);
        $range->setEndIndex($end);

        return $range;
    }

    /**
     * @param mixed $request
     * @param string $field
     */
    private function sendUpdateRequest($request, string $field)
    {
        $sheetsRequest = new Google_Service_Sheets_Request();
        $sheetsRequest->{$field}($request);

        return $this->callBatchUpdate($sheetsRequest);
    }

    /**
     * @param Google_Service_Sheets_Request $request
     */
    private function callBatchUpdate(Google_Service_Sheets_Request $request)
    {
        $requests = [$request];

        $batchRequest =
            new Google_Service_Sheets_BatchUpdateSpreadsheetRequest();
        $batchRequest->setRequests($requests);

        $response = $this->sheetsy->callBatchUpdate(
            $this->spreadsheetId,
            $batchRequest
        );

        return $response;
    }

    /**
     * @param Rect $rect
     * @param string $getFormat Method to call on a cell format
     * @param mixed $default
     * @param callable $createFormat
     * @return mixed[][]
     */
    private function getFormats(
        Rect $rect,
        string $getFormat,
        $default,
        $createFormat
    ) {
        $formatsRange = [];
        $rows = $this->getRowData($rect);

        if (!$rows) {
            $height = $rect->getHeight();
            $width = $rect->getWidth();

            for ($i = 0; $i < $height; $i++) {
                $formatsRow = [];

                for ($j = 0; $j < $width; $j++) {
                    $formatsRow[] = $default;
                }

                $formatsRange[] = $formatsRow;
            }
        }

        foreach ($rows as $row) {
            $formatsRow = [];
            $values = $row->getValues();

            foreach ($values as $value) {
                $format = $value->getUserEnteredFormat();
                if (!$format) {
                    $formatsRow[] = $default;
                    continue;
                }

                $specificFormat = $format->{$getFormat}();
                if (!$specificFormat) {
                    $formatsRow[] = $default;
                    continue;
                }

                $formatsRow[] = $createFormat($specificFormat);
            }

            $formatsRange[] = $formatsRow;
        }

        return $formatsRange;
    }

    public function getColumnWidth(int $columnIndex)
    {
        $rect = new Rect(0, $columnIndex, 1, 1);
        $columnsMetaData = $this->getGridData($rect)->getColumnMetadata();
        return $columnsMetaData[0]->getPixelSize();
    }

    /**
     * @return int
     * last row with data, 0 indexed
     */
    public function getLastRow()
    {
        $height = $this->getHeight();
        $width = $this->getWidth();
        $rect = new Rect(0, 0, $height, $width);
        $values = $this->getValues($rect);
        if (is_null($values)) {
            return -1;
        } else {
            return count($values) - 1;
        }
    }

    /**
     * @return int
     * last column with data, 0 indexed
     * returns -1 for empty sheet
     */
    public function getLastColumn()
    {
        $height = $this->getHeight();
        $width = $this->getWidth();
        $rect = new Rect(0, 0, $height, $width);
        $values = $this->getValues($rect);
        $maxWidth = 0;
        if (!empty($values)) {
            foreach ($values as $row) {
                $rowWidth = count($row);
                if ($rowWidth > $maxWidth) {
                    $maxWidth = $rowWidth;
                }
            }
        }
        return($maxWidth-1);
    }

    /**
     * @return [][]
     * Substitute for getDataRange() in SpreadsheetApp, but returns the actual
     * values (not the Rect).
     * Get all values on the sheet as a rectangular 2d array.
     * Returns [[]] for empty sheet.
     */
    public function getAllPaddedValues()
    {
        $height = $this->getHeight();
        $width = $this->getWidth();
        $rect = new Rect(0, 0, $height, $width);
        $rows = $this->getValues($rect);

        if (is_null($rows)) {
            return [[]];
        }

        $widths = array_map(function ($row) {
            return sizeof($row);
        }, $rows);
        $maxWidth = max($widths);

        $newRows = [];
        foreach ($rows as $row) {
            $newRow = array_pad($row, $maxWidth, '');
            array_push($newRows, $newRow);
        }

        return $newRows;
    }

    /**
     * @param Rect $rect
     * @return mixed[][]
     */
    public function getValues(Rect $rect)
    {
        $response =
            $this->sheetsy->callGetSpreadsheetValues(
                $this->spreadsheetId,
                $this->getA1Range($rect)
            );
        return $response->getValues();
    }

    /**
     * @param array $values
     * @param Rect $rect
     */
    public function appendValues($values, Rect $rect)
    {
        $sanitizedValues = [];

        foreach ($values as $rowIndex => $row) {
            $sanitizedRow = [];
            foreach ($row as $columnIndex => $value) {
                // We must convert null to Google's null, otherwise we get an
                // error.
                if (is_null($value)) {
                    $sanitizedRow[] = Google_Model::NULL_VALUE;
                    continue;
                }
                $sanitizedRow[] = $value;
            }
            $sanitizedValues[] = $sanitizedRow;
        }

        $body = new Google_Service_Sheets_ValueRange(['values' => $sanitizedValues]);
        $params = ['valueInputOption' => 'RAW'];
        $response =
            $this->sheetsy->callAppendSpreadsheetValues(
                $this->spreadsheetId,
                $this->getA1Range($rect),
                $body,
                $params
            );
    }

    /**
     * @param String $dim either ROWS|COLUMNS
     * @param Int $startIndex
     * @param Int $endIndex
     */
    private function deleteDimension($dim, $startIndex, $endIndex)
    {
        $dimensionRange = $this->getDimensionRange($dim, $startIndex, $endIndex);

        $deleteRequest = new Google_Service_Sheets_DeleteDimensionRequest();
        $deleteRequest->setRange($dimensionRange);

        $this->sendUpdateRequest(
            $deleteRequest,
            'setDeleteDimension'
        );
    }

    /**
     * @param Int $startIndex
     * @param Int $endIndex
     **/
    public function deleteRows($startIndex, $endIndex)
    {
        $this->deleteDimension('ROWS', $startIndex, $endIndex);
        $rowCount = $this->getHeight();
        $this->getGridProperties()->setRowCount($rowCount-($endIndex-$startIndex));
    }

    /**
     * @param Int $startIndex
     * @param Int $endIndex
     **/
    public function deleteColumns($startIndex, $endIndex)
    {
        $this->deleteDimension('COLUMNS', $startIndex, $endIndex);
        $columnCount = $this->getWidth();
        $this->getGridProperties()->setColumnCount($columnCount-($endIndex-$startIndex));
    }

    /**
     * @param String $dim either ROWS|COLUMNS
     * @param Int $startIndex
     * @param Int $endIndex
     */
    private function insertDimension($dim, $startIndex, $endIndex)
    {
        $dimensionRange = $this->getDimensionRange($dim, $startIndex, $endIndex);

        $insertRequest = new Google_Service_Sheets_InsertDimensionRequest();
        $insertRequest->setRange($dimensionRange);

        $this->sendUpdateRequest(
            $insertRequest,
            'setInsertDimension'
        );
    }

    /**
     * @param String $dim either ROWS|COLUMNS
     * @param Int $startIndex
     * @param Int $endIndex
     */
    private function autoResizeDimension($dim, $startIndex, $endIndex)
    {
        $dimensionRange = $this->getDimensionRange($dim, $startIndex, $endIndex);

        $autoResizeRequest = new Google_Service_Sheets_AutoResizeDimensionsRequest();
        $autoResizeRequest->setDimensions($dimensionRange);

        $this->sendUpdateRequest(
            $autoResizeRequest,
            'setAutoResizeDimensions'
        );
    }

    /**
     * @param Int $startIndex
     * @param Int $endIndex
     **/
    public function autoResizeRows($startIndex, $endIndex)
    {
        $this->autoResizeDimension('ROWS', $startIndex, $endIndex);
    }

    /**
     * @param Int $startIndex
     * @param Int $endIndex
     **/
    public function autoResizeColumns($startIndex, $endIndex)
    {
        $this->autoResizeDimension('COLUMNS', $startIndex, $endIndex);
    }

    /**
     * @param Int $startIndex
     * @param Int $endIndex
     **/
    public function insertRows($startIndex, $endIndex)
    {
        $this->insertDimension('ROWS', $startIndex, $endIndex);
        $rowCount = $this->getHeight();
        $this->getGridProperties()->setRowCount($rowCount+($endIndex-$startIndex));
    }

    /**
     * @param Int $startIndex
     * @param Int $endIndex
     **/
    public function insertColumns($startIndex, $endIndex)
    {
        $this->insertDimension('COLUMNS', $startIndex, $endIndex);
        $columnCount = $this->getWidth();
        $this->getGridProperties()->setColumnCount($columnCount+($endIndex-$startIndex));
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    public function showColumns($startIndex, $endIndex)
    {
        $this->displayDimension('COLUMNS', $startIndex, $endIndex, false);
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    public function hideColumns($startIndex, $endIndex)
    {
        $this->displayDimension('COLUMNS', $startIndex, $endIndex, true);
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    public function showRows($startIndex, $endIndex)
    {
        $this->displayDimension('ROWS', $startIndex, $endIndex, false);
    }

    /**
     * @param int $startIndex
     * @param int $endIndex
     */
    public function hideRows($startIndex, $endIndex)
    {
        $this->displayDimension('ROWS', $startIndex, $endIndex, true);
    }

    /**
     * @param string ROWS|COLUMNS
     * @param int $startIndex
     * @param int $endIndex
     * @param bool $shouldHide
     */
    private function displayDimension($dim, $startIndex, $endIndex, $shouldHide)
    {
        $dimensionRange = $this->getDimensionRange($dim, $startIndex, $endIndex);

        $updateDimensionPropertiesRequest = new Google_Service_Sheets_UpdateDimensionPropertiesRequest();
        $updateDimensionPropertiesRequest->setRange($dimensionRange);
        $updateDimensionPropertiesRequest->setFields("hiddenByUser");

        $properties = new Google_Service_Sheets_DimensionProperties();
        $properties->setHiddenByUser($shouldHide);

        $updateDimensionPropertiesRequest->setProperties($properties);

        $this->sendUpdateRequest(
            $updateDimensionPropertiesRequest,
            'setUpdateDimensionProperties'
        );
    }

    /**
     * Copy and pastes data from 1 rect to another, optionally to another sheet
     *
     * @param      Rect    $rectFrom   Where you want to copy the data from
     * @param      Rect    $rectTo     Where you want to copy the data to
     * @param      <type>  $sheetToId  The id of the sheet if different to where you are copying from
     */
    public function copyAndPaste(Rect $rectFrom, Rect $rectTo, $sheetToId = null)
    {
        $copyAndPaste = new Google_Service_Sheets_CopyPasteRequest();

        $fromRange = $this->getGridRange($rectFrom);
        $toRange = $this->getGridRange($rectTo);

        if ($sheetToId != null) {
            $toRange->setSheetId($sheetToId);
        }

        $copyAndPaste->setSource($fromRange);
        $copyAndPaste->setDestination($toRange);

        return $this->sendUpdateRequest(
            $copyAndPaste,
            'setCopyPaste'
        );
    }

    /**
     * @param string $name
     * @return Google_Service_Sheets_BatchUpdateSpreadsheetResponse
     */
    public function setName($name)
    {
        $properties = new Google_Service_Sheets_SheetProperties();
        $properties->setSheetId($this->sheetId);
        $properties->setTitle($name);

        $result = $this->setSheetProperties($properties, 'title');

        // Update cached properties.
        $this->getProperties()->setTitle($name);

        return $result;
    }

    /**
     * @param mixed[][] $values
     * @param int $rowStartIndex
     * @param int $columnStartIndex
     * @return Google_Service_Sheets_UpdateValuesResponse
     */
    public function setValuesFromPoint($values, $rowStartIndex, $columnStartIndex)
    {
        $rowCount = count($values);
        $columnCount = max(array_map('count', $values));
        $rect = new Rect(
            $rowStartIndex,
            $columnStartIndex,
            $rowCount,
            $columnCount
        );

        foreach ($values as &$row) {
            $numColumns = count($row);
            if ($numColumns < $columnCount) {
                $row = array_merge($row, array_fill(0, $columnCount - $numColumns, null));
            }
        }
        return $this->setValues($values, $rect);
    }

    /**
     * @param mixed $values 2D-array of values
     * @param Rect $rect
     * @return Google_Service_Sheets_UpdateValuesResponse|void
     */
    public function setValues($values, Rect $rect)
    {
        if ($rect->getHeight() < 1) {
            return;
        }
        if ($rect->getWidth() < 1) {
            return;
        }

        $sanitizedValues = [];

        foreach ($values as $rowIndex => $row) {
            $sanitizedRow = [];
            foreach ($row as $columnIndex => $value) {
                // We must convert null to Google's null, otherwise we get an
                // error.
                if (is_null($value)) {
                    $sanitizedRow[] = Google_Model::NULL_VALUE;
                    continue;
                }
                $sanitizedRow[] = $value;
            }
            $sanitizedValues[] = $sanitizedRow;
        }

        // Make sure the values fit on the sheet.
        if ($this->getHeight() < $rect->getRow() + $rect->getHeight()) {
            $this->appendRows($rect->getRow() + $rect->getHeight() -
                $this->getHeight());
        }
        if ($this->getWidth() < $rect->getColumn() + $rect->getWidth()) {
            $this->appendColumns($rect->getColumn() + $rect->getWidth() -
                $this->getWidth());
        }

        $valueRange = new Google_Service_Sheets_ValueRange();
        $valueRange->setValues($sanitizedValues);
        $valueRange->setRange($this->getA1Range($rect));
        $valueRange->setMajorDimension('ROWS');

        $opts = [
            'valueInputOption' => 'USER_ENTERED'
        ];

        return
            $this->sheetsy->callUpdateSpreadsheetValues(
                $this->spreadsheetId,
                $this->getA1Range($rect),
                $valueRange,
                $opts
            );
    }

    /**
     * Array of associative  arrays using keys 'column' and 'ascending'.
     * 'column' is relative index in Rect for which column to sort by, uses 0
     * indexing. 'ascending' is a boolean with true for ascending sort order and
     * false for descending.
     * @param array $sortOperations
     *
     * @param Rect $rect
     *
     * @return Google_Service_Sheets_UpdateValuesResponse
     */
    public function sort($sortOperations, Rect $rect)
    {
        $sortSpecs = [];
        foreach ($sortOperations as $operation) {
            $sortSpec = new Google_Service_Sheets_SortSpec();
            if ($operation['ascending']) {
                $sortSpec->setSortOrder('ASCENDING');
            } else {
                $sortSpec->setSortOrder('DESCENDING');
            }
            $sortSpec->setDimensionIndex($operation['column']);

            $sortSpecs[] = $sortSpec;
        }

        $gridRange = $this->getGridRange($rect);

        $sortRangeRequest = new Google_Service_Sheets_SortRangeRequest();
        $sortRangeRequest->setRange($gridRange);
        $sortRangeRequest->setSortSpecs($sortSpecs);

        return $this->sendUpdateRequest(
            $sortRangeRequest,
            'setSortRange'
        );
    }

    /**
     * Sets the number format.
     * @param NumberFormat[][] $formats
     * @param Rect $rect
     * @return void
     */
    public function setNumberFormats($formats, Rect $rect)
    {
        $requestRows = [];
        foreach ($formats as $row) {
            $rowData = new Google_Service_Sheets_RowData();
            $cellRow = [];
            foreach ($row as $format) {
                $numberFormat = new Google_Service_Sheets_NumberFormat();
                $numberFormat->setType($format->getType());
                $numberFormat->setPattern($format->getPattern());
                $cellFormat = new Google_Service_Sheets_CellFormat();
                $cellFormat->setNumberFormat($numberFormat);
                $cellData = new Google_Service_Sheets_CellData();
                $cellData->setUserEnteredFormat($cellFormat);
                $cellRow[] = $cellData;
            }
            $rowData->setValues($cellRow);
            $requestRow[] = $rowData;
        }

        $this->setCells($requestRow, 'userEnteredFormat.numberFormat', $rect);
    }

    /**
     * @return NumberFormat[][]
     */
    public function getNumberFormats(Rect $rect)
    {
        $default = new NumberFormat(null, null);

        return $this->getFormats(
            $rect,
            'getNumberFormat',
            $default,
            function ($numberFormat) {
                return new NumberFormat(
                    $numberFormat->getType(),
                    $numberFormat->getPattern()
                );
            }
        );
    }

    /**
     * Sets a data validation rule on a Rect
     * @param DataValidationRule $dataValidationRule
     * @param Rect               $rect
     */
    public function setDataValidation(
        DataValidationRule $dataValidationRule,
        Rect $rect
    ) {
        $rule = $dataValidationRule->unwrap();

        $gridRange = $this->getGridRange($rect);

        $dvRequest = new Google_Service_Sheets_SetDataValidationRequest;
        $dvRequest->setRange($gridRange);
        $dvRequest->setRule($rule);

        $this->sendUpdateRequest(
            $dvRequest,
            'setSetDataValidation'
        );
    }

    public function getDataValidation(Rect $rect): array
    {
        $dataValidationRules = array();
        $rows = $this->getGridData($rect)->getRowData();

        foreach ($rows as $row) {
            $dataValidationRulesRow = array();

            foreach ($row as $cell) {
                array_push(
                    $dataValidationRulesRow,
                    DataValidationRule::wrap($cell->getDataValidation())
                );
            }

            array_push($dataValidationRules, $dataValidationRulesRow);
        }
        return $dataValidationRules;
    }

    /**
    * Sets the horizontal alignment
    * @param string[][] $alignments 'LEFT' 'RIGHT' or 'CENTER'
    * @param Rect $rect
    * @return void
    */
    public function setHorizontalAlignments($alignments, Rect $rect)
    {
        $requestRows = [];
        foreach ($alignments as $row) {
            $rowData = new Google_Service_Sheets_RowData();
            $cellRow = [];
            foreach ($row as $alignment) {
                $cellFormat = new Google_Service_Sheets_CellFormat();
                $cellFormat->setHorizontalAlignment($alignment);
                $cellData = new Google_Service_Sheets_CellData();
                $cellData->setUserEnteredFormat($cellFormat);
                $cellRow[] = $cellData;
            }
            $rowData->setValues($cellRow);
            $requestRow[] = $rowData;
        }
        $this->setCells($requestRow, 'userEnteredFormat.horizontalAlignment', $rect);
    }

    /**
     * @param Rect $rect
     * @return string[][]
     */
    public function getHorizontalAlignments(Rect $rect)
    {
        $defaultAlignment = 'LEFT';

        return $this->getFormats(
            $rect,
            'getHorizontalAlignment',
            $defaultAlignment,
            function ($alignment) {
                return $alignment;
            }
        );
    }

    /**
    * Sets the vertical alignment
    * @param string[][] $alignments 'TOP' 'MIDDLE' or 'BOTTOM'
    * @param Rect $rect
    * @return void
    */
    public function setVerticalAlignments($alignments, Rect $rect)
    {
        $requestRows = [];
        foreach ($alignments as $row) {
            $rowData = new Google_Service_Sheets_RowData();
            $cellRow = [];
            foreach ($row as $alignment) {
                $cellFormat = new Google_Service_Sheets_CellFormat();
                $cellFormat->setVerticalAlignment($alignment);
                $cellData = new Google_Service_Sheets_CellData();
                $cellData->setUserEnteredFormat($cellFormat);
                $cellRow[] = $cellData;
            }
            $rowData->setValues($cellRow);
            $requestRow[] = $rowData;
        }
        $this->setCells($requestRow, 'userEnteredFormat.verticalAlignment', $rect);
    }

    /**
     * @param Rect $rect
     * @return string[][]
     */
    public function getVerticalAlignments(Rect $rect)
    {
        $defaultAlignment = 'BOTTOM';

        return $this->getFormats(
            $rect,
            'getVerticalAlignment',
            $defaultAlignment,
            function ($alignment) {
                return $alignment;
            }
        );
    }

    /**
     * Corresponds to Range.setBorder in Apps Scripts at the time of writing
     * A bool parameter should be
     * - true to set a border
     * - false to remove a border
     * - null to make no change
     *
     * @param bool $top
     * @param bool $left
     * @param bool $bottom
     * @param bool $right
     * @param bool $vertical The internal vertical borders of the range
     * @param bool $horizontal The internal horizontal borders of the range
     * @param Color $color The default is black
     * @param string $style The default is solid
     * @param Rect $rect
     */
    public function setBorder(
        bool $top = null,
        bool $left = null,
        bool $bottom = null,
        bool $right = null,
        bool $vertical = null,
        bool $horizontal = null,
        Color $color = null,
        string $style = null,
        Rect $rect
    ) {
        $range = $this->getGridRange($rect);

        if (!$color) {
            $color = new Color(0, 0, 0, 0);
        }
        if (!$style) {
            $style = Style::SOLID;
        }

        $border = new Google_Service_Sheets_Border();
        $border->setColor($color->unwrap());
        $border->setStyle($style);

        $none = new Google_Service_Sheets_Border();
        $none->setStyle(Style::NONE);

        $updateBordersRequest =
            new Google_Service_Sheets_UpdateBordersRequest();
        $updateBordersRequest->setRange($range);

        if ($top !== null) {
            $borderTop = $top ? $border : $none;
            $updateBordersRequest->setTop($borderTop);
        }
        if ($left !== null) {
            $borderLeft = $left ? $border : $none;
            $updateBordersRequest->setLeft($borderLeft);
        }
        if ($bottom !== null) {
            $borderBottom = $bottom ? $border : $none;
            $updateBordersRequest->setBottom($borderBottom);
        }
        if ($right !== null) {
            $borderRight = $right ? $border : $none;
            $updateBordersRequest->setRight($borderRight);
        }
        if ($vertical !== null) {
            $borderVertical = $vertical ? $border : $none;
            $updateBordersRequest->setInnerVertical($borderVertical);
        }
        if ($horizontal !== null) {
            $borderHorizontal = $horizontal ? $border : $none;
            $updateBordersRequest->setInnerHorizontal($borderHorizontal);
        }

        $this->sendUpdateRequest(
            $updateBordersRequest,
            'setUpdateBorders'
        );
    }

    /**
     * @return Borders[][]
     */
    public function getBorders(
        Rect $rect
    ) {
        $none = new Border(
            Style::NONE,
            null
        );

        $default = new Borders(
            $none,
            $none,
            $none,
            $none
        );

        return $this->getFormats(
            $rect,
            'getBorders',
            $default,
            function ($borders) {
                return Borders::wrap($borders);
            }
        );
    }

    /**
     * Sets the background colour of range described by rect.
     * @param Color[][] $colors
     * @param Rect $rect
     * @return void
     */
    public function setBackgroundColor($colors, Rect $rect)
    {
        $requestRows = [];
        foreach ($colors as $row) {
            $rowData = new Google_Service_Sheets_RowData();
            $cellRow = [];
            foreach ($row as $color) {
                $cellFormat = new Google_Service_Sheets_CellFormat();
                $cellFormat->setBackgroundColor($color->unwrap());
                $cellData = new Google_Service_Sheets_CellData();
                $cellData->setUserEnteredFormat($cellFormat);
                $cellRow[] = $cellData;
            }
            $rowData->setValues($cellRow);
            $requestRows[] = $rowData;
        }
        $this->setCells($requestRows, 'userEnteredFormat.backgroundColor', $rect);
    }

    /**
     * @param RowData[] $rows
     * @param string $fields
     * @param Rect $rect
     * @return void
     */
    private function setCells(array $rows, $fields, Rect $rect)
    {
        $gridRange = $this->getGridRange($rect);

        $updateCellsRequest = new Google_Service_Sheets_UpdateCellsRequest();
        $updateCellsRequest->setFields($fields);
        $updateCellsRequest->setRange($gridRange);
        $updateCellsRequest->setRows($rows);

        $this->sendUpdateRequest(
            $updateCellsRequest,
            'setUpdateCells'
        );
    }

    /**
     * @param Rect $rect
     * @return Color[][]
     */
    public function getBackgroundColor(Rect $rect)
    {
        $default = new Color(1, 1, 1, 0);

        return $this->getFormats(
            $rect,
            'getBackgroundColor',
            $default,
            function ($color) {
                return new Color(
                    $color->getRed(),
                    $color->getGreen(),
                    $color->getBlue(),
                    $color->getAlpha()
                );
            }
        );
    }

    /**
     * Clears the cells on this sheet.
     * @return void
     * @throws Exception
     */
    public function clearSheet()
    {
        $rowCount = $this->getHeight();
        $colCount = $this->getWidth();

        $this->clearRange(new Rect(0, 0, $rowCount, $colCount));
    }

    /**
     * Clears the cells in a given rect
     * @return void
     */
    public function clearRange(Rect $rect)
    {
        $values = [];

        $height = $rect->getHeight();
        $width = $rect->getWidth();

        for ($rowIndex = 0; $rowIndex < $height; $rowIndex++) {
            $row = [];
            for ($colIndex = 0; $colIndex < $width; $colIndex++) {
                $row[] = "";
            }
            $values[] = $row;
        }

        $this->setValues($values, $rect);
    }

    /**
     * @param int $rowCount
     * @return Google_Service_Sheets_BatchUpdateSpreadsheetResponse
     */
    public function appendRows($rowCount)
    {
        $result = $this->appendDimension('ROWS', $rowCount);
        $this->getGridProperties()->setRowCount(
            $this->getHeight() + $rowCount
        );
        return $result;
    }

    /**
     * @param int $columnCount
     * @return Google_Service_Sheets_BatchUpdateSpreadsheetResponse
     */
    public function appendColumns($columnCount)
    {
        $result =  $this->appendDimension('COLUMNS', $columnCount);
        $this->getGridProperties()->setColumnCount(
            $this->getWidth() + $columnCount
        );
        return $result;
    }

    /**
     * @todo Make this an API call
     * @return Chart[]
     */
    public function getCharts()
    {
        $rawCharts = $this->sheet->getCharts();
        $wrappedCharts = [];

        foreach ($rawCharts as $rawChart) {
            $wrappedCharts[] = new Chart(
                $rawChart->getChartId(),
                $this->sheetId,
                $rawChart
            );
        }

        return $wrappedCharts;
    }

    /**
     * @param string $title
     * @return Chart|null
     */
    public function getChartByTitle(string $title)
    {
        $charts = $this->getCharts();
        foreach ($charts as $chart) {
            if ($chart->getTitle() === $title) {
                return $chart;
            }
        }
        return null;
    }

    /**
     * @param int $id
     * @return Chart|null
     */
    public function getChartById(int $id)
    {
        $charts = $this->getCharts();
        foreach ($charts as $chart) {
            if ($chart->getChartId() === $id) {
                return $chart;
            }
        }
        return null;
    }

    /**
     * @return ChartBuilder
     */
    public function getChartBuilder()
    {
        return ChartBuilder::fromSheetId($this->sheetId);
    }

    /**
     * @param Chart $chart
     * @return Chart
     */
    public function addChart(Chart $chart)
    {
        // Make sure the anchor cell exists on the sheet.
        if ($this->getHeight() < $chart->getRow() + 1) {
            $this->appendRows($chart->getRow() + 1 - $this->getHeight());
        }
        if ($this->getWidth() < $chart->getColumn() + 1) {
            $this->appendColumns($chart->getColumn() + 1 - $this->getWidth());
        }

        $rawChart = $chart->getChart();
        $addChartRequest = new Google_Service_Sheets_AddChartRequest();
        $addChartRequest->setChart($rawChart);

        $batchResponse = $this->sendUpdateRequest(
            $addChartRequest,
            'setAddChart'
        );
        $response = $batchResponse->getReplies()[0]->getAddChart();
        return new Chart(
            $response->getChart()->getChartId(),
            $this->sheetId,
            $response->getChart()
        );
    }

    /**
     * @param Chart $chart
     * @return void
     */
    public function updateChart(Chart $chart)
    {
        $rawChart = $chart->getChart();

        $updateChartSpecRequest =
            new Google_Service_Sheets_UpdateChartSpecRequest();
        $updateChartSpecRequest->setChartId($chart->getChartId());
        $updateChartSpecRequest->setSpec($rawChart->getSpec());

        $this->sendUpdateRequest(
            $updateChartSpecRequest,
            'setUpdateChartSpec'
        );
    }

    /**
     * @return Google_Service_Sheets_BatchUpdateSpreadsheetResponse
     */
    public function showSheet()
    {
        return $this->setHidden(false);
    }

    /**
     * @return Google_Service_Sheets_BatchUpdateSpreadsheetResponse
     */
    public function hideSheet()
    {
        return $this->setHidden(true);
    }

    /**
     * @return bool
     */
    public function isHidden()
    {
        return $this->getProperties()->getHidden();
    }

    /**
     * Resizes the sheet to the smallest size containing the given range.
     * @param Rect $rect
     * @return void
     */
    public function cropSheet(Rect $rect)
    {
        $rowCount = $rect->getRow() + $rect->getHeight();
        $columnCount = $rect->getColumn() + $rect->getWidth();

        $gridProperties = new Google_Service_Sheets_GridProperties();
        $gridProperties->setRowCount($rowCount);
        $gridProperties->setColumnCount($columnCount);

        $sheetProperties = new Google_Service_Sheets_SheetProperties();
        $sheetProperties->setSheetId($this->sheetId);
        $sheetProperties->setGridProperties($gridProperties);

        $fields = 'gridProperties.rowCount,gridProperties.columnCount';

        $this->setSheetProperties($sheetProperties, $fields);

        $this->getGridProperties()->setRowCount($rowCount);
        $this->getGridProperties()->setColumnCount(
            $columnCount
        );
    }

    /**
     * @param bool $hidden
     * @return Google_Service_Sheets_BatchUpdateSpreadsheetResponse
     */
    private function setHidden($hidden)
    {
        $properties = new Google_Service_Sheets_SheetProperties();
        $properties->setSheetId($this->sheetId);
        $properties->setHidden($hidden);

        $result = $this->setSheetProperties($properties, 'hidden');

        // Update cached properties.
        $this->getProperties()->setHidden($hidden);

        return $result;
    }

    /**
     * @param Google_Service_Sheets_SheetProperties $properties
     * @param string $fields
     * @return Google_Service_Sheets_BatchUpdateSpreadsheetResponse
     */
    private function setSheetProperties(
        Google_Service_Sheets_SheetProperties $properties,
        $fields
    ) {
        $updateSheetPropertiesRequest = new
            Google_Service_Sheets_UpdateSheetPropertiesRequest();
        $updateSheetPropertiesRequest->setProperties($properties);
        $updateSheetPropertiesRequest->setFields($fields);

        return $this->sendUpdateRequest(
            $updateSheetPropertiesRequest,
            'setUpdateSheetProperties'
        );
    }

    /**
     * @param string $dimension Either "ROWS" or "COLUMNS"
     * @param int $count
     * @return Google_Service_Sheets_BatchUpdateSpreadsheetResponse
     * @throws Exception
     */
    private function appendDimension($dimension, $count)
    {
        if (('ROWS' !== $dimension) && ('COLUMNS' !== $dimension)) {
            throw new Exception('invalid dimension: ' . $dimension);
        }
        $appendDimensionRequest =
            new Google_Service_Sheets_AppendDimensionRequest();
        $appendDimensionRequest->setDimension($dimension);
        $appendDimensionRequest->setLength($count);
        $appendDimensionRequest->setSheetId($this->sheetId);

        return $this->sendUpdateRequest(
            $appendDimensionRequest,
            'setAppendDimension'
        );
    }

    /**
     * @param Rect $rect
     * @return string
     */
    private function getA1Range(Rect $rect)
    {
        return  "'" . $this->getName() . "'" . '!' . $rect->toA1();
    }

    /**
     * Fetch a refreshed version of this sheet
     * @return Sheet
     */
    public function refresh()
    {
        return $this->sheetsy->getSpreadsheetById($this->spreadsheetId)
            ->getSheetById($this->sheetId);
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
     * @return int
     */
    public function getSheetId()
    {
        return $this->sheetId;
    }

    /**
     * @return Google_Service_Sheets_Sheet
     */
    public function getSheet()
    {
        return $this->sheet;
    }
}
