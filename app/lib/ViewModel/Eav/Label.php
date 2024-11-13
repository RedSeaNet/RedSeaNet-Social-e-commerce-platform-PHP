<?php

namespace Redseanet\Lib\ViewModel\Eav;

use Redseanet\Lib\Source\Language;
use Redseanet\Lib\ViewModel\Template;

class Label extends Template
{
    protected $languages = null;

    public function getLanguages()
    {
        if (is_null($this->languages)) {
            $this->languages = (new Language())->getSourceArray();
        }
        return $this->languages;
    }
}
