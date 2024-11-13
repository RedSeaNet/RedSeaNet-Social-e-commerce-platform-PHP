<?php

namespace Redseanet\Sales\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Lib\Bootstrap;

class Increment implements ListenerInterface
{
    public function generate($event)
    {
        $model = $event['model'];
        if (!$model->getId() && !$model['increment_id']) {
            $model->setData('increment_id', Bootstrap::getStore()->getId() . Bootstrap::getLanguage()->getId() . date('ymdHi') . random_int(1000, 9999));
        }
    }
}
