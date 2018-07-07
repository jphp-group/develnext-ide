<?php
namespace ide\action\types\media;

use action\Media;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\str;

class OpenUrlMediaAction extends OpenMediaAction
{
    function getHelpText()
    {
        return 'В качестве плеера вы можете указать символьный код или же выбрать среди компонентов модуля нужный плеер.';
    }

    function attributeLabels()
    {
        return [
            'source' => 'wizard.audio.file.link::Ссылка на аудио-файл (http, https, ftp)',
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
        return "openUrlMedia";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.open.audio.link::Открыть аудио ссылку";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.open.audio.link::Открыть и воспроизвести аудио ссылку";
        }

        $source = $action->get('source');

        if ($action->autoplay) {
            return _("wizard.command.desc.param.open.audio.link.autoplay::Открыть аудио ссылку {0} и воспроизвести, плеер {1}.", $source, $action->get('id'));
        } else {
            return _("wizard.command.desc.param.open.audio.link::Открыть аудио ссылку {0}, плеер {1}.", $source, $action->get('id'));
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/audioUrl16.png";
    }
}