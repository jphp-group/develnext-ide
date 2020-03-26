<?php
namespace ide\action\types;

use action\Element;
use ide\action\AbstractSimpleActionType;
use ide\action\Action;
use ide\action\ActionScript;
use php\gui\layout\UXVBox;
use php\gui\UXNode;
use php\gui\UXSeparator;
use php\lib\Str;

class CommentActionType extends AbstractSimpleActionType
{
    function attributes()
    {
        return [
            'text' => 'string'
        ];
    }

    function attributeLabels()
    {
        return [
            'text' => 'wizard.comment.text::Текст комментария'
        ];
    }

    function getSubGroup()
    {
        return 'decor';
    }

    function getTagName()
    {
        return 'comment';
    }

    function getTitle(Action $action = null)
    {
        return 'wizard.command.comment::Комментарий';
    }

    function getDescription(Action $action = null)
    {
        return "wizard.command.desc.comment::Текстовый однострочный комментарий";
    }

    function getIcon(Action $action = null)
    {
        return $action ? 'icons/arrowDot16.png' : 'icons/help16.png';
    }

    function makeUi(Action $action, UXNode $titleNode, UXNode $descriptionNode = null)
    {
        $descriptionNode->text = "// $action->text";
        $descriptionNode->style .= '; -fx-font-style: italic;';

        return $descriptionNode;
    }

    /**
     * @param Action $action
     * @param ActionScript $actionScript
     * @return string
     */
    function convertToCode(Action $action, ActionScript $actionScript)
    {
        return "// {$action->text}";
    }
}