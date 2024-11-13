<?php

namespace Redseanet\Resource\Model;

use Redseanet\Lib\Model\AbstractModel;

/**
 * System backend Resource category
 */
class Category extends AbstractModel
{
    protected function construct()
    {
        $this->init('resource_category', 'id', ['id', 'store_id', 'parent_id', 'code']);
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        parent::afterSave();
        if (isset($this->storage['name'])) {
            $tableGateway = $this->getTableGateway('resource_category_language');
            foreach ((array) $this->storage['name'] as $languageId => $name) {
                $this->upsert(['name' => $name], ['category_id' => $this->getId(), 'language_id' => $languageId], $tableGateway);
            }
        }
        $this->commit();
    }
}
