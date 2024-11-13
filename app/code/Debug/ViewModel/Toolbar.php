<?php

namespace Redseanet\Debug\ViewModel;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Template;

class Toolbar extends Template
{
    protected $unit = ['B', 'KB', 'MB', 'GB'];

    protected function getRendered($template)
    {
        if (!Bootstrap::isDeveloperMode()) {
            return '';
        }
        $result = parent::getRendered($template);
        $this->getSegment('debug')->set('sql', [])
                ->set('cache', []);
        return $result;
    }

    public function getSqls()
    {
        //var_dump($this->getContainer()->get('dbAdapter'));        exit('-=');
        if ($this->getContainer()->get('dbAdapter')->getProfiler() != null) {
            foreach ($this->getContainer()->get('dbAdapter')->getProfiler()->getProfiles() as $sql) {
                if (strtoupper(substr($sql['sql'], 0, 7)) === 'EXPLAIN') {
                    yield $sql;
                }
            }
        }
    }

    public function getCacheList()
    {
        $list = $this->getSegment('debug')->get('cache', []);
        ksort($list);
        return $list;
    }

    public function getMemory()
    {
        return $this->formatMemory(memory_get_peak_usage());
    }

    protected function formatMemory($memory, $unit = 0)
    {
        return $memory >= 1024 && $unit < 2 ? $this->formatMemory((float) $memory / 1024, $unit + 1) : sprintf('%.3f' . $this->unit[$unit], $memory);
    }

    public function showTip()
    {
        return $this->getSegment('debug')->get('tip', false);
    }
}
