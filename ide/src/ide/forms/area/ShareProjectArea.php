<?php

namespace ide\forms\area;

use ide\account\api\ServiceResponse;
use ide\forms\MessageBoxForm;
use ide\forms\SharedProjectDetailForm;
use ide\Ide;
use ide\Logger;
use ide\ui\Notifications;
use ide\utils\TimeUtils;
use php\gui\framework\AbstractFormArea;
use php\gui\layout\UXVBox;
use php\gui\UXCheckbox;
use php\gui\UXClipboard;
use php\gui\UXHyperlink;
use php\gui\UXLabel;
use php\gui\UXNode;
use php\lib\str;

/**
 * Class SyncProjectArea
 * @package ide\forms\area
 *
 * @property UXVBox $content
 * @property UXHyperlink $urlLink
 * @property UXLabel $updatedAtLabel
 * @property UXNode $syncPane
 * @property UXCheckbox $autoSyncCheckbox
 * @property UXNode $nonSyncPane
 */
class ShareProjectArea extends AbstractFormArea
{
    protected $_syncPane;
    protected $_nonSyncPane;

    protected $data;

    /**
     * @var callable
     */
    protected $doRefresh;

    public function __construct(callable $refreshCallback)
    {
        parent::__construct();
        $this->doRefresh = $refreshCallback;

        $this->_syncPane = $this->syncPane;
        $this->_nonSyncPane = $this->nonSyncPane;

        $this->urlLink->on('action', function () {
            browse($this->urlLink->text);
        });

        _($this);
    }

    public function setData(array $data = null)
    {
        $this->data = $data;
        $this->_nonSyncPane->free();
        $this->_syncPane->free();

        if ($data) {
            $canWrite = Ide::accountManager()->getAccountData()['id'] == $this->data['owner'];

            if ($canWrite) {
                $this->content->add($this->_syncPane);

                $this->updatedAtLabel->text = TimeUtils::getUpdateAt($data['updatedAt']);
                $this->setUrl('https://hub.develnext.org/project/' . $data['uid']);
            } else {
                $this->content->add($this->_nonSyncPane);
            }
        } else {
            $this->content->add($this->_nonSyncPane);
        }
    }

    public function setAutoSync($value)
    {
        if ($this->autoSyncCheckbox) {
            $this->autoSyncCheckbox->selected = $value;
        }
    }

    public function setUrl($url)
    {
        $this->urlLink->text = $url;
    }

    /**
     * @event copyButton.action
     */
    public function doCopyButtonAction()
    {
        UXClipboard::setText($this->urlLink->text);
        Ide::get()->toast('message.link.successful.copied::Ссылка успешно скопирована.');
    }

    /**
     * @event urlLink.action
     */
    public function doUrlLinkAction()
    {
        Ide::get()->toast('Opening ' . $this->urlLink->text . ' ...');
        browse($this->urlLink->text);
    }

    public function reUpload($silent = false)
    {
        if (Ide::get()->isSnapshotVersion()) {
            MessageBoxForm::warning("message.this.functions.not.available.in.snapshot::Данная функция недоступна в SNAPSHOT версии среды");
            return;
        }

        $project = Ide::project();
        $project->save();

        $this->showPreloader('message.uploading.project.to.hub::Загружаем проект на hub.develnext.org ...');

        $file = Ide::get()->createTempFile('.zip');

        $project->export($file);

        $res = Ide::service()->projectArchive()->updateAsync($this->data['id'], $project->getName(), '', null);
        $res->on('success', function (ServiceResponse $res) use ($file, $project, $silent) {
            $this->setData($res->data());
            Ide::project()->getIdeServiceConfig()->set('projectArchive.uid', $res->result('uid'));

            Ide::service()->projectArchive()->uploadArchiveAsync($res->result('id'), $file, function (ServiceResponse $response) use ($project, $silent) {
                if ($response->isSuccess()) {
                    $this->setData($response->result());

                    if (!$silent) {
                        $this->hidePreloader();
                        $this->refresh();

                        uiLater(function () {
                            Notifications::show(
                                'common.changes.uploaded::Изменения загружены',
                                'message.project.was.successful.uploaded.to.hub::Измненения в проекте были успешно загружены на hub.develnext.org');
                        });
                    }
                } else {
                    $this->hidePreloader();
                    if (!$silent) {
                        list($message, $arg) = str::split($response->result(), ':');

                        if ($message === 'FileSizeLimit') {
                            $mb = round($arg / 1024 / 1024, 2);
                            Notifications::warning('project.not.uploaded::Проект не загружен',
                                _("project.size.is.big.for.upload::Проект слишком большой для загрузки, максимум разрешено {0} mb!", $mb));
                        } else {
                            if ($response->isAccessDenied()) {
                                Notifications::warning(
                                    'common.access.denied::Доступ запрещен',
                                    'message.you.have.not.access.for.project::У вас нет доступа на запись к этому проекту, попробуйте его загрузить по новой.'
                                );
                            } else if ($response->isNotFound()) {
                                Notifications::warning('project.not.found::Проект не найден',
                                    'message.project.cannot.upload.unknown::По неясной причине проект не был найден, попробуйте его загрузить по новой.');
                            } else {
                                Notifications::error(
                                    'project.not.uploaded::Проект не загружен',
                                    'message.unknown.error.service.not.available::Произошла непредвиденная ошибка, возможно сервис временно недоступен, попробуйте позже.'
                                );
                            }
                        }
                    }
                }
            });
        });

        $res->on('fail', function (ServiceResponse $res) use ($silent) {
            $this->hidePreloader();

            if (!$silent) {
                if ($res->isConflict()) {
                    Notifications::error('project.not.uploaded::Проект не загружен', 'message.you.already.have.project.with.name::У вас уже есть проект с таким именем, измените название проекта.');
                } else {
                    Notifications::error('project.not.uploaded::Проект не загружен', 'message.unknown.error.service.not.available::Произошла непредвиденная ошибка, возможно сервис временно недоступен, попробуйте позже.');
                }
            }
        });
    }

