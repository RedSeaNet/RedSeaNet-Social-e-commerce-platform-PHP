<?php

namespace Redseanet\Cms\Model\Collection;

use Redseanet\Lib\Model\AbstractCollection;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Bootstrap;
use Laminas\Db\Sql\Predicate\In;

class Page extends AbstractCollection
{
    public function construct()
    {
        $this->init('cms_page');
    }

    protected function afterLoad(&$result)
    {
        $ids = [];
        $data = [];
        foreach ($result as $key => $item) {
            if (isset($item['id']) && isset($data[$item['id']])) {
                continue;
            }
            $content = @gzdecode($item['content']);
            if (isset($item['id'])) {
                $ids[] = $item['id'];
                $data[$item['id']] = $item;
                $data[$item['id']]['language'] = [];
                $data[$item['id']]['category'] = [];
                if ($content !== false) {
                    $data[$item['id']]['content'] = $content;
                }
            } elseif ($content !== false) {
                $result[$key]['content'] = $content;
            }
        }
        if (!empty($ids)) {
            $languages = new Language();
            $languages->join('cms_page_language', 'core_language.id=cms_page_language.language_id', ['page_id'], 'right')
                    ->columns(['language_id' => 'id', 'language' => 'code'])
                    ->where(new In('page_id', $ids));
            $languages->load(false);
            foreach ($languages as $item) {
                if (isset($data[$item['page_id']])) {
                    $data[$item['page_id']]['language'][$item['language_id']] = $item['language'];
                }
            }
            $tableGateway = $this->getTableGateway('cms_category_page');
            $select = $tableGateway->getSql()->select();
            $select->join('cms_category_language', 'cms_category_page.category_id=cms_category_language.category_id', ['name'], 'left')
                    ->where(new In('page_id', $ids))
                    ->where(['language_id' => Bootstrap::getLanguage()->getId()]);
            $category = $tableGateway->selectWith($select);
            foreach ($category as $item) {
                if (isset($data[$item['page_id']])) {
                    $data[$item['page_id']]['category'][$item['category_id']] = $item['name'];
                }
            }
            $result = array_values($data);
        }
        parent::afterLoad($result);
    }
}
