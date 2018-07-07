<?php
namespace ide\action\types\media;

use action\Media;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\str;

class OpenFileMediaAction extends OpenMediaAction
{
    function getHelpText()
    {
        return 'wizard.command.open.file.media.help.text::Вы можете указать как относительный путь к аудио файлу, так и полный путь';
    }

    function attributeLabels()
    {
        return [
            'source' => 'wizard.audio.file.javafx.supports::Аудио файл (*.mp3, *.wav, *.wave, *.aif, *.aiff)',
            'autoplay' => 'wizard.autoplay.after.open::Воспроизвести после открытия',
            'id' => 'wizard.player::Плеер'
        ];
    }

    function attributeSettings()
    {
        return [
            'id' => ['def' => 'general', 'defType' => 'string'],
            'source' => ['editor' => 'string'],
            'autoplay' => ['def' => true]
        ];
    }

    function getTagName()
    {
        return "openFileMedia";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.open.file.media::Открыть аудио файл";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.open.file.media::Открыть и воспроизвести аудио файл";
        }

        $source = $action->get('source');

        if ($action->autoplay) {
            return _("wizard.command.desc.param.open.file.media.autoplay::Открыть аудио файл {0} и воспроизвести, плеер {1}.", $source, $action->get('id'));
        } else {
            return _("wizard.command.desc.param.open.file.media::Открыть аудио файл {0}, плеер {1}.", $source, $action->get('id'));
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/audioOpen16.png";
    }
}