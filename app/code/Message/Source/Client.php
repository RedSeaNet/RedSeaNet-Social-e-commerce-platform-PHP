<?php

namespace Redseanet\Message\Source;

use Redseanet\Lib\Source\SourceInterface;

class Client implements SourceInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function getSourceArray()
    {
        $config = $this->getContainer()->get('config');
        $result = [];
        foreach ($config['system']['message']['children'] as $key => $value) {
            if (isset($value['children']['model']) && class_exists($value['children']['model']['default'])) {
                $result[$key] = $value['label'];
            }
        }
        return $result;
    }
}
