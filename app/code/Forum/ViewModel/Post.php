<?php

namespace Redseanet\Forum\ViewModel;

use Redseanet\Forum\Model\Collection\Post as PostCollection;
use Redseanet\Forum\Model\Post as PModel;
use Redseanet\Forum\Model\Collection\Category as CategoryCollection;
use Redseanet\Catalog\Model\Collection\Category as ProductCategory;
use Redseanet\Forum\Model\Collection\Post\Like;
use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Forum\Model\Collection\Post\Favorite;
use Redseanet\Forum\Model\Collection\CustomerLike as customerLikeCollection;
use Redseanet\Lib\Bootstrap;
use DateTime;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Forum\Model\Collection\Tags as TagsCollection;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Redseanet\Forum\Model\Poll as PollModel;
use Redseanet\Resource\Lib\Factory as resourceFactory;

class Post extends Template
{
    use \Redseanet\Lib\Traits\Filter;

    protected $posts = null;
    protected $products = null;
    protected $drafts = null;
    protected $current = null;
    protected $likes = null;
    protected $users = null;
    protected $collected = null;
    protected $id = null;
    protected $customer_id = null;
    protected $action = [];

    protected function getCurrent()
    {
        if (is_null($this->current)) {
            $this->current = new DateTime();
        }
        return $this->current;
    }

