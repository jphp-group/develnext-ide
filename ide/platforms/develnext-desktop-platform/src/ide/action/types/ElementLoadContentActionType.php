<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\io\Stream;
use php\lib\Items;
use php\lib\Str;

class ElementLoadContentActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'object' => 'object',
            'path' => 'string',
            'sync' => 'flag',
        ];
    }

    function attributeLabels()
    {
        return [
            'object' => 'wizard.object::Объект',
            'path' => 'wizard.path.source::Источник (файл, url, и т.д.)',
            'sync' => 'wizard.in.main.thread::В главном потоке'
        ];
    }

    function attributeSettings()
    {
        return [
            'object' => ['def' => '~sender'],
        ];
    }

    function getGroup()
    {
        return 'ui';
    }

    function getSubGroup()
    {
        return 'object';
    }

    function getTagName()
    {
        return "elementLoadContent";
    }

    function getTitle(Action $action = null)
    {
        return "wizard.command.load.content::Загрузить контент";
    }

    function getDescription(Action $action = null)
    {
        if (!$action) {
            return "wizard.command.desc.load.content::Загрузить контент в объект из источника (файла, url и т.д.)";
        }

        if ($action->sync) {
            return _("wizard.command.desc.param.load.content.in.main.thread::Загрузить контент в объект {0} из {1} в главном потоке.", $action->get('object'), $action->get('path'));
        } else {
            return _("wizard.command.desc.param.load.content::Загрузить контент в объект {0} из {1}.", $action->get('object'), $action->get('path'));
        }
    }

    function getIcon(Action $action = null)
    {
        return "icons/download16.png";
    }

    function isYield(Action $action)
    {
        return !$action->sync && $action->getFieldType('object') != 'variable';
    }

    function imports(Action $action = null)
    {
        return [
            Element::class,
            Stream::class,
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        if ($action->sync) {
            switch ($action->getFieldType('object')) {
                case 'variable':
                    $actionScript->addLocalVariable($action->get('object'));
                    return "{$action->get('object')} = Stream::getContents({$action->get('path')})";
                default:
                    return "Element::loadContent({$action->get('object')}, {$action->get('path')})";
            }
        } else {

            switch ($action->getFieldType('object')) {
                case 'variable':
                    $actionScript->addLocalVariable($action->get('object'));
                    return "{$action->get('object')} = Stream::getContents({$action->get('path')})";
                default:
                    return "Element::loadContentAsync({$action->get('object')}, {$action->get('path')},";
            }
        }
    }
}