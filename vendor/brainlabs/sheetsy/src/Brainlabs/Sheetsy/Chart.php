<?php
/**
 * @author ryutaro@brainlabsdigital.com
 */
namespace Brainlabs\Sheetsy;

use Google_Service_Sheets_EmbeddedChart;

/**
 * Represent a chart for plotting data.
 */
class Chart
{
    /** @var string|null $chartId */
    private $chartId;

    /** @var string $sheetId */
    private $sheetId;

    /** @var Google_Service_Sheets_EmbeddedChart $chart */
    private $chart;

    /**
     * @param string|null $chartId
     * @param string $sheetId
     * @param Google_Service_Sheets_EmbeddedChart $chart
     * @return void
     */
    public function __construct(
        $chartId,
        $sheetId,
        $chart
    ) {
        $this->chartId = $chartId;
        $this->sheetId = $sheetId;
        $this->chart = $chart;
    }

    /**
     * @return int
     */
    public function getRow()
    {
        return $this->chart->getPosition()->getOverlayPosition()
            ->getAnchorCell()->getRowIndex();
    }

    /**
     * @return int
     */
    public function getColumn()
    {
        return $this->chart->getPosition()->getOverlayPosition()
            ->getAnchorCell()->getColumnIndex();
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->chart->getSpec()->getTitle();
    }

    /**
     * @return string|null
     */
    public function getChartId()
    {
        return $this->chartId;
    }

    /**
     * @return string
     */
    public function getSheetId()
    {
        return $this->sheetId;
    }

    /**
     * @return Google_Service_Sheets_EmbeddedChart
     */
    public function getChart()
    {
        return $this->chart;
    }

    /**
     * @return ChartBuilder
     */
    public function modify()
    {
        return ChartBuilder::fromChart($this);
    }
}
