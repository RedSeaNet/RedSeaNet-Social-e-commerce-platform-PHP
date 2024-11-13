<?php

namespace Redseanet\Forum\Model;

use Redseanet\Forum\Model\Collection\Category as Collection;
use Redseanet\Forum\Model\Collection\Post as PostCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\AbstractModel;

class Tags extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected function construct()
    {
        $this->init('forum_post_tags', 'id', ['id', 'sys_recommended', 'sort_order']);
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
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        parent::afterSave();
        if (isset($this->storage['name'])) {
            $tableGateway = $this->getTableGateway('forum_post_tags_language');
            foreach ((array) $this->storage['name'] as $languageId => $name) {
                $this->upsert(['name' => $name], ['forum_post_tags_id' => $this->getId(), 'language_id' => $languageId], $tableGateway);
            }
        }
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('forum_post_tags_language', 'forum_post_tags_language.forum_post_tags_id=forum_post_tags.id', ['name'], 'left');
        $select->join('core_language', 'forum_post_tags_language.language_id=core_language.id', ['language_id' => 'id', 'language' => 'name'], 'left');
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
