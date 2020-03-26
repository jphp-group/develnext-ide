<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use ide\editors\argument\EnumArgumentEditor;
use ide\editors\common\ObjectListEditorItem;
use php\gui\UXDialog;
use php\lib\Str;
use function var_dump;

class MessageActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'value' => 'string',
            'kind'  => 'string',
            'wait'  => 'flag'
        ];
    }

    function attributeLabels()
    {
        return [
            'value' => 'wizard.text.of.message::Текст сообщения',
            'kind'  => 'wizard.type.of.message::Тип сообщения',
            'wait'  => 'wizard.wait.closure::Ожидать закрытия'
        ];
    }

    function attributeSettings()
    {
        return [
            'kind' => [
                'editor' => function ($name, $label) {
                    return new EnumArgumentEditor([
                        new ObjectListEditorItem('wizard.information::Информация', ico('information16'), 'INFORMATION'),
                        new ObjectListEditorItem('wizard.warning::Предупреждение', ico('warning16'), 'WARNING'),
                        new ObjectListEditorItem('wizard.confirmation::Вопрос', ico('confirm16'), 'CONFIRMATION'),
                        new ObjectListEditorItem('wizard.error::Ошибка', ico('error16'), 'ERROR')
                    ]);
                }
            ]
        ];
    }

    function getGroup()
    {
        return 'ui-forms';
    }

    function getTagName()
    {
        return 'message';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.show.message::Показать сообщение';
    }

    function getDescription(Action $action = null)
    {
        $text = $action ? $action->get('value') : "";

        if ($text >= 40) {
            $text = Str::sub($text, 0, 37) . '..';
        }

        $result = _("wizard.command.desc.param.show.message::Открыть текстовый диалог с сообщением {0}, тип = {1}.", $text, $action ? $action->get('kind') : '?');

        return $result;
    }

    function getIcon(Action $action = null)
    {
        return 'icons/chat16.png';
    }

    function imports(Action $action = null)
    {
        return [
            UXDialog::class,
        ];
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        $value = $action->get('value');

        $method = $action->wait ? 'showAndWait' : 'show';

        switch ($action->kind) {
            case '':
            case 'INFORMATION':
                return "UXDialog::$method({$value})";
        }

        return "UXDialog::$method({$value}, '{$action->kind}')";
    }
}