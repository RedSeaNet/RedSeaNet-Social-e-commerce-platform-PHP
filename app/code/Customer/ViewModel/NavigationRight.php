<?php

namespace Redseanet\Customer\ViewModel;

use Redseanet\Lib\Stdlib\Singleton;
use Redseanet\Lib\ViewModel\Template;

class NavigationRight extends Template implements Singleton
{
    protected static $instance = null;
    protected $links = [];
    protected $groups = [];

    protected function __construct()
    {
        $this->setTemplate('customer/navigationright');
    }

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getLinks()
    {
        uasort($this->links, function ($a, $b) {
            if (!isset($a['priority'])) {
                $a['priority'] = 0;
            }
            if (!isset($b['priority'])) {
                $b['priority'] = 0;
            }
            return $a['priority'] <=> $b['priority'];
        });
        return $this->links;
    }

    public function setLinks(array $links)
    {
        $this->links = $links;
        return $this;
    }

    public function addLink(array $link)
    {
        $this->links[] = $link;
        return $this;
    }

    public function addGroup(array $group)
    {
        $this->groups[] = $group;
        return $this;
    }

    public function getGroups()
    {
        uasort($this->groups, function ($a, $b) {
            if (!isset($a['priority'])) {
                $a['priority'] = 0;
            }
            if (!isset($b['priority'])) {
                $b['priority'] = 0;
            }
            return $a['priority'] <=> $b['priority'];
        });
        return $this->groups;
    }
}
