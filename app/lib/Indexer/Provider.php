<?php

namespace Redseanet\Lib\Indexer;

use Redseanet\Lib\Indexer\Handler\AbstractHandler;

/**
 * Provide generated structure and data to handler
 */
interface Provider
{
    /**
     * Provide indexer structure to handler
     *
     * @param AbstractHandler $handler
     * @return bool
     */
    public function provideStructure(AbstractHandler $handler);

    /**
     * Provide indexer data to handler
     *
     * @param AbstractHandler $handler
     * @return bool
     */
    public function provideData(AbstractHandler $handler);
}
