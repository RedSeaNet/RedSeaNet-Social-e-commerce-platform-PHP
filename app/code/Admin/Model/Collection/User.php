<?php

namespace Redseanet\Admin\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class User extends AbstractCollection
{
    protected function construct()
    {
        $this->init('admin_user');
    }
}
