<?php

namespace Redseanet\Forum\Model;

use Redseanet\Forum\Model\Collection\Category as Collection;
use Redseanet\Forum\Model\Collection\Post as PostCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractModel;

class Category extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected function construct()
    {
        $this->init('forum_category', 'id', ['id', 'parent_id', 'uri_key', 'sort_order']);
    }

    public function getParentCategory()
    {
        if (!empty($this->storage['parent_id'])) {
            $navgiation = new static();
            $navgiation->load($this->storage['parent_id']);
            return $navgiation;
        }
        return null;
    }

    public function getChildrenCategories()
    {
        if (isset($this->storage['id'])) {
            $collection = new Collection();
            $collection->where(['parent_id' => $this->storage['id']]);
            return $collection;
        }
        return [];
    }

    public function getUrl()
    {
        return $this->getBaseUrl(($this->getContainer()->get('config')['forum/general/uri_key'] ?: '') .
                        '/' . $this->storage['uri_key'] . '.html');
    }

    public function getName()
    {
        return $this->storage['name'][Bootstrap::getLanguage()->getId()] ?? '';
    }

    public function getPosts()
    {
        if (isset($this->storage['id'])) {
            $pages = new PostCollection();
            $pages->where([
                'category_id' => $this->storage['id'],
                'language_id' => Bootstrap::getLanguage()->getId()
            ]);
            return $pages;
        }
        return [];
    }

    protected function beforeSave()
    {
        $this->storage['uri_key'] = rawurlencode($this->storage['uri_key']);
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        parent::afterSave();
        if (isset($this->storage['name'])) {
            $tableGateway = $this->getTableGateway('forum_category_language');
            foreach ((array) $this->storage['name'] as $languageId => $name) {
                $this->upsert(['name' => $name], ['category_id' => $this->getId(), 'language_id' => $languageId], $tableGateway);
            }
        }
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('forum_category_language', 'forum_category_language.category_id=forum_category.id', ['name'], 'left');
        $select->join('core_language', 'forum_category_language.language_id=core_language.id', ['language_id' => 'id', 'language' => 'name'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0])) {
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
