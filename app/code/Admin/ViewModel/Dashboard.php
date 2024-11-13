<?php

namespace Redseanet\Admin\ViewModel;

use Redseanet\Lib\ViewModel\Template;

class Dashboard extends Template
{
    public function getStat()
    {
        return $this->getConfig()['stat'];
    }

    public function getEvents()
    {
        $result = [];
        if (is_readable(BP . 'var/log/info.log')) {
            $handler = fopen(BP . 'var/log/info.log', 'r');
            if ($handler) {
                while (!feof($handler)) {
                    $line = trim(fgets($handler));
                    if ($line) {
                        //preg_match('#^\[(?P<time>[\d\:\s\-]+)\][^\:]+\: (?P<user>[^\s]+) has (?P<operation>logged in|saved|deleted) (?P<target>[^\[]*)\[\] \[\]$#', $line, $matches);
                        //preg_match('#(?P<user>[^\s]+) has (?P<operation>logged in|saved|deleted) (?P<target>[^\[]*)\[\] \[\]$#', $line, $matches);
                        //$result[] = '<span class="time">' . ((isset($matches['time'])) ? $matches['time'] : ''). '</span>' .
                        //        $this->translate('%s has ' . (isset($matches['operation']) ? $matches['operation'] : '') . ' %s', [(isset($matches['user']) ? $matches['user'] : ''), (isset($matches['target']) ? $matches['target'] : '')]);
                        $result[] = $line;
                    }
                }
                fclose($handler);
            }
        }
        return $result;
    }

    public function renderCell($item)
    {
        $cell = new Template();
        $cell->setTemplate($item['template'] ?? 'admin/dashboard/cell')
                ->setVariable('item', $item);
        return $cell;
    }
}
