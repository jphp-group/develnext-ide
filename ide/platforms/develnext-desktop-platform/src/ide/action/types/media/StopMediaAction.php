<?php
namespace ide\action\types\media;


use ide\action\Action;
use php\lib\str;

class StopMediaAction extends AbstractMediaAction
{
    function getTitle(Action $action = null)
    {
        return !$action
            ? "wizard.command.stop.media::Стоп"
            : _("wizard.command.param.stop.media::Стоп, плеер = {0}.", $action->get('id'));
    }

    function getDescription(Action $action = null)
    {
        return !$action
            ? "wizard.command.desc.stop.media::Полностью остановить воспроизведение (stop)"
            : _("wizard.command.desc.param.stop.media::Остановить воспроизведение (stop), плеер = {0}.", $action->get('id'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/audioStop16.png";
    }

    public function getMediaMethod()
    {
        return "stop";
    }
}