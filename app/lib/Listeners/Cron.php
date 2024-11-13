<?php

namespace Redseanet\Lib\Listeners;

class Cron
{
    use \Redseanet\Lib\Traits\Container;

    public function pudgeCache()
    {
        $cache = $this->getContainer()->get('cache');
        $lists = $cache->fetch('CACHE_LIST');
        if ($lists) {
            $current = time();
            foreach ($lists as $prefix => $v) {
                $list = $cache->fetch('CACHE_LIST_' . $prefix);
                if ($list) {
                    foreach ($list as $id => $deadline) {
                        if ($deadline != -1 && $current > $deadline) {
                            $cache->delete($id, $prefix);
                        }
                    }
                }
            }
        }
    }
}
