<?php
namespace ide\commands\account;

use ide\editors\AbstractEditor;
use ide\forms\MessageBoxForm;
use ide\Ide;
use ide\misc\AbstractCommand;

/**
 * Class AccountLogoutCommand
 * @package ide\commands\account
 */
class AccountLogoutCommand extends AbstractCommand
{
    public function getName()
    {
        return "Выйти из аккаунта";
    }

    public function getCategory()
    {
        return 'account';
    }

    public function getIcon()
    {
        return 'icons/accountLogout16.png';
    }

    public function withBeforeSeparator()
    {
        return true;
    }

    public function onExecute($e = null, AbstractEditor $editor = null)
    {
        $email = Ide::accountManager()->getAccountData()['login'];

        $dialog = new MessageBoxForm("Вы точно хотите выйти из своего аккаунта, $email?", ['btn.yes', 'btn.no']);

        if ($dialog->showDialog()) {
            if ($dialog->getResult() == _('btn.yes')) {
                Ide::service()->account()->logout();
                Ide::accountManager()->setAccessToken(null);

                Ide::get()->getMainForm()->toast(_('account.logout.is.successful::Вы успешно вышли из аккаунта'));
            }
        }
    }
}