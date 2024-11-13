<?php

namespace Redseanet\Forum\Model\Post;

use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Increment;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class RecommendLink extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected function construct()
    {
        $this->init('forum_post_recommend_link', 'id', ['id', 'post_id', 'name', 'link']);
    }

    protected function afterLoad(&$result)
    {
        parent::afterLoad($result);
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        $result = parent::isUpdate($constraint, $insertForce);
        if (!$result && !$this->getId()) {
            $increment = new Increment();
            $increment->load($this->tableName);
            $this->setId($increment->getIncrementId());
        }
        return $result;
    }

    protected function afterSave()
    {
        parent::afterSave();
    }
}
