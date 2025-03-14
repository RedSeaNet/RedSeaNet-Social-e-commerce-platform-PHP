<?php

namespace Redseanet\Forum\Model\Post;

use Redseanet\Customer\Model\Customer;
use Redseanet\Forum\Model\Post;
use Redseanet\Forum\Model\Category;
use Redseanet\Lib\Exception\SpamException;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Increment;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;

class Review extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected function construct()
    {
        $this->init('forum_post_review', 'id', ['id', 'post_id', 'customer_id', 'subject', 'content', 'temp_content', 'reference', 'anonymous', 'like', 'dislike', 'status']);
    }

    public function getLikeCount()
    {
        if ($this->getId()) {
            $tableGateway = $this->getTableGateway('forum_like');
            $select = $tableGateway->getSql()->select();
            $select->columns(['count' => new Expression('count(1)')])
                    ->group('review_id')
                    ->where(['review_id' => $this->getId()]);
            $result = $tableGateway->selectWith($select)->toArray();
            return (int) ($result[0]['count'] ?? 0);
        }
        return 0;
    }

    public function getDislikeCount()
    {
        if ($this->getId()) {
            $tableGateway = $this->getTableGateway('forum_dislike');
            $select = $tableGateway->getSql()->select();
            $select->columns(['count' => new Expression('count(1)')])
                    ->group('review_id')
                    ->where(['review_id' => $this->getId()]);
            $result = $tableGateway->selectWith($select)->toArray();
            return (int) ($result[0]['count'] ?? 0);
        }
        return 0;
    }

    public function liked($id)
    {
        return (bool) count($this->getTableGateway('forum_dislike')->select(['review_id' => $this->getId(), 'customer_id' => $id])->toArray());
    }

    public function like($id)
    {
        $ref = new static();
        $ref->load($this->getId());
        $author_id = $ref->storage['customer_id'];
        $post_id = $ref->storage['post_id'];
        if ($this->liked($id)) {
            $this->getTableGateway('forum_like')->delete(['review_id' => $this->getId(), 'customer_id' => $id]);
        } else {
            $this->getTableGateway('forum_like')->insert(['post_id' => $post_id, 'review_id' => $this->getId(), 'customer_id' => $id, 'author_id' => $author_id]);
        }
        $this->setData('like', $this->getLikeCount())->save();
        return $this->storage['like'];
    }

    public function disliked($id)
    {
        return (bool) count($this->getTableGateway('forum_dislike')->select(['review_id' => $this->getId(), 'customer_id' => $id])->toArray());
    }

    public function dislike($id)
    {
        if ($this->disliked($id)) {
            $this->getTableGateway('forum_dislike')->delete(['review_id' => $this->getId(), 'customer_id' => $id]);
        } else {
            $this->getTableGateway('forum_dislike')->insert(['review_id' => $this->getId(), 'customer_id' => $id]);
        }
        $this->setData('dislike', $this->getDislikeCount())->save();
        return $this->storage['dislike'];
    }

    public function getPost()
    {
        $post = new Post();
        $post->load($this->storage['post_id']);
        return $post;
    }

    public function getReference()
    {
        if (!empty($this->storage['reference'])) {
            $ref = new static();
            $ref->load($this->storage['reference']);
            return $ref;
        }
        return null;
    }

    public function getCustomer()
    {
        $customer = new Customer();
        $customer->load($this->storage['customer_id']);
        return $customer;
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['id'])) {
            $data = @gzdecode($result[0]['content']);
            if ($data !== false) {
                $result[0]['content'] = $data;
            }
        }
        parent::afterLoad($result);
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        $result = parent::isUpdate($constraint, $insertForce);
        if (!empty($this->storage['content'])) {
            if (!empty($this->storage['subject'])) {
                $this->storage['subject'] = htmlspecialchars($this->storage['subject'], ENT_QUOTES | ENT_HTML5);
            }
            $this->storage['tmpcontent'] = $this->getContainer()->get('htmlpurifier')->purify($this->storage['content']);
            if ($result && !$this->getContainer()->get('config')['forum/review/status']) {
                $this->setData('temp_content', gzencode($this->storage['tmpcontent']));
                unset($this->storage['content']);
            } else {
                $this->storage['content'] = gzencode($this->storage['tmpcontent']);
            }
        }
        if (!$result && !$this->getId()) {
            $increment = new Increment();
            $increment->load($this->tableName);
            $this->setId($increment->getIncrementId());
        }
        return $result;
    }

    protected function afterSave()
    {
        if ($this->isNew && !empty($this->storage['post_id'])) {
            $select = new Select($this->tableName);
            $select->columns(['count' => new Expression('count(1)')])
                    ->group('post_id')
                    ->where(['post_id' => $this->storage['post_id']]);
            $this->getPost()->setData('reviews', $select)->save();
        }
        parent::afterSave();
    }

    public function getCategory()
    {
        $category = new Category();
        $category->load($this->getPost()->storage['category_id']);
        return $category;
    }

    public function getUrl()
    {
        return $this->getBaseUrl(($this->getContainer()->get('config')['forum/general/uri_key'] ?: '') .
                        ($this->getCategory()['uri_key'] ? '/' . $this->getCategory()['uri_key'] : '') . '/' . $this->storage['uri_key'] . '.html');
    }
}
