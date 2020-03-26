<?php
namespace ide\action;

abstract class AbstractActionTypeGroup
{
    abstract public function getCode(): string;
    abstract public function getName(): string;

    public function getSubGroups(): array
    {
        return [
            'common' => 'action.type.group.common::Главное',
            'misc' => 'action.type.group.additional::Другое'
        ];
    }

    public function getSubGroupName(string $code): string
    {
        return $this->getSubGroups()[$code] ?: $code;
    }
}