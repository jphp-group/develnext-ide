<?php
namespace ide;

abstract class AbstractExtension
{
    abstract public function onRegister();
    abstract public function onIdeStart();
    abstract public function onIdeShutdown();

    abstract public function getName(): string;
    abstract public function getDescription(): string;

    public function getIcon32(): string {
        return "icons/plugin32.png";
    }

    public function isSystem(): bool {
        return false;
    }

    /**
     * @return string[] classes of AbstractExtension or AbstractBundle
     */
    public function getDependencies()
    {
        return [];
    }
}