<?php
namespace ide\forms;

use ide\commands\CreateScriptModuleProjectCommand;
use ide\forms\mixins\DialogFormMixin;
use ide\Ide;
use php\gui\UXButton;
use php\gui\UXCheckbox;
use php\gui\UXListCell;
use php\gui\UXListView;

/**
 *
 * @property UXListView $list
 * @property UXButton $saveButton
 * @property UXButton $cancelButton
 */
class ModuleListEditorForm extends AbstractIdeForm
{
    use DialogFormMixin;

    protected $checkboxes = [];

    protected function init()
    {
        $this->list->setCellFactory(function (UXListCell $cell, $item) {
            if ($item) {
                $cell->text = null;
                $cell->graphic = $item;
            }
        });
    }

    /**
     * @event show
     */
    public function actionOpen()
    {
        $project = Ide::get()->getOpenedProject();

        $this->list->items->clear();
        $this->checkboxes = [];

        $values = $this->getResult();

        foreach ($values as $value) {
            $values[$value] = $value;
        }

        if ($project) {
            $javafx = $project->findSupport('javafx');

            $classes = $javafx->getModuleClasses($project);

            foreach ($classes as $class) {
                if ($class == $javafx->getAppModuleClass($project)) {
                    continue;
                }

                $class = $javafx->getModuleShortClass($project, $class);

                $checkbox = new UXCheckbox($class);
                $checkbox->selected = $values[$class];

                $this->checkboxes[$class] = $checkbox;
                $this->list->items->add($checkbox);
            }
        }

        $this->list->requestFocus();
    }

    /**
     * @event saveButton.action
     */
    public function actionSave()
    {
        $modules = [];

        foreach ($this->checkboxes as $module => $checkbox) {
            if ($checkbox->selected) {
                $modules[] = $module;
            }
        }

        $this->setResult($modules);
        $this->hide();
    }

    /**
     * @event createModuleButton.action
     */
    public function actionCreateModuleButton()
    {
        $command = new CreateScriptModuleProjectCommand();
        $name = $command->onExecute();

        if (!$name) {
            return;
        }

        $modules = [];

        foreach ($this->checkboxes as $module => $checkbox) {
            if ($checkbox->selected) {
                $modules[] = $module;
            }
        }

        $modules[] = $name;

        $this->setResult($modules);
        $this->hide();
    }

    /**
     * @event cancelButton.action
     */
    public function actionCancel()
    {
        $this->setResult(null);
        $this->hide();
    }
}