<?php

namespace Redseanet\I18n\Model;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Language;

class Region extends AbstractModel
{
    protected function construct()
    {
        $this->init('i18n_region', 'id', ['id', 'parent_id', 'code', 'default_name']);
    }

    protected function afterSave()
    {
        parent::afterSave();
        if (isset($this->storage['name'])) {
            $tableGateway = $this->getTableGateway('i18n_region_name');
            foreach ((array) $this->storage['name'] as $languageId => $name) {
                $language = new Language();
                $language->load($languageId);
                $this->upsert(['name' => $name], ['region_id' => $this->getId(), 'locale' => $language['code']], $tableGateway);
            }
        }
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('i18n_region_name', 'i18n_region_name.region_id=i18n_region.id', ['name'], 'left');
        $select->join('core_language', 'i18n_region_name.locale=core_language.code', ['language_id' => 'id', 'language' => 'name'], 'left');

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
}
