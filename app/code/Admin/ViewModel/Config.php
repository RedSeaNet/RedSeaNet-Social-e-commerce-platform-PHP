<?php

namespace Redseanet\Admin\ViewModel;

use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Config extends Edit
{
    protected $key = null;
    protected $elements = null;
    protected $config = null;
    protected $tab = null;
    protected $store = false;

    public function __construct()
    {
        $this->setTemplate('admin/config');
    }

    public function getSaveUrl()
    {
        return $this->getAdminUrl('config/save/');
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getElements()
    {
        if (is_null($this->elements)) {
            $this->elements = $this->getConfig()['system'][$this->getKey()]['children'];
        }
        uasort($this->elements, function ($a, $b) {
            if (!isset($a['type']) && isset($b['type'])) {
                return 1;
            } elseif (!isset($b['type']) && isset($a['type'])) {
                return -1;
            }
            if (!isset($a['priority'])) {
                $a['priority'] = 0;
            }
            if (!isset($b['priority'])) {
                $b['priority'] = 0;
            }
            return $a['priority'] <=> $b['priority'];
        });
        return $this->elements;
    }

    public function setKey($key)
    {
        $this->key = $key;
        return $this;
    }

    public function setElements($elements)
    {
        $this->elements = $elements;
        return $this;
    }

    protected function getRendered($template)
    {
        if (!$this->getKey()) {
            return '';
        }
        return parent::getRendered($template);
    }

    protected function prepareElements($columns = [])
    {
        foreach ((array) $this->getElements() as $key => $item) {
            $column = $this->getColumn($item, $key, $this->getKey());
            if ($column) {
                $columns[$key] = $column;
            }
        }
        return $columns;
    }

    protected function getTab()
    {
        if (is_null($this->tab)) {
            $this->tab = $this->getChild('tabs');
        }
        return $this->tab;
    }

    protected function getColumn($item, $key, $prefix)
    {
        $scope = $this->getQuery('scope');
        $scopeId = $this->getQuery('scope_id');
        if (isset($item['scope']) && !in_array($this->getStore() ? 'store' : $this->getQuery('scope', 'merchant'), (array) $item['scope'])) {
            return null;
        }
        if (isset($item['children'])) {
            $result = [];
            foreach ($item['children'] as $ckey => $child) {
                $column = $this->getColumn($child, $ckey, $prefix . '/' . $key);
                if ($column) {
                    $result[$ckey] = $column;
                }
            }
            if (!empty($result)) {
                $this->getTab()->addTab($key, $item['label']);
            }
            return $result;
        }
        if (isset($item['source']) && is_subclass_of($item['source'], '\\Redseanet\\Lib\\Source\\SourceInterface')) {
            $item['options'] = (new $item['source']())->getSourceArray($item);
        }
        if ($scope && $scopeId) {
            $config = $this->getConfig()[substr($scope, 0, 1) . $scopeId . '/' . $prefix . '/' . $key];
        } else {
            $config = $this->getConfig()[$prefix . '/' . $key];
        }
        if (is_scalar($config)) {
            $item['value'] = ($item['type'] === 'image' ? $this->getBaseUrl($config) : $config);
        } else {
            if (is_array($config) && (in_array($item['type'], ['multiselect', 'checkbox']) || $item['type'] === 'select' && isset($item['attrs']['multiple']))) {
                $item['value'] = $config;
            } else {
                $item['value'] = ($item['default'] ?? '');
            }
        }
        return $item;
    }

    public function getStore()
    {
        if ($this->store === false) {
            $userArray = (new Segment('admin'))->get('user');
            $user = new User();
            $user->load($userArray['id']);
            $this->store = $user->getStore();
        }
        return $this->store;
    }
}
