<?php
namespace ide\formats\form\event;

use ide\editors\AbstractEditor;
use php\gui\event\UXMouseEvent;

class MouseParamEventKind extends MouseEventKind
{
    public function getParamVariants(AbstractEditor $contextEditor = null)
    {
        return [
            'ui.event.mouse.any.btn::Любая кнопка' => '',
            '-',
            'ui.event.mouse.primary.btn::Левая кнопка' => 'Left',
            'ui.event.mouse.secondary.btn::Правая кнопка' => 'Right',
            'ui.event.mouse.middle.btn::Средняя кнопка' => 'Middle',
            '-',
            'ui.event.mouse.2x.click::Двойное нажатие' => '2x',
            'ui.event.mouse.3x.click::Тройное нажатие' => '3x',
        ];
    }
}