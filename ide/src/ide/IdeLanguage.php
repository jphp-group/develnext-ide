<?php
namespace ide;

use framework\localization\Localizer;
use ide\l10n\L10n;
use ide\systems\IdeSystem;
use php\lib\fs;
use php\util\Configuration;

/**
 * Class IdeLanguage
 * @package ide
 */
class IdeLanguage
{
    private $code;
    private $directory;

    private $title;
    private $titleEn;

    private $restartMessage;

    private $altLang;

    function __construct($code, $directory)
    {
        $this->code = $code;

        $this->directory = $directory;

        $config = new Configuration("$directory/description.ini");
        $this->title = $config->get('name');
        $this->titleEn = $config->get('name.en');
        $this->altLang = $config->get('alt.lang');

        $this->restartMessage = $config->get('restart.message');
        $this->restartYes = $config->get('restart.yes');
        $this->restartNo = $config->get('restart.no');
    }

    /**
     * @return mixed
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * @return string
     */
    public function getAltLang()
    {
        return $this->altLang;
    }

    /**
     * @return mixed
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return mixed
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @return mixed
     */
    public function getTitleEn()
    {
        return $this->titleEn;
    }

    /**
     * @return string
     */
    public function getRestartMessage()
    {
        return $this->restartMessage;
    }

    /**
     * @return string
     */
    public function getRestartNo()
    {
        return $this->restartNo;
    }

    /**
     * @return string
     */
    public function getRestartYes()
    {
        return $this->restartYes;
    }

    /**
     * @return null|string
     */
    public function getIcon()
    {
        if (fs::isFile($file = "$this->directory/icon.png")) {
            return $file;
        }

        return null;
    }

    /**
     * @return null|string
     */
    public function getBigIcon()
    {
        if (fs::isFile($file = "$this->directory/icon32.png")) {
            return $file;
        }

        return null;
    }

    public function load(Localizer $localizer) {
        if (fs::isFile($file = "$this->directory/messages.ini")) {
            $localizer->load($this->code, $file);
        }
    }
}