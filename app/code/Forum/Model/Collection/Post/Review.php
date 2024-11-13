<?php

namespace Redseanet\Forum\Model\Collection\Post;

use Redseanet\Lib\Model\AbstractCollection;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;

class Review extends AbstractCollection
{
    protected function construct()
    {
        $this->init('forum_post_review');
    }

    protected function afterLoad(&$result)
    {
        foreach ($result as $key => $item) {
            $content = @gzdecode($item['content']);
            if ($content !== false) {
                $result[$key]['content'] = $content;
            }
        }
        parent::afterLoad($result);
    }

    public function getMyReviews($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $sub1 = new Select('forum_post');
        $sub1->columns(['id']);
        $sub1->where(['customer_id' => $customer_id]);
        $sub2 = new Select('forum_post_review');
        $sub2->columns(['id']);
        $sub2_where = new Where();
        $sub2_where->notIn('post_id', $sub1)->equalTo('customer_id', $customer_id);
        $sub2->where($sub2_where);
        $where = new Where();
        $where->in('post_id', $sub1)->or->in('reference', $sub2);
        $this->select
                ->join('forum_post', 'forum_post.id = post_id', ['title'], 'left')
                ->where($where)
                ->order('created_at DESC');
        return $this;
    }

    public function getMyReferences($customer_id = null)
    {
        if (is_null($customer_id)) {
            return $this;
        }
        $sub1 = new Select('forum_post');
        $sub1->columns(['id']);
        $sub1->where(['customer_id' => $customer_id]);
        $sub2 = new Select('forum_post_review');
        $sub2->columns(['id']);
        $sub2_where = new Where();
        $sub2_where->notIn('post_id', $sub1)->equalTo('customer_id', $customer_id);
        $sub2->where($sub2_where);
        $where = new Where();
        $where->in('post_id', $sub1)->and->notEqualTo('reference', 0);
        $this->select
                ->join('forum_post', 'forum_post.id = post_id', ['title'], 'left')
                ->where($where)
                ->order('created_at DESC');
        return $this;
    }
}
