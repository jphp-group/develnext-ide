<?php

use crud\CrudEntity;
use crud\CrudForm;
use crud\Cruds;
use crud\ui\CrudUI;
use php\gui\framework\Application;
use php\gui\layout\UXAnchorPane;
use php\gui\UXApplication;
use php\gui\UXForm;
use php\lib\fs;
use php\time\Timer;

$app = new Application();
$app->launch(function () {
    $crudForm = new CrudForm(fs::parse('res://example_entity.yml'));
    $crudForm->setEntity(['firstName' => 'Dmitriy', 'lastName' => 'Zaitsev', 'confirmed' => true, 'sex' => 'FEMALE']);

    Timer::every('0.2s', function () use ($crudForm) {
        $crudForm->updateEntity(['progress' => $crudForm->getEntity()['progress'] + 0.005]);
    });

    $crudForm->getCrudUi()->on('cancel', fn() => $crudForm->hide());
    $crudForm->getCrudUi()->on('save', function (CrudUI $crudUi) use ($crudForm) {
        pre($crudUi->getEntity());
        $crudForm->hide();
    });

    $crudForm->centerOnScreen();
    $crudForm->show();
});


