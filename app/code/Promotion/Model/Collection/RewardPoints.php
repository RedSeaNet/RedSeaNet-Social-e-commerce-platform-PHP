<?php

namespace Redseanet\Promotion\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;

class RewardPoints extends AbstractCollection
{
    protected function construct()
    {
        $this->init('reward_points');
    }
}
