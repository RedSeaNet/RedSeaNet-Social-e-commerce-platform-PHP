<?php

namespace Redseanet\Lib\Traits;

use Redseanet\Lib\Bootstrap;

trait Time
{
    public function secondTime2String($second)
    {
        $day = floor($second / (3600 * 24));
        $second = $second % (3600 * 24);
        $hour = floor($second / 3600);
        $second = $second % 3600;
        $minute = floor($second / 60);
        $second = $second % 60;
        return ['day' => $day, 'hour' => $hour, 'minute' => $minute, 'second' => $second];
    }
}
