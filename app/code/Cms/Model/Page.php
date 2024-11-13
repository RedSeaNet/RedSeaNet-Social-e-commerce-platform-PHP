<?php

namespace Redseanet\Cms\Model;

use Redseanet\Cms\Model\Collection\Category as CategoryCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Resource\Model\Collection\Resource;
use Laminas\Db\Sql\Select;

class Page extends AbstractModel
{
    public function construct()
    {
        $this->init('cms_page', 'id', ['id', 'store_id', 'status', 'uri_key', 'title', 'keywords', 'description', 'thumbnail', 'image', 'content']);
    }

    protected function beforeSave()
    {
        $this->storage['uri_key'] = !empty($this->storage['uri_key']) ? rawurlencode($this->storage['uri_key']) : '';
        $this->storage['content'] = gzencode($this->storage['content']);
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if (isset($this->storage['language_id'])) {
            $tableGateway = $this->getTableGateway('cms_page_language');
            $tableGateway->delete(['page_id' => $this->getId()]);
            foreach ($this->storage['language_id'] as $languageId) {
                $tableGateway->insert(['page_id' => $this->getId(), 'language_id' => $languageId]);
            }
        }
        $tableGateway = $this->getTableGateway('cms_category_page');
        $tableGateway->delete(['page_id' => $this->getId()]);
        if (isset($this->storage['category_id']) && is_array($this->storage['category_id']) && count($this->storage['category_id']) > 0) {
            foreach ($this->storage['category_id'] as $category_id) {
                if (!empty($category_id)) {
                    $tableGateway->insert(['page_id' => $this->getId(), 'category_id' => intval($category_id)]);
                }
            }
        }
        parent::afterSave();
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('cms_page_language', 'cms_page_language.page_id=cms_page.id', [], 'left');
        $select->join('core_language', 'cms_page_language.language_id=core_language.id', ['language_id' => 'id', 'language' => 'name'], 'left');
        $select->join('cms_category_page', 'cms_category_page.page_id=cms_page.id', [], 'left');
        $select->join('cms_category', 'cms_category.id=cms_category_page.category_id', ['category_id' => 'id'], 'left');
        $select->join('cms_category_language', 'cms_category.id=cms_category_language.category_id', ['category' => 'name'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['id'])) {
            $language = [];
            $category = [];
            foreach ($result as $item) {
                $language[$item['language_id']] = $item['language'];
                $category[$item['category_id']] = $item['category'];
            }
            $result[0]['language'] = $language;
            $result[0]['language_id'] = array_keys($language);
            $result[0]['category'] = $category;
            $result[0]['category_id'] = array_keys($category);
            $data = @gzdecode($result[0]['content']);
            if ($data !== false) {
                $result[0]['content'] = $data;
            }
        }
        parent::afterLoad($result);
    }

    public function getCategories()
    {
        if ($this->getId()) {
            $collection = new CategoryCollection();
            $collection->in('id', (new Select('cms_category_page'))
                            ->columns(['category_id'])
                            ->where(['page_id' => $this->getId()]));
            return $collection;
        }
        return [];
    }

    public function getImage()
    {
        if (!empty($this->storage['image'])) {
            $collection = new Resource();
            $collection->where(['id' => $this->storage['image']]);
            return $collection;
        }
        return null;
    }

    public function getThumbnail()
    {
        if (!empty($this->storage['thumbnail'])) {
            $collection = new Resource();
            $collection->where(['id' => $this->storage['thumbnail']]);
            return $collection;
        }
        return null;
    }

    public function getUrl($category = null)
    {
        if ($this->getId()) {
            $constraint = ['page_id' => $this->getId()];
            if (!is_null($category)) {
                $constraint['category_id'] = $category['id'];
            }
            $result = $this->getContainer()->get('indexer')->select('cms_url', Bootstrap::getLanguage()->getId(), $constraint);
            if ($result) {
                return $result[0]['path'];
            }
        }
        return '';
    }
}
