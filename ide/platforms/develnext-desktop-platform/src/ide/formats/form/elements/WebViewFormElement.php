<?php
namespace ide\formats\form\elements;

use ide\formats\form\AbstractFormElement;
use ide\library\IdeLibraryScriptGeneratorResource;
use php\gui\UXNode;
use php\gui\UXWebView;

class WebViewFormElement extends AbstractFormElement
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'ui.elemenet.web.view::Web Браузер';
    }

    public function getElementClass()
    {
        return UXWebView::class;
    }

    public function getGroup()
    {
        return 'ui.group.additional::Дополнительно';
    }

    public function getIcon()
    {
        return 'icons/webBrowser16.png';
    }

    public function getIdPattern()
    {
        return "browser%s";
    }

    /**
     * @return UXNode
     */
    public function createElement()
    {
        $element = new UXWebView();

        //Ide::get()->getMainForm()->toast('У браузера есть баги при открытии некоторых страниц');

        return $element;
    }

    public function getDefaultSize()
    {
        return [300, 300];
    }

    public function isOrigin($any)
    {
        return $any instanceof UXWebView;
    }

    public function getScriptGenerators()
    {
        return [
            new IdeLibraryScriptGeneratorResource('res://.dn/bundle/uiDesktop/scriptgen/LoadHtmlWebViewScriptGen'),
            new IdeLibraryScriptGeneratorResource('res://.dn/bundle/uiDesktop/scriptgen/HistoryListWebViewScriptGen'),
        ];
    }
}