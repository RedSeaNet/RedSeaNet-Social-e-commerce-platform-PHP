<?php

namespace Redseanet\I18n\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Country extends AbstractCollection
{
    protected function construct()
    {
        $this->init('i18n_country');
    }
}
