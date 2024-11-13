<?php

namespace Redseanet\Resource\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Sql\Predicate\In;

/**
 * System backend Resource category
 */
class Category extends AbstractCollection
{
    protected function construct()
    {
        $this->init('resource_category');
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['id'])) {
            $ids = [];
            $data = [];
            foreach ($result as $item) {
                $ids[] = $item['id'];
                $data[$item['id']] = $item;
            }
            $languages = new Language();
            $languages->join('resource_category_language', 'core_language.id=resource_category_language.language_id', ['category_id', 'name'], 'right')
                    ->columns(['language_id' => 'id', 'language' => 'code', 'language_name' => 'name'])
                    ->where(new In('category_id', $ids));
            $languages->load(false);
            foreach ($languages as $item) {
                if (isset($data[$item['category_id']])) {
                    $data[$item['category_id']]['language'][$item['language_id']] = $item['language'];
                    $data[$item['category_id']]['name'][$item['language_id']] = $item['name'];
                    $data[$item['category_id']]['language_name'][$item['language_id']] = $item['language_name'];
                }
            }
            $result = array_values($data);
        }
        parent::afterLoad($result);
    }

    public function getCategoryByCode($code)
    {
        $this->select->where->equalTo('code', $code);
        return $this;
    }
}
