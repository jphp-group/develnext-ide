<?php

use crud\CrudEntity;
use crud\Cruds;
use crud\ui\CrudForm;
use crud\ui\CrudPane;
use crud\ui\CrudUI;
use php\gui\framework\Application;
use php\gui\layout\UXAnchorPane;
use php\gui\UXApplication;
use php\gui\UXForm;
use php\lib\fs;
use php\time\Timer;

$app = new Application();
$app->launch(function () {
    $crudPane = new CrudPane(fs::parse('res://example_entity.yml'));
    $crudForm = new CrudForm($crudPane);
    $crudPane->setEntity(['firstName' => 'Dmitriy', 'lastName' => 'Zaitsev', 'confirmed' => true, 'sex' => 'FEMALE']);

    Timer::every('0.2s', function () use ($crudPane) {
        $crudPane->updateEntity(['progress' => $crudPane->getEntity()['progress'] + 0.005]);
    });

    $crudPane->getCrudUi()->on('cancel', fn() => $crudForm->hide());
    $crudPane->getCrudUi()->on('save', function (CrudUI $crudUi) use ($crudForm) {
        pre($crudUi->getEntity());
        $crudForm->hide();
    });

    $crudForm->centerOnScreen();
    $crudForm->show();
});


