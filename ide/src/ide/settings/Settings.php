<?php

namespace ide\settings;

use ide\forms\SettingsForm;
use ide\settings\ui\AbstractSettingsGroup;
use ide\settings\ui\AbstractSettingsItem;

class Settings
{
    /**
     * @var AbstractSettingsGroup[]
     */
    private $groups;

    /**
     * @var SettingsForm
     */
    private $form;

    /**
     * @param AbstractSettingsGroup $group
     */
    public function registerSettingGroup(AbstractSettingsGroup $group) {
        $this->groups[$group->getName()] = $group;
    }

    /**
     * @return AbstractSettingsGroup[]
     */
    public function getSettingGroups(): ?array {
        return $this->groups;
    }

    /**
     * @param AbstractSettingsItem|AbstractSettingsGroup $item
     * @throws \Exception
     */
    public function open($item) {
        $this->getForm()->open($item);
        $this->openForm();
    }

    /**
     * @throws \Exception
     */
    public function hide() {
        $this->getForm()->hide();
    }

    /**
     * @return SettingsForm
     * @throws \Exception
     */
    public function getForm(): SettingsForm
    {
        if (!$this->form)
            $this->form = new SettingsForm();

        return $this->form;
    }

    /**
     * @throws \Exception
     */
    public function openForm() {
        if (!$this->getForm()->visible) {
            $this->getForm()->show();
            $this->getForm()->size = [700, 500];
        } else {
            $this->getForm()->requestFocus();
        }
    }
}