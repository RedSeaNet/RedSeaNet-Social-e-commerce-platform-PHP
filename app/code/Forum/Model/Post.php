<?php

namespace Redseanet\Forum\Model;

use Redseanet\Customer\Model\Customer;
use Redseanet\Forum\Model\Category;
use Redseanet\Forum\Indexer\Search as Indexer;
use Redseanet\Forum\Model\Collection\Post as Collection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Exception\SpamException;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Model\Increment;
use Redseanet\Search\Model\Factory;
use Laminas\Db\Sql\Expression;
use Redseanet\Forum\Model\Collection\CustomerLike as customerLikeCollection;
use Redseanet\Catalog\Model\Collection\Product as productCollection;

class Post extends AbstractModel
{
    use \Redseanet\Lib\Traits\Url;

    protected $customer = null;

    protected function construct()
    {
        $this->init('forum_post', 'id', ['id', 'customer_id', 'category_id',
            'language_id', 'status', 'anonymous', 'uri_key', 'poll_id', 'product_id',
            'images', 'videos', 'title', 'description', 'content', 'like', 'dislike',
            'reviews', 'collections', 'is_top', 'is_hot', 'is_draft', 'is_relate', 'can_review', 'original_videos', 'videos_screenshot', 'tags']);
    }

    public function getCustomer()
    {
        if (is_null($this->customer)) {
            $this->customer = new Customer($this->storage['language_id']);
            $this->customer->load($this->storage['customer_id']);
        }
        return $this->customer;
    }

    public function getCategory()
    {
        $category = new Category();
        $category->load($this->storage['category_id']);
        return $category;
    }

    public function getReviews()
    {
        if ($this->getId()) {
            $reviews = new \Redseanet\Forum\Model\Collection\Post\Review();
            $reviews->where(['post_id' => $this->getId()]);
        }
        return $reviews ?? [];
    }

    public function getPoll()
    {
        if (!empty($this->storage['poll_id'])) {
            $poll = new Poll();
            $poll->load($this->storage['poll_id']);
            return $poll;
        }
        return null;
    }

    public function getUrl()
    {
        return $this->getBaseUrl(($this->getContainer()->get('config')['forum/general/uri_key'] ?: '') .
                        ($this->getCategory()['uri_key'] ? '/' . $this->getCategory()['uri_key'] : '') . '/' . $this->storage['uri_key'] . '.html');
    }

    public function getLikeCount()
    {
        if ($this->getId()) {
            $tableGateway = $this->getTableGateway('forum_like');
            $select = $tableGateway->getSql()->select();
            $select->columns(['count' => new Expression('count(1)')])
                    ->group('post_id')
                    ->where(['post_id' => $this->getId(), 'review_id' => null]);
            $result = $tableGateway->selectWith($select)->toArray();
            return (int) ($result[0]['count'] ?? 0);
        }
        return 0;
    }

    public function liked($id)
    {
        return (bool) count($this->getTableGateway('forum_like')->select(['post_id' => $this->getId(), 'customer_id' => $id, 'review_id' => null])->toArray());
    }

    public function like($id)
    {
        $ref = new static();
        $ref->load($this->getId());
        $author_id = $ref->storage['customer_id'];
        if ($this->liked($id)) {
            $this->getTableGateway('forum_like')->delete(['post_id' => $this->getId(), 'customer_id' => $id, 'review_id' => null]);
        } else {
            $this->getTableGateway('forum_like')->insert(['post_id' => $this->getId(), 'customer_id' => $id, 'author_id' => $author_id]);
        }
        $this->setData('like', $this->getLikeCount())->save();
        return $this->storage['like'];
    }

    public function getDislikeCount()
    {
        if ($this->getId()) {
            $tableGateway = $this->getTableGateway('forum_post_dislike');
            $select = $tableGateway->getSql()->select();
            $select->columns(['count' => new Expression('count(1)')])
                    ->group('post_id')
                    ->where(['post_id' => $this->getId()]);
            $result = $tableGateway->selectWith($select)->toArray();
            return (int) ($result[0]['count'] ?? 0);
        }
        return 0;
    }