    public function getTime($time)
    {
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

    public function getReviews()
    {
        $post = $this->getVariable('post');
        $reviews = $post->getReviews();
        $reviews->order('created_at DESC')->where->greaterThan('status', 0);
        $query = $this->getQuery();
        if (!empty($query['author'])) {
            $reviews->where(['customer_id' => $post['customer_id']]);
        }
        $this->filter($reviews, ['page' => $query['page'] ?? 1]);
        return $reviews;
    }

    public function getReferences()
    {
        $post = $this->getVariable('post');
        $reviews = $post->getReviews();
        $reviews->order('created_at ASC')->where->greaterThan('reference', 0);
        $query = $this->getQuery();
        if (!empty($query['author'])) {
            $reviews->where(['customer_id' => $post['customer_id']]);
        }
        $this->filter($reviews, ['page' => $query['page'] ?? 1]);
        return $reviews;
    }

    public function getPosts()
    {
        $segment = new Segment('customer');
        $customerId = $segment->get('hasLoggedIn') ? $segment->get('customer')['id'] : null;
        if (is_null($this->posts)) {
            $views = new Select('log_visitor');
            $views->columns(['count' => new Expression('count(1)')])
                    ->group('post_id')
            ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');
            if ($customerId != null) {
                $likedSelect = new Select();
                $likedSelect->from('forum_like');
                $likedSelect->columns(['liked' => new Expression('count(forum_like.post_id)')]);
                $likedSelect->where('`forum_like`.`customer_id`=' . $customerId . ' and `forum_like`.`post_id`=`forum_post`.`id`');
                $favoritedSelect = new Select();
                $favoritedSelect->from('forum_post_favorite');
                $favoritedSelect->columns(['favorited' => new Expression('count(forum_post_favorite.post_id)')]);
                $favoritedSelect->where('`forum_post_favorite`.`customer_id`=' . $customerId . ' and `forum_post_favorite`.`post_id`=`forum_post`.`id`');
                $this->posts = new PostCollection();
                $this->posts->columns(['*', 'views' => $views, 'liked' => $likedSelect, 'favorited' => $favoritedSelect])
                        ->where(['is_draft' => 0])
                        ->order('is_top DESC')
                ->where->greaterThan('status', 0);
            } else {
                $this->posts = new PostCollection();
                $this->posts->columns(['*', 'views' => $views])
                        ->where(['is_draft' => 0])
                        ->order('is_top DESC')
                ->where->greaterThan('status', 0);
            }
            //echo $this->posts->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
            $searchQuery = [];
            $query = $this->getQuery();
            $allowSearchColumn = ['id', 'customer_id', 'category_id',
                'language_id', 'status', 'anonymous', 'uri_key', 'poll_id', 'product_id',
                'images', 'videos', 'title', 'description', 'content', 'like', 'dislike',
                'reviews', 'collections', 'is_top', 'is_hot', 'is_draft', 'is_relate', 'can_review', 'original_videos', 'videos_screenshot', 'tags', 'page'];
            foreach ($this->getQuery() as $key => $value) {
                if (in_array($key, $allowSearchColumn)) {
                    $searchQuery[$key] = $value;
                }
            }
            if (isset($query['q']) && $query['q'] != '') {
                $this->posts->where('title like "%' . $query['q'] . '%" and tags like "%' . $query['q'] . '%"');
            }

            if ($this->getVariable('category', false)) {
                $this->posts->where(['category_id' => $this->getVariable('category')->getId()]);
            } elseif ($this->getVariable('category_id', false)) {
                $this->posts->where(['category_id' => $this->getVariable('category_id')]);
            } elseif (!empty($query['category_id'])) {
                $this->posts->where(['category_id' => $query['category_id']]);
            }

            if (!empty($query['product_id'])) {
                $this->posts->where(['product_id' => $query['product_id']]);
            }

            unset($query['category_id'], $query['product_id'], $query['status'], $query['q'], $query['is_json'], $query['lastId']);
            if (!isset($query['asc']) && !isset($query['desc'])) {
                $this->posts->order('created_at DESC');
            }
            //echo $this->posts->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
            $this->filter($this->posts, $searchQuery);
        }
        return $this->posts;
    }

    public function getDrafts()
    {
        if (is_null($this->posts)) {
            $views = new Select('log_visitor');
            $views->columns(['count' => new Expression('count(1)')])
                    ->group('post_id')
            ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');
            $this->posts = new PostCollection();
            $this->posts->columns(['*', 'views' => $views])
                    ->where(['is_draft' => 1])
                    ->order('is_top DESC')
            ->where->greaterThan('status', 0);
            $query = $this->getQuery();
            if (!isset($query['asc']) && !isset($query['desc'])) {
                $this->posts->order('created_at DESC');
            }
            $this->filter($this->posts, $query);
        }
        return $this->posts;
    }

    public function getUsers()
    {
        if (is_null($this->posts)) {
            $views = new Select('customer_1_index');
            $views->columns(['count' => new Expression('count(1)')])
                    ->group('id')
            ->where->equalTo('id', 'forum_post.customer_id', 'identifier', 'identifier');
            $this->posts = new PostCollection();
            $this->posts->columns(['*', 'views' => $views])
                    ->where(['language_id' => Bootstrap::getLanguage()->getId(), ])
                    ->order('id DESC')
            ->where->greaterThan('status', 0);
        }
        return $this->posts;
    }

    public function getLikeCount()
    {
        $likes = new Select('forum_post');
        $likes->columns(['count' => new Expression('count(1)')])
                ->group('id')
        ->where->equalTo(['customer_id', 'forum_like.customer_id'], 'identifier', 'identifier');
        $this->likes = new Like();
        $this->likes->columns(['*', 'likes' => $likes])
                ->where(['customer_id' => $this->getSegment('customer')->get('customer')['id']]);
        $count = count($this->likes) ?? 0;
        return $count;
    }

    public function getCollected($customer_id = null)
    {
        if (empty($customer_id)) {
            $customer = (new Segment('customer'))->get('customer');
            $customer_id = $customer['id'];
        }
        if (is_null($customer_id)) {
            return null;
        }
        $query = $this->getQuery();
        $liked = new PostCollection();
        $my_likes = $liked->getCollected($customer_id);
        $this->filter($my_likes, $query);
        return $my_likes;
    }

    public function getCategories()
    {
        $categories = new CategoryCollection();
        $categories->withName();
        return $categories;
    }

    public function getRootCategory()
    {
        $categories = new ProductCategory();
        $categories->where(['parent_id' => null]);
        if (count($categories)) {
            return $categories[0];
        }
        return [];
    }

    public function getLinkedProducts()
    {
        $post = new PModel();
        $post->load($this->getQuery('id'));
        $products = $post->getLinkedProducts();
        if ($products) {
            return $products;
        }
        return [];
    }

    public function getActiveIds()
    {
        $collection = (new PModel())->setId($this->getRequest()->getQuery('id'))
                ->getLinkedProducts();
        $old = $this->getSegment('forum')->get('forum_product_relation', false);
        if ($old === false) {
            $activeIds = [];
        } else {
            $activeIds[] = $old['forum_product_relation'];
        }
        if (count($collection)) {
            foreach ($collection->toArray() as $item) {
                if ($old === false || !in_array($item['product_id'], $old['remove'])) {
                    $activeIds[] = $item['product_id'];
                }
            }
        }
        return $activeIds;
    }

    public function getSelfPosts()
    {
        $post = new PModel();
        $post->load($this->getQuery('id'));
        $posts = $post->getSelfPosts();
        if ($posts) {
            return $posts;
        }
        return [];
    }

    public function getCategoryRelatePosts($category_id)
    {
        $post = new PModel();
        $post->load($this->getQuery('id'));
        $posts = $post->getCategoryRelatePosts($category_id);
        if ($posts) {
            return $posts;
        }
        return [];
    }

    public function getCategoryUnLikeCustomer($category_id, $exclude = [], $random = 3)
    {
        if (empty($category_id)) {
            return [];
        }
        $customers = new Customer();
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedIn')) {
            $customer_id = $segment->get('customer')['id'];
            if (!in_array($customer_id, $exclude)) {
                $exclude[] = $customer_id;
            }
            $sub1 = new Select('forum_post');
            $sub1->columns(['customer_id']);
            $sub2 = new Select('forum_customer_like');
            $sub2->columns(['customer_id'])->where(['customer_id' => $customer_id]);
            $sub1->where(['category_id' => $category_id])
                    ->where
                    ->notIn('customer_id', $sub2)
                    ->notIn('customer_id', $exclude);
            $sub1->group('customer_id');
        } else {
            $sub1 = new Select('forum_post');
            $sub1->columns(['customer_id']);
            $sub1->where(['category_id' => $category_id]);
            $sub1->group('customer_id');
        }
        $customers->in('id', $sub1)->order(new Expression('Rand()'))->limit($random);
        return $customers;
    }

