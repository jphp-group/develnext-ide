<?php

use crud\CrudEntity;
use crud\Cruds;
use crud\ui\CrudUI;
use php\gui\layout\UXAnchorPane;
use php\gui\UXApplication;
use php\gui\UXForm;
use php\lib\fs;

UXApplication::launch(function (UXForm $form) {
    $crud = Cruds::create();

    $crudEntity = new CrudEntity();
    $crudEntity->load(fs::parse('res://example_entity.yml'));

    $crudUi = new CrudUI($crud, $crudEntity);
    $ui = $crudUi->makeUi();

    $crudUi->setEntity(['firstName' => 'Dmitriy', 'lastName' => 'Zaitsev', 'confirmed' => true, 'sex' => 'FEMALE']);
    $crudUi->load();

    $ui->prefWidth = 400;
    $ui->padding = 20;

    $form->layout = $ui;

    //$form->add($ui);
    $form->show();
    $form->centerOnScreen();

    $crudUi->on('cancel', fn() => $form->hide());
    $crudUi->on('save', function () use ($form, $crudUi) {
        pre($crudUi->getEntity());
        $form->hide();
    });
});

