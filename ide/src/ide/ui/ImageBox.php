<?php
namespace ide\ui;
use ide\ui\elements\DNLabel;
use ide\utils\UiUtils;
use php\gui\UXImageArea;
use php\gui\layout\UXVBox;
use php\gui\UXLabelEx;
use php\gui\UXImage;
use behaviour\StreamLoadableBehaviour;
use php\io\Stream;

/**
 * Class ImageBox
 * @package ide\ui
 */
class ImageBox extends UXVBox implements StreamLoadableBehaviour
{
    /**
     * @var UXImageArea
     */
    protected $imageArea;

    /**
     * @var UXLabelEx
     */
    protected $titleLabel;

    protected $boxBackgroundColor = "transparent";

    /**
     * ImageBox constructor.
     * @param int $width
     * @param int $height
     */
    public function __construct($width, $height)
    {
        parent::__construct();

        $this->alignment = 'TOP_CENTER';

        $item = new UXImageArea();
        $item->size = [$width, $height];

        $item->centered = true;
        $item->stretch = true;
        $item->smartStretch = true;
        $item->proportional = true;

        $this->add($item);
        $this->imageArea = $item;

        $nameLabel = new DNLabel();
        $nameLabel->textAlignment = 'CENTER';
        $nameLabel->alignment = 'TOP_CENTER';
        $nameLabel->paddingTop = 5;
        $nameLabel->width = $item->width;

        $this->classes->addListener(function () {
            $this->backgroundColor = $this->boxBackgroundColor = $this->classes->has("selected") ? "#00000020" : "transparent";
        });

        $this->on("mouseEnter", function () {
            $this->backgroundColor = "#00000020";
        });

        $this->on("mouseExit", function () {
            $this->backgroundColor = $this->boxBackgroundColor;
        });

        $this->add($nameLabel);
        $this->titleLabel = $nameLabel;
        $this->padding = 12;
    }

    public function setImage(UXImage $image = null)
    {
        $this->imageArea->image = $image;
    }

    public function getImage()
    {
        return $this->imageArea->image;
    }

    public function setTitle($title, $style = '')
    {
        $this->titleLabel->text = $title;
        $this->titleLabel->style .= $style;
        $this->titleLabel->tooltipText = $title;
    }

    public function getTitle()
    {
        return $this->titleLabel->text;
    }

    /**
     * @param $path
     * @return mixed
     */
    function loadContentForObject($path)
    {
        return new UXImage(Stream::of($path));
    }

    /**
     * @param $content
     * @return mixed
     */
    function applyContentToObject($content)
    {
        $this->imageArea->image = $content;
    }

    /**
     * @param string $tooltip
     */
    public function setTooltip($tooltip)
    {
        $this->titleLabel->tooltipText = $tooltip;
    }
}