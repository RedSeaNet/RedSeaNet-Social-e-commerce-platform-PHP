<?php

namespace Redseanet\Banner\Model;

use Redseanet\Lib\Model\AbstractModel;

class Banner extends AbstractModel
{
    public function construct()
    {
        $this->init('banner', 'id', ['id', 'store_id', 'status', 'code', 'url', 'app_url', 'mini_program_url', 'sort_order']);
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        $this->storage['sort_order'] = !empty($this->storage['sort_order']) ? $this->storage['sort_order'] : 0;
        parent::beforeSave();
    }

    protected function afterSave()
    {
        parent::afterSave();
        if (isset($this->storage['title'])) {
            $tableGateway = $this->getTableGateway('banner_language');
            foreach ((array) $this->storage['title'] as $languageId => $title) {
                $this->upsert(['title' => $title, 'content' => $this->storage['content'][$languageId], 'image' => $this->storage['image'][$languageId]], ['banner_id' => $this->getId(), 'language_id' => $languageId], $tableGateway);
            }
        }
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('banner_language', 'banner_language.banner_id=banner.id', ['title', 'content', 'image'], 'left');
        $select->join('core_language', 'banner_language.language_id=core_language.id', ['language_id' => 'id', 'language' => 'name'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0])) {
            $language = [];
            $title = [];
            $content = [];
            $image = [];
            foreach ($result as $item) {
                $language[$item['language_id']] = $item['language'];
                $title[$item['language_id']] = $item['title'];
                $content[$item['language_id']] = $item['content'];
                $image[$item['language_id']] = $item['image'];
            }
            $result[0]['language'] = $language;
            $result[0]['language_id'] = array_keys($language);
            $result[0]['title'] = $title;
            $result[0]['content'] = $content;
            $result[0]['image'] = $image;
        }
        parent::afterLoad($result);
    }
}
