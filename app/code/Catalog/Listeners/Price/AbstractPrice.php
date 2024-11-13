<?php

namespace Redseanet\Catalog\Listeners\Price;

use Redseanet\Lib\Listeners\ListenerInterface;

abstract class AbstractPrice implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    protected function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }

    abstract public function calc($event);
}
