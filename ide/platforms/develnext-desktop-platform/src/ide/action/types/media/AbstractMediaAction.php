<?php
namespace ide\action\types\media;

use action\Media;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\lib\str;

abstract class AbstractMediaAction extends AbstractSimpleActionType
{
    abstract public function getMediaMethod();

    function getHelpText()
    {
        return 'wizard.command.media.help.text::В качестве плеера вы можете указать символьный код или же выбрать среди компонентов модуля нужный плеер.';
    }

    function attributes()
    {
        return [
            'id' => 'mixed',
        ];
    }

    function attributeLabels()
    {
        return [
            'id' => 'wizard.player::Плеер'
        ];
    }

    function attributeSettings()
    {
        return [
            'id' => ['def' => 'general', 'defType' => 'string'],
        ];
    }

    function getGroup()
    {
        return 'media';
    }

    function getSubGroup()
    {
        return 'audio';
    }

    function getTagName()
    {
        return $this->getMediaMethod() . "Media";
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
        $method = $this->getMediaMethod();

        if ($id == "'general'" || $id == '"general"') {
            return "Media::$method()";
        } else {
            return "Media::$method($id)";
        }
    }
}