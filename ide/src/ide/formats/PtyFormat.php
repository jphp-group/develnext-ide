<?php

namespace ide\formats;

use ide\editors\AbstractEditor;
use ide\editors\PtyEditor;
use php\lib\str;

class PtyFormat extends AbstractFormat {

    /**
     * @param $file
     * @param array $options
     * @return AbstractEditor
     */
    public function createEditor($file, array $options = []) {
        return new PtyEditor($file);
    }

    /**
     * @param $file
     * @return bool
     */
    public function isValid($file) {
        return str::startsWith($file, "pty://");
    }

    /**
     * @param $any
     * @return mixed
     */
    public function register($any) {
        // nope.
    }
}