<?php

namespace Redseanet\Shipping\Model;

abstract class AbstractMethod
{
    use \Redseanet\Lib\Traits\Container;

    public function available($data = [])
    {
        $config = $this->getContainer()->get('config');
        return $config['shipping/' . static::METHOD_CODE . '/enable'] &&
                ($config['shipping/' . static::METHOD_CODE . '/max_total'] === '' ||
                $config['shipping/' . static::METHOD_CODE . '/max_total'] >= $data['total']) &&
                $config['shipping/' . static::METHOD_CODE . '/min_total'] <= $data['total'];
    }

    abstract public function getShippingRate($items);

    public function getLabel()
    {
        return $this->getContainer()->get('config')['shipping/' . static::METHOD_CODE . '/label'];
    }
}
