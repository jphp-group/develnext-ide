<?php
namespace ide\project\control;
use ide\commands\CreateFormProjectCommand;
use ide\editors\AbstractEditor;
use ide\editors\common\FormListEditor;
use ide\editors\FormEditor;
use ide\Ide;
use ide\project\Project;
use ide\ui\elements\DNLabel;
use ide\ui\elements\DNSeparator;
use ide\ui\FlowListViewDecorator;
use ide\ui\ImageBox;
use php\gui\layout\UXHBox;
use php\gui\layout\UXVBox;
use php\gui\UXLabel;
use php\gui\UXNode;

/**
 * @package ide\project\control
 */
class FormsProjectControlPane extends AbstractEditorsProjectControlPane
{
    /**
     * @var FlowListViewDecorator
     */
    protected $list;

    /**
     * @var FormListEditor
     */
    protected $settingsMainFormCombobox;

    public function getName()
    {
        return "ui.forms::Формы";
    }

    public function getDescription()
    {
        return "ui.forms.and.windows::Формы и окна";
    }

    public function getIcon()
    {
        return 'icons/windows16.png';
    }

    /**
     * @param FormEditor $item
     * @return mixed
     */
    protected function getBigIcon($item)
    {
        return 'icons/window32.png';
    }

    /**
     * @return AbstractEditor[]
     * @throws \ide\IdeException
     * @throws \Exception
     */
    protected function getItems()
    {
        $javafx = Project::findSupportOfCurrent('javafx');

        return $javafx ? $javafx->getFormEditors(Ide::project()) : [];
    }

    /**
     * @return mixed
     */
    protected function doAdd()
    {
        $command = new CreateFormProjectCommand();
        $command->onExecute();
    }

    /**
     * @param FormEditor $item
     * @return UXNode
     * @throws \Exception
     */
    protected function makeItemUi($item)
    {
        /** @var ImageBox $box */
        $box = parent::makeItemUi($item);

        $javafx = Project::findSupportOfCurrent('javafx');

        if ($javafx && $javafx->isMainForm(Ide::project(), $item)) {
            $box->setTitle($box->getTitle(), '-fx-font-weight: bold;');
        }

        return $box;
    }


    protected function makeAdditionalUi()
    {
        $formListEditor = new FormListEditor();
        $formListEditor->setEmptyItemText('ui.form.list.no::[Нет]');
        $formListEditor->build();

        $formListEditor->onChange(function ($value) {
            $javafx = Project::findSupportOfCurrent('javafx');

            if ($javafx) {
                $javafx->setMainForm(Ide::project(), $value);
            }

            $this->refresh(false);
        });

        $formListEditor->getUi()->width = 250;
        $this->settingsMainFormCombobox = $formListEditor;

        $label = _(new DNLabel('ui.form.show.on.start'));
        $label->opacity = 0.75;

        $box = new UXHBox([$formListEditor->getUi(), $label], 5);
        $box->alignment = 'CENTER_LEFT';

        $ui = new UXVBox([
            _(new DNLabel('{ui.main.form::Главная форма}:')),
            $box,
        ]);

        $ui->spacing = 3;
        $ui->alignment = 'CENTER_LEFT';

        return [new UXVBox([ $ui, new DNSeparator() ], 8)];
    }

    public function refresh($updateUi = true)
    {
        parent::refresh();

        if ($updateUi && $this->settingsMainFormCombobox) {
            $javafx = Project::findSupportOfCurrent('javafx');

            $project = Ide::project();

            if ($javafx) {
                $mainForm = $javafx->getMainForm($project);
            }

            $this->settingsMainFormCombobox->updateUi();

            if ($javafx) {
                $javafx->setMainForm($project, $mainForm);
                $this->settingsMainFormCombobox->setSelected($mainForm);
            }
        }
    }
}