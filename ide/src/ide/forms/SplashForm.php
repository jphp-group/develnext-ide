<?php
namespace ide\forms;

use ide\Ide;
use ide\Logger;
use ide\systems\SplashTipSystem;
use php\gui\effect\UXColorAdjustEffect;
use php\gui\effect\UXSepiaToneEffect;
use php\gui\event\UXEvent;
use php\gui\framework\AbstractForm;
use php\gui\layout\UXAnchorPane;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\UXApplication;
use php\gui\UXImage;
use php\gui\UXImageArea;
use php\gui\UXImageView;
use php\gui\UXLabel;
use php\io\IOException;
use php\io\Stream;
use php\lang\Thread;
use php\lang\ThreadPool;
use php\lib\str;
use php\time\Time;

/**
 * @property UXLabel $version
 * @property UXImageView $image
 * @property UXLabel $accountNameLabel
 * @property UXAnchorPane $accountAvatarImage
 * @property UXHBox $accountPane
 * @property UXLabel $tip
 * @property UXHBox $tipBox
 */
class SplashForm extends AbstractIdeForm
{
    protected function init()
    {
        Logger::debug("Init form ...");

        $this->centerOnScreen();

        $versionCode = $this->_app->getConfig()->get('app.versionCode');
        $this->version->text = $this->_app->getVersion();

        // Конечно, эфекты это прикольно, но нужно ли это?
        if ($this->_app->isSnapshotVersion()) {
            $effect = new UXSepiaToneEffect();
            $effect->level = 0.5;
            $this->image->effects->add($effect);

            $effect2 = new UXColorAdjustEffect();
            $effect2->saturation = -1;
            $this->tipBox->effects->add($effect2);
        }

        if ($versionCode) {
            $this->versionCode->text = str::upperFirst($versionCode);

            $now = Time::now()->toString('dd-MM');
            $specialCode = 'res://.data/img/code/special/' . $now . '.png';

            if (Stream::exists($specialCode)) {
                $versionCode = "special/$now";
            }

            $codeImg = new UXImageArea(new UXImage('res://.data/img/code/' . $versionCode . '.png'));
            $codeImg->stretch = true;
            $codeImg->smartStretch = true;
            $codeImg->size = [64, 64];
            $codeImg->position = [690 - 64 - 14, 14];

            $this->add($codeImg);
        }

        $name = Ide::get()->getUserConfigValue('splash.name');
        $avatar = Ide::get()->getUserConfigValue('splash.avatar');

        if (!$name) {
            $this->accountPane->hide();
        } else {
            $this->accountNameLabel->text = $name;

            $image = new UXImageArea(Ide::get()->getImage('noAvatar.jpg')->image);
            $image->stretch = true;
            $image->smartStretch = true;
            $image->centered = true;
            $image->proportional = true;
            UXAnchorPane::setAnchor($image, 0);

            $this->accountAvatarImage->add($image);

            if ($avatar) {
                try {
                    $image->image = new UXImage(Stream::of($avatar));
                } catch (IOException $e) {
                    Logger::error("Unable to load splash account image, {$e->getMessage()}");
                }
            }
        }

        Ide::get()->on('start', function () {
            Ide::accountManager()->on('update', function ($data) {
                Ide::service()->file()->getImageAsync($data['avatar'], function ($file) {
                    Ide::get()->setUserConfigValue('splash.avatar', $file);
                });

                Ide::get()->setUserConfigValue('splash.name', $data['name']);
            }, __CLASS__);
            Ide::accountManager()->updateAccount();
        }, __CLASS__);

        $this->waiter();
    }

    /**
     * Функция ждёт, когда отрисуется окно среды, и скрывает сплеш
     * Надо ли это вообще?
     */
    public function waiter(int $timeoutMs = 15000){
        waitAsync($timeoutMs, function() use ($timeoutMs){
            if (is_object($this->_app->getMainForm()) && $this->_app->getMainForm()->visible) {
                $this->hide();
            } elseif($this->visible) {
                $this->waiter($timeoutMs);
            }
        });
    }

    /**
     * @param UXEvent $e
     * @event tip.click
     */
    public function doTipClick(UXEvent $e)
    {
        $this->tip->text = SplashTipSystem::get(Ide::get()->getLanguage()->getCode());
        $e->consume();
    }

    /**
     * @event show
     */
    public function doShow()
    {
        $this->tip->text = SplashTipSystem::get(Ide::get()->getLanguage()->getCode());
        // Я хочу увидеть, как это выглядит в продакшне
        /*if (Ide::get()->isDevelopment() && Ide::get()->isWindows()) {
            if ($this->opacity > 0.9) {
                $this->opacity = 0.05;
            } else {
                $this->opacity = 1;
            }
        }*/

        //uiLater(function () { // мы же и так в ui, разве нет?
        $this->toFront();
        $this->requestFocus();
        //});
    }

    /**
     * @event click
     */
    public function hide()
    {
        parent::hide();
    }
}