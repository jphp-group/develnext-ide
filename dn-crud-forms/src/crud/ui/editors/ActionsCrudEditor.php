<?php
namespace crud\ui\editors;

use crud\CrudException;
use php\gui\icons\UXFontAwesomeIcon;
use php\gui\layout\UXHBox;
use php\gui\UXButton;
use php\gui\UXNode;
use php\lib\str;

/**
 * Class ActionsCrudEditor
 * @package crud\ui\editors
 */
class ActionsCrudEditor extends AbstractCrudEditor
{
    public function makeUI(): UXNode
    {
        $box = new UXHBox([], 5);
        $box->fillHeight = true;

        $buttons = $this->getField()->args;

        if (!$buttons) {
            $buttons = [
                'save' => ['label' => 'Save', 'primary' => true, 'actions' => 'save'],
                'reset' => ['label' => 'Reset', 'actions' => 'load'],
                'cancel' => ['label' => 'Cancel', 'actions' => 'cancel']
            ];
        }

        foreach ($buttons as $code => $button) {
            $btnUi = new UXButton($this->getCrud()->t($button['label']));
            $btnUi->classes->add("action-$code");

            if ($button['primary']) {
                $btnUi->classes->add('primary');
                $btnUi->font->bold = true;
            }

            if ($button['icon']) {
                $btnUi->graphic = new UXFontAwesomeIcon($button['icon']);
            }

            $btnUi->on('click', function () use ($button) {
                foreach (flow($button['actions']) as $action) {
                    switch ($action) {
                        case "save":
                            $this->context->{'save'}();
                            $this->context->trigger($action);
                            break;
                        case "load":
                            $this->context->{'load'}();
                            $this->context->trigger($action);
                            break;
                        default:
                            $this->context->trigger($action);
                            break;
                            //throw new CrudException("Unknown action type: $action, button = " . str::formatAs($button, 'json'));
                    }
                }
            });
            $box->add($btnUi);
        }

        return $box;
    }

    public function setValueForUI(UXNode $editorUi, $value)
    {
    }

    public function getValueFromUI(UXNode $editorUi)
    {
    }

    public function isWithoutLabel(): bool
    {
        return true;
    }


}