    public function getFollowCount($id = null)
    {
        $id = $this->getQuery();
        $customer_id = $id['customer_id'] ?? (new Segment('customer'))->get('customer')['id'];
        $like_customer = new customerLikeCollection();
        $like_customer->columns(['count' => new Expression('count(1)')])
                ->where(['customer_id' => $customer_id]);
        return $like_customer->toArray()[0]['count'];
    }

    public function getNewFollowCount($id = null)
    {
        $id = $this->getQuery();
        $customer_id = $id['customer_id'] ?? (new Segment('customer'))->get('customer')['id'];
        $fans = new customerLikeCollection();
        $fans->columns(['count' => new Expression('count(1)')])
                ->where(['customer_id' => $customer_id, 'is_new' => 1]);
        return $fans->toArray()[0]['count'];
    }

    public function getFansCount($id = null)
    {
        $id = $this->getQuery();
        $customer_id = $id['customer_id'] ?? (new Segment('customer'))->get('customer')['id'];
        $fans = new customerLikeCollection();
        $fans->columns(['count' => new Expression('count(1)')])
                ->where(['like_customer_id' => $customer_id]);
        return $fans->toArray()[0]['count'];
    }

    public function getNewFansCount($id = null)
    {
        $id = $this->getQuery();
        $customer_id = $id['customer_id'] ?? (new Segment('customer'))->get('customer')['id'];
        $fans = new customerLikeCollection();
        $fans->columns(['count' => new Expression('count(1)')])
                ->where(['like_customer_id' => $customer_id, 'is_new' => 1]);
        return $fans->toArray()[0]['count'];
    }

    public function getBeLikeCount($id = null)
    {
        $id = $this->getQuery();
        $customer_id = $id['customer_id'] ?? (new Segment('customer'))->get('customer')['id'];
        $beLikes = new Like();
        $beLikes->columns(['count' => new Expression('count(1)')])
                ->where(['author_id' => $customer_id]);
        return $beLikes->toArray()[0]['count'];
    }

    public function getNewBeLikeCount($id = null)
    {
        $id = $this->getQuery();
        $customer_id = $id['customer_id'] ?? (new Segment('customer'))->get('customer')['id'];
        $like = new Like();
        $like->columns(['count' => new Expression('count(1)')])
                ->where(['author_id' => $customer_id, 'is_new_be_like' => 1]);
        return $like->toArray()[0]['count'];
    }

    public function getBefollowCount($id = null)
    {
        $id = $this->getQuery();
        $customer_id = $id['customer_id'] ?? (new Segment('customer'))->get('customer')['id'];
        $beFollows = new Favorite();
        $beFollows->getBeCollectedCount($customer_id);
        return $beFollows->toArray()[0]['count'];
    }

    public function getNewBefollwCount($customer_id = null)
    {
        $id = $this->getQuery();
        $customer_id = $id['customer_id'] ?? (new Segment('customer'))->get('customer')['id'];
        $favorite = new Favorite();
        $favorite->getBeCollectedCount($customer_id, 1);
        return $favorite->toArray()[0]['count'];
    }

    public function getDynamic($customer_id = null)
    {
        if (empty($customer_id)) {
            $customer = (new Segment('customer'))->get('customer');
            $customer_id = $customer['id'];
        }
        if (is_null($customer_id)) {
            return null;
        }
        $post = new PostCollection();
        $post->where(['is_draft' => 0]);
        $posts = $post->getDynamic($customer_id);
        return $posts;
    }

    public function getSystemRecommendedTags()
    {
        $tags = new TagsCollection();
        $tags->withName();
        $tags->where(['sys_recommended' => 1]);
        return $tags;
    }

    public function getLinks()
    {
        $post = $this->getVariable('post');
        $links = $post->getLinks();
        $links->order('created_at ASC');
        return $links;
    }

    public function checkLink($url)
    {
        $preg = "/http[s]?:\/\/[\w.]+[\w\/]*[\w.]*\??[\w=&\+\%]*/is";
        $lowerUrl = strtolower($url);
        $returnUrl = '';
        if (preg_match($preg, $url)) {
            $returnUrl = $lowerUrl;
        } else {
            $returnUrl = 'http://' . $lowerUrl;
        }
        return $returnUrl;
    }

    public function getPoll($poll_id)
    {
        $poll = new PollModel();
        $poll->load(intval($poll_id));
        return $poll;
    }

    public function customerFollowed($customer_id, $like_customer_id)
    {
        $customerLike = new customerLikeCollection();
        $customerLike->where(['customer_id' => $customer_id, 'like_customer_id' => $like_customer_id]);
        $customerLike->load(true, true);
        return count($customerLike) ? true : false;
    }
}
