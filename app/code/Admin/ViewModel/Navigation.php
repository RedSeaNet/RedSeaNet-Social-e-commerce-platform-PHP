<?php

namespace Redseanet\Admin\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class Navigation extends Template
{
    protected $items = [];
    protected $role;

    protected function sortItems($a, $b)
    {
        if (!isset($a['priority'])) {
            $a['priority'] = 0;
        }
        if (!isset($b['priority'])) {
            $b['priority'] = 0;
        }
        return (int) $a['priority'] <=> (int) $b['priority'];
    }

    public function getMenuItems()
    {
        if (empty($this->items)) {
            $this->items = $this->getConfig()['menu'] ?? [];
        }
        uasort($this->items, [$this, 'sortItems']);
        return $this->items;
    }

    public function addMenuItem(array $item)
    {
        $this->items[] = $item;
        return $this;
    }

    public function setMenuItems(array $items)
    {
        $this->items = $items;
        return $this;
    }

    public function hasPermission($operation)
    {
        if (!$this->role) {
            $userArray = (new Segment('admin'))->get('user');
            $user = new User();
            $user->load($userArray['id']);
            $this->role = $user->getRole();
        }
        return $this->role->hasPermission($operation);
    }

    public function getUrl($path = '')
    {
        return strpos($path, '//') === false ? ($this->isAdminPage() ?
                $this->getAdminUrl($path) :
                $this->getBaseUrl($path)) : $path;
    }
}
