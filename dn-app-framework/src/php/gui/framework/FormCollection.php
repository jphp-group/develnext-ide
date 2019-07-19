<?php
namespace php\gui\framework;

use php\framework\Logger;
use php\gui\framework\AbstractForm;

/**
 * Здесь будут собраны все созданные в приложении формы и ссылки на них
 * @package php\gui\framework
 *
 * @packages framework
 */
class FormCollection {
    /**
     * @var AbstractForm[]
     */
    protected static $forms = [];

    /**
     * @var callable[]
     */
    protected static $callbacks = [];

    public static function addForm(AbstractForm $form){
        self::$forms[$form->getName()] = $form;
        Logger::debug("Form '{$form->getName()}' added to collection");

        foreach (self::$callbacks as $callback) {
            call_user_func($callback, $form);
        }
    }

    public static function getForms(): array {
        return self::$forms;
    }

    public static function onAddEvent(callable $callback){
        self::$callbacks[] = $callback;

        foreach (self::$forms as $form) {
            call_user_func($callback, $form);
        }
    }
}