    public function disliked($id)
    {
        return (bool) count($this->getTableGateway('forum_post_dislike')->select(['post_id' => $this->getId(), 'customer_id' => $id])->toArray());
    }

    public function dislike($id)
    {
        if ($this->disliked($id)) {
            $this->getTableGateway('forum_post_dislike')->delete(['post_id' => $this->getId(), 'customer_id' => $id]);
        } else {
            $this->getTableGateway('forum_post_dislike')->insert(['post_id' => $this->getId(), 'customer_id' => $id]);
        }
        $this->setData('dislike', $this->getDislikeCount())->save();
        return $this->storage['dislike'];
    }

    public function favorited($id)
    {
        return (bool) count($this->getTableGateway('forum_post_favorite')->select(['post_id' => $this->getId(), 'customer_id' => $id])->toArray());
    }

    public function getCollectCount()
    {
        if ($this->getId()) {
            $tableGateway = $this->getTableGateway('forum_post_favorite');
            $select = $tableGateway->getSql()->select();
            $select->columns(['count' => new Expression('count(1)')])
                    ->group('post_id')
                    ->where(['post_id' => $this->getId()]);
            $result = $tableGateway->selectWith($select)->toArray();
            return (int) ($result[0]['count'] ?? 0);
        }
        return 0;
    }

    public function collected($id)
    {
        return (bool) count($this->getTableGateway('forum_post_favorite')->select(['post_id' => $this->getId(), 'customer_id' => $id])->toArray());
    }

    public function collect($id)
    {
        $col = new static();
        $col->load($this->getId());
        if ($this->collected($id)) {
            $this->getTableGateway('forum_post_favorite')->delete(['post_id' => $this->getId(), 'customer_id' => $id]);
        } else {
            $this->getTableGateway('forum_post_favorite')->insert(['post_id' => $this->getId(), 'customer_id' => $id]);
        }
        $this->setData('collections', $this->getCollectCount())->save();
        $this->flushList($this->getCacheKey());
        return $this->storage['collections'];
    }

    protected function beforeSave()
    {
        $this->beginTransaction();
        parent::beforeSave();
    }

    protected function afterSave()
    {
        $isNew = $this->isNew;
        parent::afterSave();
        if (!empty($this->storage['tmpcontent'])) {
            $engine = (new Factory())->getSearchEngineHandler();
            if ($isNew) {
                $item = [[
                    'id' => $this->getId(),
                    'store_id' => 1,
                    'data' => (empty($this->storage['title']) ? '' : $this->storage['title'] . Indexer::DELIMITER) . $this->storage['tmpcontent']
                ]];
                $data = [];
                $languages = new Language();
                $languages->columns(['id']);
                $languages->load(true, true);
                foreach (new Language() as $language) {
                    $data[$language['id']] = $item;
                }
            } else {
                $engine->delete('forum_post_search', $this->getId(), Bootstrap::getLanguage()->getId());
                $data = [Bootstrap::getLanguage()->getId() => [[
                    'id' => $this->getId(),
                    'store_id' => 1,
                    'data' => (empty($this->storage['title']) ? '' : $this->storage['title'] . Indexer::DELIMITER) . $this->storage['tmpcontent']
                ]]];
            }
            $engine->update('forum_post_search', $data);
        }
        $this->commit();
    }

    protected function afterLoad(&$result)
    {
        if (isset($result[0]['id'])) {
            $data = @gzdecode($result[0]['content']);
            if ($data !== false) {
                $result[0]['content'] = $data;
            }
            if (!empty($result[0]['temp_content'])) {
                $data = @gzdecode($result[0]['temp_content']);
                if ($data !== false) {
                    $result[0]['temp_content'] = $data;
                }
            }
        }
        parent::afterLoad($result);
    }

