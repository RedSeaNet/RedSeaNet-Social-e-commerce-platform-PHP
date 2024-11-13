<?php

namespace Redseanet\Message\Model;

use Redseanet\Lib\Model\AbstractModel;

class Template extends AbstractModel
{
    protected function construct()
    {
        $this->init('message_template', 'id', ['id', 'code', 'content']);
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
            $tableGateway = $this->getTableGateway('message_template_language');
            $tableGateway->delete(['template_id' => $this->getId()]);
            foreach ($this->storage['language_id'] as $languageId) {
                $tableGateway->insert(['template_id' => $this->getId(), 'language_id' => $languageId]);
            }
        }
        parent::afterSave();
        $this->commit();
    }

    protected function beforeLoad($select)
    {
        $select->join('message_template_language', 'message_template_language.template_id=message_template.id', [], 'left');
        $select->join('core_language', 'email_template_language.language_id=core_language.id', ['language_id' => 'id', 'language' => 'name'], 'left');
        parent::beforeLoad($select);
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['id'])) {
            $language = [];
            foreach ($result as $item) {
                $language[$item['language_id']] = $item['language'];
            }
            $result[0]['language'] = $language;
            $data = @gzdecode($result[0]['content']);
            if ($data !== false) {
                $result[0]['content'] = $data;
            }
        }
        parent::afterLoad($result);
    }
}
