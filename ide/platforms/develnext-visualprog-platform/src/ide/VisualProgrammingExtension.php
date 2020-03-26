<?php
namespace ide;

use ide\project\supports\VisualProgrammingSupport;

class VisualProgrammingExtension extends IdeStandardExtension
{
    public function onRegister()
    {
        $ide = Ide::get();

        $ide->registerProjectSupport(VisualProgrammingSupport::class);
        $ide->addLanguageSource('en', 'res://.dn/visprog/l10n/en.ini');
        $ide->addLanguageSource('ru', 'res://.dn/visprog/l10n/ru.ini');
    }
}