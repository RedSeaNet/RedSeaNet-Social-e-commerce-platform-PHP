<?php

namespace Redseanet\Cms\Model;

use Redseanet\Lib\Model\AbstractModel;

class Block extends AbstractModel
{
    public function construct()
    {
        $this->init('cms_block', 'id', ['id', 'store_id', 'status', 'code', 'content']);
    }

    protected function beforeSave()
    {
        $this->storage['content'] = gzencode($this->storage['content']);
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        if (isset($this->storage['language_id'])) {
            $tableGateway = $this->getTableGateway('cms_block_language');
            $tableGateway->delete(['block_id' => $this->getId()]);
            foreach ($this->storage['language_id'] as $languageId) {
                $tableGateway->insert(['block_id' => $this->getId(), 'language_id' => $languageId]);
            }
        }
        parent::afterSave();
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('cms_block_language', 'cms_block_language.block_id=cms_block.id', [], 'left');
        $select->join('core_language', 'cms_block_language.language_id=core_language.id', ['language_id' => 'id', 'language' => 'name'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0])) {
            $language = [];
            foreach ($result as $item) {
                $language[$item['language_id']] = $item['language'];
            }
            $result[0]['language'] = $language;
            $result[0]['language_id'] = array_keys($language);
            $data = @gzdecode($result[0]['content']);
            if ($data !== false) {
                $result[0]['content'] = $data;
            }
        }
        parent::afterLoad($result);
    }
}