    protected function isUpdate($constraint = [], $insertForce = false)
    {
        $result = parent::isUpdate($constraint, $insertForce);
        if (!empty($this->storage['content'])) {
            $this->storage['title'] = htmlspecialchars($this->storage['title'], ENT_QUOTES | ENT_HTML5);
            $this->storage['tmpcontent'] = $this->getContainer()->get('htmlpurifier')->purify($this->storage['content']);
            if ($result && !$this->getContainer()->get('config')['forum/post/status']) {
                $this->setData([
                    'temp_content' => gzencode($this->storage['tmpcontent']),
                    'status' => 2
                ]);
                unset($this->storage['content']);
            } else {
                $this->storage['content'] = gzencode($this->storage['tmpcontent']);
            }
        }
        if (!$result && !$this->getId()) {
            $increment = new Increment();
            $increment->load($this->tableName);
            $this->setId($increment->getIncrementId());
            $this->setData('uri_key', 'thread-' . $this->getId());
        }
        return $result;
    }

    public function getThumbnail()
    {
        $images = json_decode($this->storage['images']);
        if (is_array($images) && !empty($images)) {
            return $images[0];
        } else {
            return '';
        }
    }

    public function getThumbnails()
    {
        $images = json_decode($this->storage['images']);
        if (is_array($images) && !empty($images)) {
            return $images;
        } else {
            return [];
        }
    }

    public function getVideo()
    {
        $video = $this->storage['videos'];
        if (!empty($video)) {
            return $video;
        } else {
            return '';
        }
    }

    public function getLinkedProducts($useCache = false)
    {
        if ($this->getId()) {
            $products = new productCollection();
            $products->columns(['id', 'name']);
            $products->join('forum_product_relation', 'forum_product_relation.product_id=main_table.id', ['post_id', 'category_id', 'product_id', 'sort_order'], 'left')
                    ->where
                    ->equalTo('post_id', $this->getId());
            if ($useCache) {
                $this->flushList($products);
            }
            return $products;
        }
        return [];
    }

    public function getSelfPosts()
    {
        if ($this->getId()) {
            $posts = new \Redseanet\Forum\Model\Collection\Post();
            $posts->where(['customer_id' => $this->getCustomer()['id']])
                    ->where
                    ->notEqualTo('id', $this->getId());
            return $posts;
        }
        return [];
    }

    public function getCategoryRelatePosts($category_id)
    {
        if ($this->getId()) {
            $posts = new \Redseanet\Forum\Model\Collection\Post();
            $posts->where(['category_id' => $category_id])
                    ->where
                    ->notEqualTo('id', $this->getId());
            return $posts;
        }
        return [];
    }

    public function getPostList($customer_id)
    {
        if ($this->getId()) {
            $posts = new \Redseanet\Forum\Model\Collection\Post();
            $posts->where(['customer_id' => $customer_id]);
            return $posts;
        }
        return [];
    }

    public function favorite($id)
    {
        $ref = new static();
        $ref->load($this->getId());
        if ($this->favorited($id)) {
            $this->getTableGateway('forum_post_favorite')->delete(['post_id' => $this->getId(), 'customer_id' => $id]);
            return false;
        } else {
            $this->getTableGateway('forum_post_favorite')->insert(['post_id' => $this->getId(), 'customer_id' => $id]);
            return true;
        }
    }

    public function getLinks()
    {
        if ($this->getId()) {
            $links = new \Redseanet\Forum\Model\Collection\Post\RecommendLink();
            $links->where(['post_id' => $this->getId()]);
            $links->load(true, true);
        }
        return $links ?? [];
    }

    public function customerFollowed($customer_id, $like_customer_id)
    {
        $customerLike = new customerLikeCollection();
        $customerLike->where(['customer_id' => $customer_id, 'like_customer_id' => $like_customer_id]);
        $customerLike->load(true, true);

        return count($customerLike) ? true : false;
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
}
