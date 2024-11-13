<?php

namespace Redseanet\Forum\Model\Collection;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Model\Collection\Language;
use Laminas\Db\Sql\Predicate\In;

class Category extends AbstractCollection
{
    protected $joined = false;

    protected function construct()
    {
        $this->init('forum_category');
    }

    public function withName($languageId = '')
    {
        $this->join('forum_category_language', 'forum_category_language.category_id=forum_category.id', ['name'], 'left')
                ->where(['forum_category_language.language_id' => $languageId != '' ? intval($languageId) : Bootstrap::getLanguage()->getId()]);
        $this->joined = true;
        return $this;
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['id']) && !$this->joined) {
            $ids = [];
            $data = [];
            foreach ($result as $item) {
                $ids[] = $item['id'];
                $data[$item['id']] = $item;
                $data[$item['id']]['language_id'] = [];
                $data[$item['id']]['language'] = [];
                $data[$item['id']]['name'] = [];
            }
            $languages = new Language();
            $languages->join('forum_category_language', 'core_language.id=forum_category_language.language_id', ['category_id', 'name'], 'right')
                    ->columns(['language_id' => 'id', 'language' => 'code'])
                    ->where(new In('category_id', $ids));
            $languages->load(false);
            foreach ($languages as $item) {
                if (isset($data[$item['category_id']])) {
                    $data[$item['category_id']]['language_id'][] = $item['language_id'];
                    $data[$item['category_id']]['language'][$item['language_id']] = $item['language'];
                    $data[$item['category_id']]['name'][$item['language_id']] = $item['name'];
                }
            }
            $result = array_values($data);
        }
        parent::afterLoad($result);
    }
}
