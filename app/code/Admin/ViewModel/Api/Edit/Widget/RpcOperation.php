<?php

namespace Redseanet\Admin\ViewModel\Api\Edit\Widget;

use Redseanet\Lib\ViewModel\Template;

class RpcOperation extends Template
{
    public function getOperations()
    {
        $config = $this->getConfig()['api'];
        $result = [];
        if (!empty($config['rpcport'])) {
            foreach ($config['rpcport'] as $portkey => $port) {
                if ($group = ($config['rpc'][$portkey] ?? false)) {
                    $group = substr($group, strrpos($group, '\\') + 1);
                    if (!isset($result[$group])) {
                        $result[$group] = [];
                    }
                    $result[$group][$portkey] = $port['documentation'];
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
