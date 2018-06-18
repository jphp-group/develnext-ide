<?php
namespace ide\project\behaviours\backup;


class BackupDeleteMenuCommand extends BackupMenuCommand
{
    public function getName()
    {
        return 'command.delete::Удалить';
    }

    public function getIcon()
    {
        return 'icons/trash16.gif';
    }

    public function onBackupExecute(Backup $backup)
    {
        $this->behaviour->deleteBackupRequest($backup);
    }

    public function getAccelerator()
    {
        return 'Delete';
    }

    public function withBeforeSeparator()
    {
        return true;
    }
}