<?php

namespace Redseanet\RewardPoints\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class Record extends AbstractCollection
{
    protected function construct()
    {
        $this->init('reward_points');
    }
}
