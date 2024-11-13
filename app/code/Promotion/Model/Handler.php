<?php

namespace Redseanet\Promotion\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Promotion\Model\Collection\Handler as Collection;

class Handler extends AbstractModel
{
    protected function construct()
    {
        $this->init('promotion_handler', 'id', ['id', 'promotion_id', 'parent_id', 'identifier', 'operator', 'value']);
    }

    public function getChildren()
    {
        if ($this->getId()) {
            $collection = new Collection();
            $collection->where(['parent_id' => $this->getId()]);
            return $collection;
        }
        return [];
    }

    public function getHandlerClass($identifier = null)
    {
        if (is_null($identifier)) {
            $identifier = $this->storage['identifier'];
        }
        $className = '\\Redseanet\\Promotion\\Model\\Handler\\' . str_replace(' ', '', ucwords(str_replace('_', ' ', $identifier)));
        if (is_subclass_of($className, '\\Redseanet\\Promotion\\Model\\Handler\\HandlerInterface')) {
            return new $className();
        }
        return null;
    }

    public function matchItems($items)
    {
        if ($this->getId()) {
            return $this->getHandlerClass()->matchItems($items, $this);
        }
        return [];
    }
}
