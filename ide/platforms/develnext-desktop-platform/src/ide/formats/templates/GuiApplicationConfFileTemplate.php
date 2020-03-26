<?php
namespace ide\formats\templates;

use ide\formats\AbstractFileTemplate;
use ide\project\Project;
use ide\project\supports\JavaFXProjectSupport;

/**
 * Class GuiApplicationConfFileTemplate
 * @package ide\formats\templates
 */
class GuiApplicationConfFileTemplate extends AbstractFileTemplate
{
    /**
     * @var Project
     */
    private $project;
    /**
     * @var JavaFXProjectSupport
     */
    private JavaFXProjectSupport $javafx;

    /**
     * GuiApplicationConfFileTemplate constructor.
     *
     * @param Project $project
     * @param JavaFXProjectSupport $javafx
     */
    public function __construct(Project $project, JavaFXProjectSupport $javafx)
    {
        parent::__construct();

        $this->project = $project;
        $this->javafx = $javafx;
    }


    /**
     * @return array
     * @throws \Exception
     */
    public function getArguments()
    {
        $javafx = $this->javafx;

        return [
            'PROJECT_NAME' => $this->project->getName(),
            'PROJECT_PACKAGE' => $this->project->getPackageName(),
            'MAIN_FORM' => $javafx->getMainForm($this->project),
            'APP_UUID' => $javafx->getAppUuid($this->project),
            'FX_SPLASH_AUTO_HIDE' => $javafx->getSplashData($this->project)['autoHide'] ? 1 : 0
        ];
    }
}