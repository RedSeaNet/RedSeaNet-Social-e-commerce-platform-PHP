<?php

namespace Redseanet\Forum\ViewModel;

use Redseanet\Forum\Model\Collection\Post;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Template;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Redseanet\Lib\Session\Segment;
use Redseanet\Customer\Model\Collection\Customer as CustomerCollection;
use Redseanet\Forum\Model\Collection\Category as CategoryCollection;
class Category extends Template {

    use \Redseanet\Lib\Traits\Filter;

    protected $posts = null;
    protected $bannedMember = ['query', 'posts'];
    protected $current = null;

    protected function getCurrent() {
        if (is_null($this->current)) {
            $this->current = new DateTime();
        }
        return $this->current;
    }

    public function getTime($time) {
        $dt = new DateTime($time);
        $days = $dt->diff($this->getCurrent())->format('%a');
        if ($days && $days > 1) {
            return $this->translate('%d Days Ago', [$days]);
        } elseif ((int) $days == 1) {
            return $this->translate('Yesterday %d', [$dt->format('H')]);
        } else {
            return $this->translate('Today') . $dt->format('H:i');
        }
    }

    public function getPosts() {
        $segment = new Segment('customer');
        $customerId = $segment->get('hasLoggedIn') ? $segment->get('customer')['id'] : null;
        if (is_null($this->posts)) {
            $views = new Select('log_visitor');
            $views->columns(['count' => new Expression('count(1)')])
                    ->group('post_id')
            ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');
            $this->posts = new Post();
            if ($segment->get('hasLoggedIn')) {
                $likedSelect = new Select();
                $likedSelect->from('forum_like');
                $likedSelect->columns(['liked' => new Expression('count(forum_like.post_id)')]);
                $likedSelect->where('`forum_like`.`customer_id`=' . $customerId . ' and `forum_like`.`post_id`=`forum_post`.`id`');

                $favoritedSelect = new Select();
                $favoritedSelect->from('forum_post_favorite');
                $favoritedSelect->columns(['favorited' => new Expression('count(forum_post_favorite.post_id)')]);
                $favoritedSelect->where('`forum_post_favorite`.`customer_id`=' . $customerId . ' and `forum_post_favorite`.`post_id`=`forum_post`.`id`');

                $this->posts->columns(['*', 'views' => $views, 'liked' => $likedSelect, 'favorited' => $favoritedSelect])
                        ->order('is_top DESC')
                ->where->greaterThan('status', 0);
            } else {
                $this->posts->columns(['*', 'views' => $views])
                        ->order('is_top DESC')
                ->where->greaterThan('status', 0);
            }
            $query = $this->getQuery();
            if ($this->getVariable('category', false)) {
                $this->posts->where(['category_id' => $this->getVariable('category')['id']]);
            } elseif ($this->getVariable('category_id', false)) {
                $this->posts->where(['category_id' => $this->getVariable('category_id')]);
            } elseif (!empty($query['category_id'])) {
                $this->posts->where(['category_id' => $query['category_id']]);
            }
            if (!empty($query['product_id'])) {
                $this->posts->where(['product_id' => $query['product_id']]);
            }
            unset($query['category_id'], $query['product_id'], $query['status'], $query['is_json']);
            if (!isset($query['asc']) && !isset($query['desc'])) {
                $this->posts->order('created_at DESC');
            }
            $this->filter($this->posts, $query);
        }
        return $this->posts;
    }

    public function getRandCustomer($exclude = [], $random = 5) {
        $customers = new CustomerCollection();
        $segment = new Segment('customer');
        $customer_id = $segment->get('customer')->getId();
        if (!in_array($customer_id, $exclude)) {
            $exclude[] = $customer_id;
        }
        $sub1 = new Select('forum_customer_like');
        $sub1->columns(['customer_id'])->where(['customer_id' => $customer_id]);
        $customersSelect = $customers->getSelect();
        $customersSelect->where->notIn('id', $sub1)->notIn('id', $exclude);
        $customers->where(['status' => 1]);
        $customers->order(new Expression('Rand()'))->limit($random);

        return $customers;
    }

    public function getCategories($systemRecomment=false) {
        $categories = new CategoryCollection();
        //$categories->where(['status' => 1]);
        $categories->where(['parent_id' => 1]);
        $categories->order('sort_order desc');
        $categories->withName();

        return $categories;
    }
}
