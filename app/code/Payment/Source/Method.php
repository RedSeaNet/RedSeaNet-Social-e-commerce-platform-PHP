<?php

namespace Redseanet\Payment\Source;

use Redseanet\Lib\Source\SourceInterface;

class Method implements SourceInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\Translate;

    public function getSourceArray()
    {
        $config = $this->getContainer()->get('config');
        $result = [];
        foreach ($config['system']['payment']['children'] as $code => $info) {
            $result[$code] = $this->translate($config['payment/' . $code . '/label']);
        }
        return $result;
    }
}
