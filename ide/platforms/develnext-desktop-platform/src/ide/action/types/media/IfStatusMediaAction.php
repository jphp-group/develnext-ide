<?php
namespace ide\action\types\media;


use ide\action\Action;
use ide\action\ActionScript;
use ide\editors\argument\MethodsArgumentEditor;
use php\lib\str;

class IfStatusMediaAction extends AbstractMediaAction
{
    function attributes()
    {
        return [
            'id'     => 'mixed',
            'status' => 'string',
        ];
    }

    function attributeLabels()
    {
        return [
            'id' => 'wizard.player::Плеер',
            'status' => 'wizard.status.of.player::Статус плеера'
        ];
    }

    function attributeSettings()
    {
        return [
            'status' => ['def' => 'PLAYING', 'editor' => function () {
                $editor = new MethodsArgumentEditor([
                    'UNKNOWN' => 'wizard.player.status.unknown::Неизвестно [UNKNOWN]',
                    'READY' => 'wizard.player.status.ready::Подготовлен [READY]',
                    'PLAYING' => 'wizard.player.status.playing::Играет [PLAYING]',
                    'PAUSED' => 'wizard.player.status.paused::На паузе [PAUSED]',
                    'STOPPED' => 'wizard.player.status.stopped::Остановлен [STOPPED]',
                    'HALTED' => 'wizard.player.status.halted::Возникла ошибка [HALTED]',
                ]);
                return $editor;
            }],
            'id' => ['def' => 'general', 'defType' => 'string'],
        ];
    }


    function getTitle(Action $action = null)
    {
        return !$action ?
            "wizard.command.if.player::Если плеер" : _("wizard.command.param.if.player::Если плеер {0} ", $action->get('id'));
    }

    function getDescription(Action $action = null)
    {
        return !$action ?
            "wizard.command.desc.if.player::Если статус плеера"
            : _("wizard.command.desc.param.if.player::Если статус плеера {0} = {1}.", $action->get('id'), $action->get('status'));
    }

    function getIcon(Action $action = null)
    {
        return "icons/audioIf16.png";
    }

    public function getMediaMethod()
    {
        return "ifStatus";
    }

    function isAppendSingleLevel()
    {
        return true;
    }

    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $id = $action->get('id');
        $status = $action->get('status');

        if ($id == "'general'" || $id == '"general"') {
            return "if (Media::isStatus($status))";
        } else {
            return "if (Media::isStatus($status, $id)";
        }
    }
}