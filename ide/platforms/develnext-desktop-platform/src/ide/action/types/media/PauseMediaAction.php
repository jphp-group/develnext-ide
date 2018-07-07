<?php
namespace ide\action\types\media;


use ide\action\Action;
use php\lib\str;

class PauseMediaAction extends AbstractMediaAction
{
    function getTitle(Action $action = null)
    {
        return !$action ? "wizard.command.pause.media::Пауза" : _("wizard.command.param.pause.media::Пауза, плеер = {0}.", $action->get('id'));
    }

    function getDescription(Action $action = null)
    {
        return !$action
            ? "wizard.command.desc.pause.media::Поставить на паузу (pause)"
            : _("wizard.command.desc.param.pause.media::Поставить на паузу (pause), плеер = {0}.", $action->get('id'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/audioPause16.png";
    }

    public function getMediaMethod()
    {
        return "pause";
    }
}