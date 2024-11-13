<?php

namespace Redseanet\Promotion\Model\Condition;

interface ConditionInterface
{
    /**
     * @param mixed $model
     * @param \Redseanet\Promotion\Model\Condition $condition
     * @param int $storeId
     * @return bool
     */
    public function match($model, $condition, $storeId);
}
