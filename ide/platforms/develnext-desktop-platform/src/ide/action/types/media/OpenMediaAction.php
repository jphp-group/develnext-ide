<?php
namespace ide\action\types\media;

use action\Media;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;

class OpenMediaAction extends AbstractSimpleActionType
{
    function getHelpText()
    {
        return 'wizard.command.media.help.text::В качестве плеера вы можете указать символьный код или же выбрать среди компонентов модуля нужный плеер.';
    }

    function attributes()
    {
        return [
            'source' => 'string',
            'id'     => 'mixed',
            'autoplay' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'source' => 'wizard.audio.resource::Аудио ресурс',
            'autoplay' => 'wizard.autoplay.after.open::Воспроизвести после открытия',
            'id' => 'wizard.player::Плеер'
        ];
    }

    function attributeSettings()
    {
        return [
            'id' => ['def' => 'general', 'defType' => 'string'],
            'source' => ['editor' => 'audio'],
            'autoplay' => ['def' => true]
        ];
    }

    function getTagName()
    {
        return "openMedia";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.open.audio.resource::Открыть аудио ресурс";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.open.audio.resource::Открыть и воспроизвести аудио ресурс";
        }

        $source = $action->get('source');

        if ($action->autoplay) {
            return _("wizard.command.desc.param.open.audio.resource.autoplay::Открыть аудио ресурс {0} и воспроизвести, плеер {1}.", $source, $action->get('id'));
        } else {
            return _("wizard.command.desc.param.open.audio.resource::Открыть аудио ресурс {0}, плеер {1}.", $source, $action->get('id'));
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/audio16.png";
    }

    function imports(Action $action = null)
    {
        return [
            Media::class
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $id = $action->get('id');

        if ($id == "'general'" || $id == '"general"') {
            if ($action->autoplay) {
                return "Media::open({$action->get('source')})";
            } else {
                return "Media::open({$action->get('source')}, false)";
            }
        } else {
            if ($action->autoplay) {
                return "Media::open({$action->get('source')}, true, $id)";
            } else {
                return "Media::open({$action->get('source')}, false, $id)";
            }
        }
    }
}