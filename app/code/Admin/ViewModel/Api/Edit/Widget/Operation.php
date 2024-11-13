<?php

namespace Redseanet\Admin\ViewModel\Api\Edit\Widget;

use Redseanet\Lib\ViewModel\Template;

class Operation extends Template
{
    public function getOperations()
    {
        $config = $this->getConfig()['api'];
        $result = [];
        if (!empty($config['wsdl']['port'])) {
            foreach ($config['wsdl']['port'] as $port) {
                if ($group = ($config['soap'][$port['name']] ?? false)) {
                    $group = substr($group, strrpos($group, '\\') + 1);
                    if (!isset($result[$group])) {
                        $result[$group] = [];
                    }
                    $result[$group][$port['name']] = $port['documentation'];
                }
            }
        }
        return $result;
    }

    public function getPermission()
    {
        return $this->getVariable('model') ?
                $this->getVariable('model')->getPermission() : [];
    }
}
