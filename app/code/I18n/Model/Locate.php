<?php

namespace Redseanet\I18n\Model;

use BadMethodCallException;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Select;

class Locate
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    public function getLabel($part, $id = '', $pid = '')
    {
        $cache = $this->getContainer()->get('cache');
        $result = $cache->fetch($part . $id, 'I18N_');
        if (!$result) {
            $select = new Select('i18n_' . $part);
            $select->join('i18n_' . $part . '_name', $part . '_id=id', ['name', 'locale'], 'left');
            if ($id) {
                $select->where(['id' => $id]);
            }
            if ($pid) {
                $select->where(['parent_id' => $pid]);
            }
            if (extension_loaded('pdo_sqlite') && file_exists(BP . 'var/i18n.db')) {
                $adapter = new Adapter([
                    'driver' => 'pdo',
                    'dsn' => 'sqlite:' . BP . 'var\i18n.db'
                ]);
                $resultSet = $adapter->query($select->getSqlString($adapter->getPlatform()), 'execute');
            } else {
                $resultSet = $this->getTableGateway('i18n_' . $part)->selectWith($select)->toArray();
            }
            $result = [];
            foreach ($resultSet as $item) {
                if (isset($result[$item['id']])) {
                    $result[$item['id']]['name'][$item['locale']] = $item['name'];
                } else {
                    $result[$item['id']] = new Locate\Item($item);
                    $result[$item['id']]['name'] = [$item['locale'] => $item['name']];
                    unset($result[$item['id']]['locale']);
                }
            }
            $cache->save($part . $id, $result, 'I18N_');
        }
        return $result;
    }

    public function getCode($part, $id)
    {
        $select = new Select('i18n_' . $part);
        $select->where(['id' => $id]);
        if (extension_loaded('pdo_sqlite') && file_exists(BP . 'var/i18n.db')) {
            $adapter = new Adapter([
                'driver' => 'pdo',
                'dsn' => 'sqlite:' . BP . 'var\i18n.db'
            ]);
            $resultSet = $adapter->query($select->getSqlString($adapter->getPlatform()), 'execute');
        } else {
            $resultSet = $this->getTableGateway('i18n_' . $part)->selectWith($select)->toArray();
        }
        return empty($resultSet) ? '' : ($resultSet[0]['iso2_code'] ?? ($resultSet[0]['code'] ?? ''));
    }

    public function load($part, $id = '')
    {
        $cache = $this->getContainer()->get('cache');
        $result = $cache->fetch($part . 'c' . $id, 'I18N_');
        if (!$result) {
            $select = new Select('i18n_' . $part);
            $select->join('i18n_' . $part . '_name', $part . '_id=id', ['name', 'locale'], 'left');
            if ($id) {
                $select->where(['parent_id' => (int) $id]);
            }
            if (extension_loaded('pdo_sqlite') && file_exists(BP . 'var/i18n.db')) {
                $adapter = new Adapter([
                    'driver' => 'pdo',
                    'dsn' => 'sqlite:' . BP . 'var\i18n.db'
                ]);
                $resultSet = $adapter->query($select->getSqlString($adapter->getPlatform()), 'execute');
            } else {
                $resultSet = $this->getTableGateway('i18n_' . $part)->selectWith($select)->toArray();
            }
            $result = [];
            foreach ($resultSet as $item) {
                if (isset($result[$item['id']])) {
                    $result[$item['id']]['name'][$item['locale']] = $item['name'];
                } else {
                    $result[$item['id']] = new Locate\Item($item);
                    $result[$item['id']]['name'] = [$item['locale'] => $item['name']];
                    unset($result[$item['id']]['locale']);
                }
            }
            $cache->save($part . 'c' . $id, $result, 'I18N_');
        }
        return $result;
    }

    public function __call($name, $arguments)
    {
        if (in_array($name, ['getCountry', 'getCountries'])) {
            return $this->load('country');
        } elseif (in_array($name, ['getRegion', 'getRegions', 'getState', 'getStates', 'getProvience', 'getProviences'])) {
            return $this->load('region', count($arguments) ? $arguments[0] : '');
        } elseif (in_array($name, ['getCity', 'getCities'])) {
            return $this->load('city', count($arguments) ? $arguments[0] : '');
        } elseif (in_array($name, ['getCounty', 'getCounties'])) {
            return $this->load('county', count($arguments) ? $arguments[0] : '');
        } else {
            throw new BadMethodCallException('Call to undefined method: ' . $name);
        }
    }
}