    /**
     * @event uploadButton.action
     */
    public function doReUploadButtonAction()
    {
        if (!MessageBoxForm::confirm('message.confirm.to.upload.project.changes.to.hub::Вы точно хотите загрузить изменения в проекте на develnext.org?', $this)) {
            return;
        }

        $this->reUpload();
    }

    /**
     * @event сontrolButton.action
     */
    public function doControlAction()
    {
        $form = new SharedProjectDetailForm($this->data['uid']);
        $form->showAndWait();

        $this->refresh();
    }

    /**
     * ...
     */
    public function refresh()
    {
        $refresh = $this->doRefresh;
        $refresh();
    }

    /**
     * @event shareButton.action
     */
    public function doShareButtonAction()
    {
        if (Ide::get()->isSnapshotVersion()) {
            MessageBoxForm::warning("message.this.functions.not.available.in.snapshot::Данная функция недоступна в SNAPSHOT версии среды");
            return;
        }

        if ($this->data['canWrite'] === false) {
            $this->doReUploadButtonAction();
            return;
        }

        if (!MessageBoxForm::confirm('message.confirm.to.upload.project.to.hub::Вы точно хотите загрузить проект на develnext.org?', $this)) {
            return;
        }

        $this->showPreloader('message.saving.project::Сохраняем проект ...');

        $file = Ide::get()->createTempFile('.zip');

        $project = Ide::project();

        $project->export($file);

        $this->showPreloader('message.uploading.project.to.hub::Загружаем проект на hub.develnext.org ...');

        $failedCallback = function (ServiceResponse $response) {
            if ($response->isConflict()) {
                Notifications::error('project.not.uploaded::Проект не загружен', 'message.you.already.have.project.with.name::У вас уже есть проект с таким именем, измените название проекта.');
            } else {
                list($message, $arg) = str::split($response->result(), ':');

                switch ($message) {
                    case 'FileSizeLimit':
                        $mb = round($arg / 1024 / 1024, 2);
                        Notifications::warning('project.not.uploaded::Проект не загружен', _("project.size.is.big.for.upload::Проект слишком большой для загрузки, максимум разрешено {0} mb!", $mb));
                        break;
                    case 'LimitSpacePerDay':
                        Notifications::warning('project.not.uploaded::Проект не загружен', "message.limit.space.per.day::Вы исчерпали лимит загрузок на сегодня, попробуйте удалить большие ненужные проекты!");
                        break;
                    case 'CountLimit':
                        Notifications::warning('project.not.uploaded::Проект не загружен', _("message.project.count.limit::Вы загрузили слишком много проектов, максимум разрешено {0} шт. в день!", $response->data()));
                        break;
                    default:
                        Logger::error("Unable to upload project {$response->toLog()}");
                        Notifications::error('project.not.uploaded::Проект не загружен', 'message.unknown.error.service.not.available');
                }
            }
            
            $this->hidePreloader();
        };

        $response = Ide::service()->projectArchive()->createAsync($project->getName(), '', null);
        $response->on('fail', $failedCallback);
        $response->on('success', function (ServiceResponse $response) use ($file, $failedCallback) {
            $projectId = $response->result('id');
            $projectUid = $response->result('uid');

            $response = Ide::service()->projectArchive()->uploadArchiveAsync($projectId, $file, null);
            $response->on('fail', $failedCallback);

            $response->on('success', function (ServiceResponse $response) use ($projectUid, $failedCallback) {
                Notifications::show('project.is.uploaded::Проект загружен', 'message.project.is.uploaded.to.hub::Ваш проект был успешно загружен и опубликован на hub.develnext.org', 'SUCCESS');

                Ide::project()->getIdeServiceConfig()->set("projectArchive.uid", $projectUid);

                uiLater(function () use ($response) {
                    $this->hidePreloader();
                    $this->setData($response->result());
                });
            });
        });
    }
}