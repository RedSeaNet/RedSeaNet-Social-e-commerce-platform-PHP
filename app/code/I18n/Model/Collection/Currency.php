<?php

namespace Redseanet\I18n\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Currency extends AbstractCollection
{
    protected function construct()
    {
        $this->init('i18n_currency');
    }
}
