<?php

namespace Redseanet\Lib\ViewModel;

use Error;

class Wrapper extends AbstractViewModel
{
    /**
     * Render variables and children view models strightly
     *
     * @return string
     */
    public function render()
    {
        try {
            ob_start();
            foreach ($this->getChild() as $child) {
                echo $child->__toString();
            }
            return ob_get_clean();
        } catch (Error $e) {
            $this->getContainer()->get('log')->logError($e);
            ob_end_clean();
            return '';
        }
    }
}
