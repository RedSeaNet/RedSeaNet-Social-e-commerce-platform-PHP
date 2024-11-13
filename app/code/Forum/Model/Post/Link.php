<?php

namespace Redseanet\Forum\Model\Post;

use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Model\AbstractModel;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Model\Increment;

class Link extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected function construct()
    {
        $this->init('forum_post_recommend_link', 'id', ['id', 'post_id', 'name', 'link']);
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
}
