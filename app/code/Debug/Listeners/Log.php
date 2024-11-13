<?php

namespace Redseanet\Debug\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Lib\Session\Segment;

class Log implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    protected $segment = null;

    protected function getSegment()
    {
        if (is_null($this->segment)) {
            $this->segment = new Segment('debug');
        }
        return $this->segment;
    }

    public function logCache($e)
    {
        $segment = $this->getSegment();
        $caches = $segment->get('cache', []);
        $prefix = trim($e['prefix'], ' _');
        if (!isset($caches[$prefix])) {
            $caches[$prefix] = [];
        }
        $caches[$prefix][$e['key']] = $e['result'];
        $segment->set('cache', $caches);
    }

    public function logCaches($e)
    {
        foreach ($e['keys'] as $key) {
            $this->logCache(['key' => $key, 'prefix' => $e['prefix'], 'result' => $e['result'][$key]]);
        }
    }
}
