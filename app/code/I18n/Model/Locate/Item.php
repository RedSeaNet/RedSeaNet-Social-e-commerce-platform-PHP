<?php

namespace Redseanet\I18n\Model\Locate;

use Redseanet\Lib\Stdlib\ArrayObject;

class Item extends ArrayObject
{
    public function __construct($input = [])
    {
        $this->storage = $input;
    }

    public function getName($locale = null)
    {
        if (is_null($locale) || !isset($this->storage['name'][$locale])) {
            return $this->storage['default_name'];
        }
        return $this->storage['name'][$locale];
    }
}
