<?php

namespace Redseanet\Forum\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Select;

class Poll extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_poll');
    }

    protected function afterLoad(&$result)
    {
        parent::afterLoad($result);
    }
}
