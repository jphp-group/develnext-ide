<?php
namespace ide;

use ide\project\supports\JavaFXGame2DSupport;
use ide\project\supports\JavaFXProjectSupport;

class DesktopExtension extends IdeStandardExtension
{
    public function onRegister()
    {
        $ide = Ide::get();
        $ide->addLanguageSource('en', 'res://.dn/bundle/uiDesktop/l10n/en.ini');
        $ide->addLanguageSource('ru', 'res://.dn/bundle/uiDesktop/l10n/ru.ini');

        $ide->registerProjectSupport(JavaFXProjectSupport::class);
        $ide->registerProjectSupport(JavaFXGame2DSupport::class);
    }
}