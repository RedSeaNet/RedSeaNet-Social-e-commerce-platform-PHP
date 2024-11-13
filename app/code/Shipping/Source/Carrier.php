<?php

namespace Redseanet\Shipping\Source;

use Redseanet\Lib\Source\SourceInterface;
use Redseanet\TrackingMore\Source\Carrier as Supported;

class Carrier implements SourceInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function getSourceArray()
    {
        $supported = (new Supported())->getSourceArray();
        $config = $this->getContainer()->get('config')['tracking/trackingmore/supported_carrier'];
        if (is_string($config)) {
            $config = explode(',', $config);
        }
        $result = [];
        foreach ($config as $item) {
            $result[$item] = $supported[$item];
        }
        return $result;
    }
}
