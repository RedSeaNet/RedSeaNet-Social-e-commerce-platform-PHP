<?php

namespace Redseanet\Customer\Model;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractModel;

class Level extends AbstractModel
{
    protected function construct()
    {
        $this->init('customer_level', 'id', ['id', 'level', 'amount']);
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
            $tableGateway = $this->getTableGateway('customer_level_language');
            foreach ((array) $this->storage['name'] as $languageId => $name) {
                $this->upsert(['name' => $name], ['level_id' => $this->getId(), 'language_id' => $languageId], $tableGateway);
            }
        }
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('customer_level_language', 'customer_level_language.level_id=customer_level.id', ['name'], 'left');
        $select->join('core_language', 'customer_level_language.language_id=core_language.id', ['language_id' => 'id', 'language' => 'name'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['id'])) {
            $language = [];
            $name = [];
            foreach ($result as $item) {
                $language[$item['language_id']] = $item['language'];
                $name[$item['language_id']] = $item['name'];
            }
            $result[0]['language'] = $language;
            $result[0]['language_id'] = array_keys($language);
            $result[0]['name'] = $name;
        }
        parent::afterLoad($result);
    }

    public function getName($languageId = null)
    {
        if (!$this->getId()) {
            return 0;
        }
        if (is_null($languageId)) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        return $this->storage['name'][$languageId] ?? 0;
    }
}
