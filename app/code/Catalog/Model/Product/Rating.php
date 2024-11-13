<?php

namespace Redseanet\Catalog\Model\Product;

use Redseanet\Lib\Model\AbstractModel;

class Rating extends AbstractModel
{
    protected function construct()
    {
        $this->init('rating', 'id', ['id', 'type', 'title']);
    }

    public function afterSave()
    {
        $adapter = $this->getContainer()->get('dbAdapter');
        if (!empty($this->storage['category'])) {
            $tableGateway = new TableGateway('rating', $adapter);
            $tableGateway->delete(['id' => $this->getId()]);
            foreach ((array) $this->storage['category'] as $category) {
                $tableGateway->insert(['id' => $this->getId(), 'id' => $category]);
            }
        }
        return parent::afterSave();
    }
}
