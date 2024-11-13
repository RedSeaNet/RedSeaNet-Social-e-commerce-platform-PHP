<?php

namespace Redseanet\Promotion\Model\Handler;

interface HandlerInterface
{
    /**
     * @param array $items
     * @param \Redseanet\Promotion\Model\Handler $handler
     * @return array
     */
    public function matchItems($items, $handler);
}
