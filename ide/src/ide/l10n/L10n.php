<?php
namespace ide\l10n;

use framework\localization\Localizer;
use ide\Logger;
use php\gui\framework\DataUtils;
use php\gui\UXLabeled;
use php\gui\UXMenu;
use php\gui\UXMenuBar;
use php\gui\UXMenuItem;
use php\gui\UXNode;
use php\gui\UXParent;
use php\gui\UXTabPane;
use php\gui\UXTextInputControl;
use php\io\IOException;
use php\lib\str;
use php\util\Configuration;
use php\util\Regex;

/**
 * Class L10n
 * @package ide\l10n
 */
class L10n
{
    /**
     * @var Localizer
     */
    private $localizer;

    /**
     * L10n constructor.
     */
    public function __construct(Localizer $localizer)
    {
        $this->localizer = $localizer;
    }

    /**
     * @return mixed
     */
    public function getLanguage()
    {
        return $this->localizer->language;
    }

    /**
     * @param mixed $language
     */
    public function setLanguage($language)
    {
        $this->localizer->language = $language;
    }



}