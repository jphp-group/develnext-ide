<?php
namespace ide;

use framework\core\Event;
use framework\localization\Localizer;
use ide\l10n\L10n;
use ide\misc\FileWatcher;
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

    private $beta;

    /**
     * @var array
     */
    private $sources = [];

    /**
     * @var Localizer
     */
    private $localizer;

    function __construct(Localizer $localizer, $code, $directory)
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
        $this->beta = (bool) $config->get('beta');

        $this->localizer = $localizer;

        $this->addSource("$directory/messages.ini");
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

    public function addSource(string $source)
    {
        // Зачем постоянно следить за языковыми файлами?
        // При разработке - ок, но не в продакшне
        /*$this->sources[$source] = $fw = new FileWatcher($source);
        $fw->start();
        $fw->on('change', function (Event $event) use ($source) {
            $newTime = $event->data['newTime'];
            $oldTime = $event->data['oldTime'];

            if ($newTime > 0) {
                uiLater(function () use ($source) {
                    $this->localizer->load($this->code, $source);
                });
            }
        });*/

        if (fs::isFile($source)) {
            $this->localizer->load($this->code, $source);
        }
    }

    public function isBeta(): bool
    {
        return $this->beta;
    }
}