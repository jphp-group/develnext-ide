<?php
namespace ide\action\types\media;


use ide\action\Action;
use php\lib\str;

class PlayMediaAction extends AbstractMediaAction
{
    function getTitle(Action $action = null)
    {
        return !$action
            ? "wizard.command.play.media::Воспроизвести"
            : _("wizard.command.param.play.media::Воспроизвести, плеер = {0}.", $action->get('id'));
    }

    function getDescription(Action $action = null)
    {
        return !$action
            ? "wizard.command.desc.play.media::Воспроизвести (play)"
            : _("wizard.command.desc.param.play.media::Воспроизвести (play), плеер = {0}.", $action->get('id'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/audioPlay16.png";
    }

    public function getMediaMethod()
    {
        return "play";
    }
}