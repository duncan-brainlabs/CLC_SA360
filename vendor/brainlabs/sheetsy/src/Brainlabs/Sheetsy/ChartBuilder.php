<?php
/**
 * @author ryutaro@brainlabsdigital.com
 */
namespace Brainlabs\Sheetsy;

use Google_Service_Sheets_BasicChartAxis;
use Google_Service_Sheets_BasicChartDomain;
use Google_Service_Sheets_BasicChartSeries;
use Google_Service_Sheets_BasicChartSpec;
use Google_Service_Sheets_ChartData;
use Google_Service_Sheets_ChartSourceRange;
use Google_Service_Sheets_ChartSpec;
use Google_Service_Sheets_EmbeddedChart;
use Google_Service_Sheets_EmbeddedObjectPosition;
use Google_Service_Sheets_GridCoordinate;
use Google_Service_Sheets_GridRange;
use Google_Service_Sheets_OverlayPosition;

/**
 * Build charts. This wrapper does not support pie charts.
 */
class ChartBuilder
{
    /**
     * @var string $sheetId;
     */
    private $sheetId;

    /**
     * @var Google_Service_Sheets_EmbeddedChart $chart
     */
    private $chart;

    /**
     * @var Google_Service_Sheets_BasicChartSeries[] $series
     */
    private $series;

    /**
     * @var Google_Service_Sheets_BasicChartAxis[] $axes
     */
    private $axes;

    /**
     * @param string $sheetId
     * @param Google_Service_Sheets_EmbeddedChart $chart
     * @param Google_Service_Sheets_BasicChartSeries[] $series
     * @param Google_Service_Sheets_BasicChartAxis[] $axes
     */
    public function __construct(
        $sheetId,
        $chart,
        $series,
        $axes
    ) {
        $this->sheetId = $sheetId;
        $this->chart = $chart;
        $this->series = $series;
        $this->axes = $axes;
    }

    /**
     * @param string $sheetId
     * @return ChartBuilder
     */
    public static function fromSheetId(string $sheetId)
    {
        $basicChartSpec = new Google_Service_Sheets_BasicChartSpec();

        $chartSpec = new Google_Service_Sheets_ChartSpec();
        $chartSpec->setBasicChart($basicChartSpec);

        $chart = new Google_Service_Sheets_EmbeddedChart();
        $chart->setSpec($chartSpec);

        return new ChartBuilder(
            $sheetId,
            $chart,
            [],
            []
        );
    }

    /**
     * @param Chart $chart
     * @return ChartBuilder
     */
    public static function fromChart(Chart $chart)
    {
        $rawChart = $chart->getChart();
        $basicChartSpec = $rawChart->getSpec()->getBasicChart();

        return new ChartBuilder(
            $chart->getSheetId(),
            $rawChart,
            $basicChartSpec->getSeries(),
            $basicChartSpec->getAxis()
        );
    }

    /**
     * @param string $title
     * @return ChartBuilder
     */
    public function setTitle($title)
    {
        $this->chart->getSpec()->setTitle($title);
        return $this;
    }

    /**
     * @param int $rowNum
     * @param int $columnNum
     * @param Rect $rect The dimensions of the chart, in pixels
     * @return ChartBuilder
     */
    public function setPosition($rowNum, $columnNum, Rect $rect)
    {
        $coordinate = new Google_Service_Sheets_GridCoordinate();
        $coordinate->setSheetId($this->sheetId);
        $coordinate->setRowIndex($rowNum);
        $coordinate->setColumnIndex($columnNum);

        $overlayPosition = new Google_Service_Sheets_OverlayPosition();
        $overlayPosition->setAnchorCell($coordinate);
        $overlayPosition->setOffsetXPixels($rect->getRow());
        $overlayPosition->setOffsetYPixels($rect->getColumn());
        $overlayPosition->setHeightPixels($rect->getHeight());
        $overlayPosition->setWidthPixels($rect->getWidth());

        $position = new Google_Service_Sheets_EmbeddedObjectPosition();
        $position->setOverlayPosition($overlayPosition);

        $this->chart->setPosition($position);
        return $this;
    }

    /**
     * This wrapper supports up to one domain
     * @param string $sheetId
     * @param Rect $rect
     * @param bool $reversed
     * @return ChartBuilder
     */
    public function setDomain($sheetId, Rect $rect, bool $reversed = false)
    {
        $data = self::buildChartData($sheetId, $rect);

        $domain = new Google_Service_Sheets_BasicChartDomain();
        $domain->setDomain($data);
        $domain->setReversed($reversed);
        $this->chart->getSpec()->getBasicChart()->setDomains([$domain]);
        return $this;
    }

    /**
     * @param string $legendPosition See ChartLegend
     * @return ChartBuilder
     */
    public function setLegendPosition($legendPosition)
    {
        $this->chart->getSpec()->getBasicChart()
            ->setLegendPosition($legendPosition);
        return $this;
    }

    /**
     * @param string $sheetId
     * @param Rect $rect
     * @param string $axis See ChartAxis
     * @param Color|null $color
     * @return ChartBuilder
     */
    public function addSeries($sheetId, Rect $rect, $axis, Color $color = null)
    {
        $data = self::buildChartData($sheetId, $rect);

        $series = new Google_Service_Sheets_BasicChartSeries();
        $series->setSeries($data);
        $series->setTargetAxis($axis);

        if (!is_null($color)) {
            $series->setColor($color->unwrap());
        }

        $this->series[] = $series;
        return $this;
    }

    /**
     * @return ChartBuilder
     */
    public function clearSeries()
    {
        $this->series = [];
        return $this;
    }

    /**
     * @param string $name
     * @param string $position See ChartAxis
     * @return ChartBuilder
     */
    public function addAxis($name, $position)
    {
        $axis = new Google_Service_Sheets_BasicChartAxis();
        $axis->setTitle($name);
        $axis->setPosition($position);
        $this->axes[] = $axis;
        return $this;
    }

    /**
     * @return ChartBuilder
     */
    public function clearAxes()
    {
        $this->axes = [];
        return $this;
    }

    /**
     * @param string $chartType
     * @return ChartBuilder
     */
    public function setChartType($chartType)
    {
        $this->chart->getSpec()->getBasicChart()->setChartType($chartType);
        return $this;
    }

    /**
     * @return Chart
     */
    public function build()
    {
        $this->chart->getSpec()->getBasicChart()->setSeries($this->series);
        $this->chart->getSpec()->getBasicChart()->setAxis($this->axes);

        // will be null for a new chart
        $chartId = $this->chart->getChartId();

        return new Chart($chartId, $this->sheetId, $this->chart);
    }

    /**
     * @param string $sheetId
     * @param Rect $rect
     * @return Google_Service_Sheets_ChartData
     */
    private static function buildChartData($sheetId, Rect $rect)
    {
        if ((1 !== $rect->getHeight()) && (1 !== $rect->getWidth())) {
            throw new \InvalidArgumentException('height or width must be 1');
        }

        $range = new Google_Service_Sheets_GridRange();
        $range->setSheetId($sheetId);
        $range->setStartRowIndex($rect->getRow());
        // The end index is excluded from the range
        $range->setEndRowIndex($rect->getRow() + $rect->getHeight());
        $range->setStartColumnIndex($rect->getColumn());
        $range->setEndColumnIndex($rect->getColumn() + $rect->getWidth());

        $source = new Google_Service_Sheets_ChartSourceRange();
        $source->setSources([$range]);

        $data = new Google_Service_Sheets_ChartData();
        $data->setSourceRange($source);
        return $data;
    }
}
