<?php

namespace Redseanet\Admin\Listeners;

class Cron
{
    use \Redseanet\Lib\Traits\Container;

    public function reindex(...$code)
    {
        $manager = $this->getContainer()->get('indexer');
        if ($code) {
            foreach ((array) $code as $indexer) {
                $manager->reindex(is_string($indexer) ? $indexer : $indexer['code']);
            }
        }
    }
}
