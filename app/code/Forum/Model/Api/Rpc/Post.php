<?php

namespace Redseanet\Forum\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Laminas\Db\Sql\Predicate\In;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Sql\Select;
use Redseanet\Lib\Bootstrap;
use Redseanet\Forum\Model\Collection\Post as PostCollection;
use Redseanet\Forum\Model\Post as Model;
use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Customer as customerCollection;
use Redseanet\Forum\Model\Collection\Post\Review as ReviewCollection;
use Redseanet\Forum\Model\Collection\Post\Like as likeCollecttion;
use Redseanet\Forum\Model\Post\Review;
use Redseanet\Forum\Model\Collection\Post\Following as followingCollection;
use Redseanet\Forum\Model\Collection\Post\Favorite as favoriteCollection;
use Redseanet\Forum\Model\CustomerLike;
use Redseanet\Lib\Model\Language;
use DateTime;
use Redseanet\Forum\Model\Poll;
use Redseanet\Forum\Model\Poll\Option;
use Redseanet\Forum\Model\Post\Link;

class Post extends AbstractHandler {

    use \Redseanet\Lib\Traits\Url;
    use \Redseanet\Lib\Traits\Translate;
    use \Redseanet\Forum\Traits\Wechat;

    protected $current = null;
    protected $conditionKey = ['id', 'customer_id', 'category_id', 'language_id', 'product_id', 'poll_id', 'anonymous', 'status', 'uri_key', 'title', 'description', 'content', 'temp_content', 'images', 'like', 'dislike', 'reviews', 'collections', 'can_review', 'is_top', 'is_hot', 'is_draft', 'is_relate', 'created_at', 'updated_at'];

    /**
     * @param string $id
     * @param string $token
     * @param array $condition
     * @param int $languageId
     * @return array
     */
    public function getForumPostList($id, $token, $condition = [], $languageId = 0, $customerId = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $postCollection = new PostCollection();
        $postCollection->columns(["id"]);
        $postCollection->join('forum_category', 'forum_post.category_id=forum_category.id', [], 'left');

        if (count($condition) > 0) {
            foreach ($condition as $conditionDataK => $conditionDataV) {
                if (in_array($conditionDataK, $this->conditionKey)) {
                    $postCollection->where(['forum_post.' . $conditionDataK => $conditionDataV]);
                }
            }
        }
        if (count($condition) > 0 && !empty($condition["categories"]) && is_array($condition["categories"]) && count($condition["categories"]) > 0) {
            $postCollection->where("forum_post.category_id in (" . implode(',', $condition["categories"]) . ")");
        }
        if (isset($condition['inFavorited']) && $condition['inFavorited'] != '') {
            $postCollection->join('forum_post_favorite', 'forum_post.id=forum_post_favorite.post_id', [], 'left');
            $postCollection->where(['forum_post_favorite.customer_id' => intval($condition['inFavorited'])]);
        }
        if (isset($condition['inLiked']) && $condition['inLiked'] != '') {
            $postCollection->join('forum_like', 'forum_post.id=forum_like.post_id', [], 'left');
            $postCollection->where(['forum_like.customer_id' => intval($condition['inLiked'])]);
        }
        if (!isset($condition['limit']) || $condition['limit'] == '') {
            $condition['limit'] = 20;
        } else {
            $condition['limit'] = intval($condition['limit']);
        }
        if (!isset($condition['page']) || $condition['page'] == '') {
            $condition['page'] = 1;
        } else {
            $condition['page'] = intval($condition['page']);
        }
        if ($languageId == 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $language = new Language();
        $language->load($languageId);
        $resultData = [];

        $total = $postCollection->count();
        $postCollection->offset(($condition['page'] - 1) * $condition['limit'])->limit((int) $condition['limit']);
        $postCollection->order('forum_post.created_at DESC');
        $last_page = ceil($total / $condition['limit']);
        //echo $postCollection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        $resultData['pagination'] = [
            "total" => $total,
            "per_page" => $condition['limit'],
            "current_page" => $condition['page'],
            "last_page" => $last_page,
            "next_page" => ($last_page > $condition['page'] ? $condition['page'] + 1 : $last_page),
            "previous_page" => ($condition['page'] > 1 ? $condition['page'] - 1 : 1),
            "has_next_page" => ($last_page > $condition['page'] ? true : false),
            "has_previous_page" => ($condition['page'] > 1 && $last_page > 1 ? true : false)
        ];

        $postList = $postCollection->load(true, true);
        $ids = [];
        if (count($postList) > 0) {
            foreach ($postList as $post) {
                $ids[] = $post["id"];
            }
        }
        $resultData["posts"] = [];
        if (count($ids) > 0) {
            $resultData["posts"] = $this->getPostData($ids, $customerId, $languageId, $language["code"]);
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get post list successfully'];
        return $this->responseData;
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param array $data
     * @param int $languageId
     * @return array
     */
    public function addForumPost($id, $token, $cutomerId, $data = [], $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $language = new Language();
        $language->load($languageId);
        if (!empty($data['openid'])) {
            $accesstoken = $this->getAccessToken();
            Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($accesstoken)));
            if (!empty($accesstoken)) {
                $msgMsgCheckParams = ['openid' => $data['openid'], 'token' => $accesstoken, 'scene' => 2, 'content' => $data['content'], 'title' => $data['title'],];
                $msgCheck = $this->msgSecCheck($msgMsgCheckParams);
                Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($msgCheck)));
                if ($msgCheck['code'] != 200) {
                    $this->responseData = ['statusCode' => $msgCheck['code'], 'data' => json_encode($msgCheck), 'message' => $this->translate('The title or content contains pornographic or political words', [], null, $language['code'])];
                    return $this->responseData;
                }
            } else {
                $this->responseData = ['statusCode' => 400, 'data' => [], 'message' => $this->translate('get wechat accesstoken fail', [], null, $language['code'])];
                return $this->responseData;
            }
        }
        $config = $this->getContainer()->get('config');
        if (empty($data['category_id'])) {
            if (empty($data['category'])) {
                $data['category_id'] = 1;
            } else {
                $data['category_id'] = $data['category'];
            }
        }
        if (empty($data['is_relate'])) {
            $data['is_relate'] = 0;
        } else {
            $data['is_relate'] = 1;
        }
        if (empty($data['product_category_id'])) {
            if (empty($data['product_category'])) {
                $data['product_category_id'] = 1;
            } else {
                $data['product_category_id'] = $data['product_category'];
            }
        }
        $customer = new Customer();
        $customer->load($cutomerId);
        $data['customer_id'] = $customer->getId();
        $data['language_id'] = $languageId != 0 ? $languageId : Bootstrap::getLanguage()->getId();
        $data['status'] = $config['forum/post/status'];
        if (isset($data['images']) && $data['images'] != '' && is_array($data['images'])) {
            $images = [];
            for ($i = 0; $i < count($data['images']); $i++) {
                $avatar = $data['images'][$i];
                $name = 'post' . date('YmdHis') . mt_rand(10, 1000) . '.' . substr($avatar, 11, strpos($avatar, ';') - 11);
                if (!is_dir(BP . 'pub/upload/forum/')) {
                    mkdir(BP . 'pub/upload/forum/', 0777, true);
                }
                $fp = fopen(BP . 'pub/upload/forum/' . $name, 'wb');
                fwrite($fp, base64_decode(trim(substr($avatar, strpos($avatar, ',') + 1))));
                fclose($fp);
                $images[] = $name;
            }
            $data['images'] = json_encode($images);
        }
        //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($data['images'])));
        try {
            $this->beginTransaction();
            if (isset($data['poll']) && isset($data['poll']['title']) && $data['poll']['title'] != '') {
                $newPoll = [];
                $newPoll['description'] = [];
                for ($p = 0; $p < count($data['poll']['description']); $p++) {
                    if ($data['poll']['description'][$p] != '') {
                        $newPoll['description'][] = $data['poll']['description'][$p];
                    }
                }
                $newPoll['max_choices'] = intval($data['poll']['max_choices']);
                $newPoll['title'] = $data['poll']['title'];
                $newPoll['expired_at'] = !empty($data['poll']['expired_at']) ? $data['poll']['expired_at'] : date('Y-m-d');
                if (count($newPoll['description']) > 0) {
                    $pollData = ['max_choices' => $newPoll['max_choices'], 'title' => $newPoll['title'], 'expired_at' => $newPoll['expired_at']];

                    $poll = new Poll($pollData);
                    $poll->save();
                    foreach ($newPoll['description'] as $order => $description) {
                        $option = new Option();
                        $option->setData([
                            'poll_id' => $poll->getId(),
                            'description' => $description,
                            'sort_order' => $order
                        ])->save();
                    }
                    $data['poll_id'] = $poll->getId();
                    unset($data['poll']);
                }
            }

            $model = new Model($data);
            $model->save();
            if (isset($data['links']) && is_array($data['links']) && count($data['links']) > 0) {
                for ($l = 0; $l < count($data['links']); $l++) {
                    if (!empty($data['links'][$l]['name']) && !empty($data['links'][$l]['link'])) {
                        $linkModel = new Link(['post_id' => $model->getId(), 'name' => $data['links'][$l]['name'], 'link' => $data['links'][$l]['link']]);
                        $linkModel->save();
                    }
                }
            }
            $this->commit();
            $this->flushList('forum_post');
            $resultData = $model->load($model->getId())->toArray();
            $images = [];
            $tmpImages = json_decode($resultData['images'], true);
            for ($i = 0; $i < count($tmpImages); $i++) {
                $images[] = $this->getBaseUrl('pub/upload/forum/' . $tmpImages[$i]);
            }
            unset($resultData['images']);
            $resultData['images'] = $images;
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => $this->translate('The post published successfully', [], null, $language['code'])];
            return $this->responseData;
        } catch (Exception $e) {
            $this->rollback();
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => $this->translate('The post published failure', [], null, $language['code']) . 'error:' . $e->getMessage()];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $postId
     * @param int $languageId
     * @return array
     */
    public function getForumPostById($id, $token, $postId, $languageId = 0, $customerId = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');
        $postCollection = new PostCollection();
        $views = new Select('log_visitor');
        $views->columns(['count' => new Expression('count(1)')])
                ->group('post_id')
        ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');
        $language = new Language();
        $language->load($languageId);
        if ($customerId != '') {
            $likedSelect = new Select();
            $likedSelect->from('forum_like');
            $likedSelect->columns(['liked' => new Expression('count(forum_like.post_id)')]);
            $likedSelect->where('`forum_like`.`customer_id`=' . $customerId . ' and `forum_like`.`post_id`=`forum_post`.`id`');
            $favoritedSelect = new Select();
            $favoritedSelect->from('forum_post_favorite');
            $favoritedSelect->columns(['favorited' => new Expression('count(forum_post_favorite.post_id)')]);
            $favoritedSelect->where('`forum_post_favorite`.`customer_id`=' . $customerId . ' and `forum_post_favorite`.`post_id`=`forum_post`.`id`');
            $customerLikedSelect = new Select();
            $customerLikedSelect->from('forum_customer_like');
            $customerLikedSelect->columns(['customerliked' => new Expression('count(forum_customer_like.id)')]);
            $customerLikedSelect->where('`forum_customer_like`.`customer_id`=' . $customerId . ' and `forum_customer_like`.`like_customer_id`=`forum_post`.`customer_id`');
            $postCollection->columns(['*', 'views' => $views, 'liked' => $likedSelect, 'favorited' => $favoritedSelect, 'customerliked' => $customerLikedSelect]);
        } else {
            $postCollection->columns(['*', 'views' => $views]);
        }
        $postCollection->join('forum_category', 'forum_post.category_id=forum_category.id', ['category_id' => 'id', 'parent_id'], 'left')
                ->join('forum_category_language', 'forum_category.id=forum_category_language.category_id', ['category_name' => 'name'], 'left');
        if ($languageId != 0) {
            $postCollection->where(['forum_category_language.language_id' => $languageId, 'forum_post.id' => intval($postId)]);
        } else {
            $postCollection->where(['forum_category_language.language_id' => Bootstrap::getLanguage()->getId(), 'forum_post.id' => intval($postId)]);
        }
        //echo $postCollection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        $postList = $postCollection->load(true);
        if (count($postList) > 0) {
            $customer = new Customer();
            $customer->load($postList[0]['customer_id']);
            $images = [];
            $tmpImages = json_decode($postList[0]['images'], true);
            if ($tmpImages != '' && count($tmpImages) > 0) {
                for ($i = 0; $i < count($tmpImages); $i++) {
                    $images[] = $this->getBaseUrl('pub/upload/forum/' . $tmpImages[$i]);
                }
            }
            unset($postList[0]['images']);
            $reviews = new ReviewCollection();
            $reviews->join('customer_1_index', 'forum_post_review.customer_id=customer_1_index.id', ['avatar', 'username'], 'left');
            $reviews->where(['forum_post_review.post_id' => $postList[0]['id']]);
            $reviews->order('forum_post_review.created_at DESC')->where->greaterThan('forum_post_review.status', 0);
            $this->filter($reviews, ['page' => 1]);
            $reviewList = [];
            if (count($reviews) > 0) {
                for ($r = 0; $r < count($reviews); $r++) {
                    $tmpReview = $reviews[$r]->toArray();
                    $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                    if ($tmpReview['avatar']) {
                        $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $tmpReview['avatar']);
                    }
                    $tmpReview['avatar'] = $avatar;
                    $tmpReview['created_at_string'] = $this->getTime($tmpReview['created_at'], $language['code']);
                    $reviewList[] = $tmpReview;
                }
            }
            $postList[0]['images'] = $images;
            if ($postList[0]['videos'] != '') {
                $postList[0]['videos'] = $this->getBaseUrl('pub/upload/forum/videos/' . $postList[0]['videos']);
            }
            $postList[0]['created_at_string'] = $this->getTime($postList[0]["created_at"], $language['code']);
            $likeList = [];
            $postlikeList = new likeCollecttion();
            $postlikeList->columns(['customer_id']);
            $postlikeList->join('customer_1_index', 'forum_like.customer_id=customer_1_index.id', ['avatar', 'username', 'created_at'], 'left');
            $postlikeList->where(['forum_like.post_id' => $postList[0]['id']]);
            $postlikeList->order('forum_like.created_at DESC')->offset(0)->limit(20);
            if (count($postlikeList) > 0) {
                for ($l = 0; $l < count($postlikeList); $l++) {
                    $tmpLike = $postlikeList[$l]->toArray();
                    $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                    if ($tmpLike['avatar']) {
                        $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $tmpLike['avatar']);
                    }
                    $tmpLike['avatar'] = $avatar;
                    $tmpLike['created_at_string'] = $this->getTime($tmpLike['created_at'], $language['code']);
                    $likeList[] = $tmpLike;
                }
            }
            $resultData = $postList[0]->toArray() + ['customer_name' => $customer->offsetGet('username'), 'customer_avatar' => ($customer->offsetGet('avatar')?$this->getBaseUrl('pub/upload/customer/avatar/' . $customer->offsetGet('avatar')):$this->getPubUrl('frontend/images/avatar-holderplace.jpg')), 'reviewlist' => $reviewList, 'likelist' => $likeList];
            $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get post information successfully'];
            return $this->responseData;
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'the post do not exit'];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param int $postId
     * @param array $data
     * @param int $languageId
     * @return array
     */
    public function forumPostReviewSave($id, $token, $customerId, $postId, $data, $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $language = new Language();
        $language->load($languageId);
        $config = $this->getContainer()->get('config');
        $required = ['post_id', 'content'];
        if ($config['forum/review/subject'] == 1) {
            $required[] = 'subject';
        }
        if ($config['forum/review/anonymous'] == 0) {
            unset($data['anonymous']);
        }
        $customer = new Customer();
        $customer->load($customerId);
        if ($customer['forum_banned'] || !empty($data['id']) && (new Review())->load($data['id'])['customer_id'] !== $customer->getId()) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => $this->translate('You are not allowed to review. Please contact us if you have any doubt', [], null, $language['code'])];
            return $this->responseData;
        }
        $post = new Model();
        $post->load($postId);
        if (isset($post['id']) && $post['id'] != '') {
            if ($post['can_review']) {
                $data['customer_id'] = $customer->getId();
                $data['status'] = $config['forum/review/status'];
                $data['post_id'] = intval($postId);
                try {
                    $model = new Review($data);
                    $model->save();
                    $resultData = $model->load($model->getId())->toArray();
                    $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                    if (!empty($customer['avatar'])) {
                        $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $customer['avatar']);
                    }
                    $resultData["avatar"] = $avatar;
                    $resultData["username"] = $customer['username'];
                    $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => $data['status'] ? $this->translate('The review has been posted successfully', [], null, $language['code']) : $this->translate('The review has been posted successfully. It would been viewed after approval', [], null, $language['code'])];
                    return $this->responseData;
                } catch (Exception $e) {
                    $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => $this->translate('An error detected. Please contact us or try again later', [], null, $language['code']) . ',Error:' . $e->getMessage()];
                    return $this->responseData;
                }
            } else {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => $this->translate('the post can not bet reviewed', [], null, $language['code'])];
                return $this->responseData;
            }
        } else {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => $this->translate('the post do not exit', [], null, $language['code'])];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param int $postId
     * @return array
     */
    public function forumLikePost($id, $token, $customerId, $postId) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        try {
            $model = new Model();
            $customer = new Customer();
            $customer->load($customerId);
            $result = $model->setId($postId)->like($customer->getId());
            if (!$model->offsetGet('like')) {
                $this->responseData = ['statusCode' => '200', 'data' => $model->toArray(), 'message' => 'Cancel Like'];
                return $this->responseData;
            } else {
                $this->responseData = ['statusCode' => '200', 'data' => $model->toArray(), 'message' => 'You have successfully read this post'];
                return $this->responseData;
            }
        } catch (Exception $e) {
            $this->rollback();
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please contact us or try again later'];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param int $postId
     * @return array
     */
    public function forumDeletePost($id, $token, $customerId, $postId) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        try {
            $model = new Model();
            $model->setId(intval($postId))->remove();
            $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'remove the post successfully'];
            return $this->responseData;
        } catch (Exception $e) {
            $this->rollback();
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please contact us or try again later' . $e];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param string $condition json format string
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function forumPostReviewList($id, $token, $condition, $page = 1, $limit = 20) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }

        $conditionKey = ['id', 'post_id', 'customer_id', 'subject', 'reference', 'content', 'temp_content', 'like', 'dislike', 'anonymous', 'status', 'created_at', 'updated_at'];
        $ReviewCollection = new ReviewCollection();
        //Bootstrap::getContainer()->get("log")->logException(new \Exception(json_encode($condition)));
        //var_dump($condition);
        if (is_array($condition) && count($condition) > 0) {
            foreach ($condition as $conditionK => $conditionV) {
                if (in_array($conditionK, $conditionKey)) {
                    $ReviewCollection->where(['forum_post_review.' . $conditionK => $conditionV]);
                }
            }
        }
        $ReviewCollection->offset(($page > 0 ? ($page - 1) : 0) * $limit)->limit((int) $limit);
        $ReviewCollection->join('customer_1_index', 'forum_post_review.customer_id=customer_1_index.id', ['avatar', 'username'], 'left');
        $ReviewCollection->order('forum_post_review.created_at DESC')->where->greaterThan('forum_post_review.status', 0);
        //Bootstrap::getContainer()->get("log")->logException(new \Exception($ReviewCollection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform())));
        $reviewList = $ReviewCollection->load(true, true);
        $resultData = [];
        for ($p = 0; $p < count($reviewList); $p++) {
            $tmpReview = $reviewList[$p];
            $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
            if ($tmpReview['avatar']) {
                $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $tmpReview['avatar']);
            }
            $tmpReview['avatar'] = $avatar;
            $resultData[] = $tmpReview;
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get forum post review list successfully'];
        return $this->responseData;
    }

    /**
     * @param string $id
     * @param string $token
     * @param string $customerId
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function forumPostReplyList($id, $token, $customerId, $page = 1, $limit = 20) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $ReviewCollection = new ReviewCollection();
        $myReviews = $ReviewCollection->getMyReviews($customerId);

        $myReviews->offset(($page > 0 ? ($page - 1) : 0) * $limit)->limit((int) $limit);
        $myReviews->order('forum_post_review.created_at DESC');

        $reviewList = $myReviews->load(true, true);
        $resultData = [];
        foreach ($reviewList as $review) {
            $ReviewReferences = new ReviewCollection();
            $ReviewReferences->where(['reference' => $review['id'], 'status' => 1, 'customer_id' => $customerId]);
            $ReviewReferences->load(true, true);
            $reference = [];
            if (count($ReviewReferences) > 0) {
                for ($r = 0; $r < count($ReviewReferences); $r++) {
                    $customerReference = new Customer();
                    $customerReference->load($review['customer_id']);
                    $reference[] = $ReviewReferences[$r] + ['username' => $customerReference['username'], 'customer_avatar' => $this->getBaseUrl('pub/upload/customer/avatar/' . $customerReference['avatar'])];
                }
            }
            $customer = new Customer();
            $customer->load($review['customer_id']);
            $resultData[] = $review + ['references' => $reference, 'username' => $customer['username'], 'customer_avatar' => $this->getBaseUrl('pub/upload/customer/avatar/' . $customer['avatar'])];
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get forum post review reply list successfully'];
        return $this->responseData;
    }

    public function getMyLiked($id, $token, $customerId, $page = 1, $limit = 20) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $liked = new likeCollecttion();
        $my_likes = $liked->getMyLikes($customerId);
        $query = ['page' => $page, 'limit' => $limit];
        $this->filter($my_likes, $query);
        $likes = [];
        if (count($my_likes) > 0) {
            for ($l = 0; $l < count($my_likes); $l++) {
                $tmpMyLike = $my_likes[$l]->toArray();
                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                if ($tmpMyLike['avatar']) {
                    $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $tmpMyLike['avatar']);
                }
                $tmpMyLike['avatar'] = $avatar;
                $images = [];
                $tmpImages = json_decode($tmpMyLike['images'], true);
                if ($tmpImages != '' && count($tmpImages) > 0) {
                    for ($i = 0; $i < count($tmpImages); $i++) {
                        $images[] = $this->getBaseUrl('pub/upload/forum/' . $tmpImages[$i]);
                    }
                }
                $tmpMyLike['images'] = $images;
                $likes[] = $tmpMyLike;
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $likes, 'message' => 'get forum my like list successfully'];
        return $this->responseData;
    }

    public function getBeLikes($id, $token, $customerId, $page = 1, $limit = 20) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $query = ['page' => $page, 'limit' => $limit];
        $belike = new likeCollecttion();
        $belikes = $belike->getBeLikes($customerId);
        $this->filter($belikes, $query);
        $likes = [];
        if (count($belikes) > 0) {
            for ($l = 0; $l < count($belikes); $l++) {
                $tmpBeLike = $belikes[$l]->toArray();
                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                if ($tmpBeLike['avatar']) {
                    $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $tmpBeLike['avatar']);
                }
                $tmpBeLike['avatar'] = $avatar;
                $images = [];
                $tmpImages = json_decode($tmpBeLike['images'], true);
                if ($tmpImages != '' && count($tmpImages) > 0) {
                    for ($i = 0; $i < count($tmpImages); $i++) {
                        $images[] = $this->getBaseUrl('pub/upload/forum/' . $tmpImages[$i]);
                    }
                }
                $tmpBeLike['images'] = $images;

                $likes[] = $tmpBeLike;
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $likes, 'message' => 'get forum be liked list successfully'];
        return $this->responseData;
    }

    public function getFollow($id, $token, $customerId, $conditionData = [], $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $liked = new followingCollection();
        $my_likes = $liked->getFollow($customerId);
        if (!isset($conditionData['limit']) || $conditionData['limit'] == '') {
            $conditionData['limit'] = 20;
        } else {
            $conditionData['limit'] = intval($conditionData['limit']);
        }
        if (!isset($condition['page']) || $conditionData['page'] == '') {
            $conditionData['page'] = 1;
        } else {
            $conditionData['page'] = intval($conditionData['page']);
        }
        $total = 0;
        $last_page = ceil($total / $conditionData['limit']);
        $resultData['pagination'] = [
            "total" => $total,
            "per_page" => $conditionData['limit'],
            "current_page" => $conditionData['page'],
            "last_page" => $last_page,
            "next_page" => ($last_page > $conditionData['page'] ? $conditionData['page'] + 1 : $last_page),
            "previous_page" => ($conditionData['page'] > 1 ? $conditionData['page'] - 1 : 1),
            "has_next_page" => ($last_page > $conditionData['page'] ? true : false),
            "has_previous_page" => ($conditionData['page'] > 1 && $last_page > 1 ? true : false)
        ];
        $likes = [];
        if (count($my_likes) > 0) {
            for ($l = 0; $l < count($my_likes); $l++) {
                $like = $my_likes[$l]->toArray();
                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                if ($like['avatar']) {
                    $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $like['avatar']);
                }
                $like['avatar'] = $avatar;
                $likes[] = $like;
            }
        }
        $resultData["followed"] = $likes;
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get forum my follow list successfully'];
        return $this->responseData;
    }

    public function getFans($id, $token, $customerId, $page = 1, $limit = 20) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $query = ['page' => $page, 'limit' => $limit];
        $belike = new followingCollection();
        $belikes = $belike->getFans($customerId);
        $this->filter($belikes, $query);
        $likes = [];
        if (count($belikes) > 0) {
            for ($l = 0; $l < count($belikes); $l++) {
                $like = $belikes[$l]->toArray();
                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                if ($like['avatar']) {
                    $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $like['avatar']);
                }
                $like['avatar'] = $avatar;
                $likes[] = $like;
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $likes, 'message' => 'get forum belike list successfully'];
        return $this->responseData;
    }

    public function getBeCollected($id, $token, $customerId, $page = 1, $limit = 20) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $query = ['page' => $page, 'limit' => $limit];
        $favorited = new favoriteCollection();
        $be_collects = $favorited->getBeCollected($customerId);

        $this->filter($be_collects, $query);
        $collects = [];
        if (count($be_collects) > 0) {
            for ($l = 0; $l < count($be_collects); $l++) {
                $collect = $be_collects[$l]->toArray();
                $images = [];
                $tmpImages = json_decode($collect['images'], true);
                if ($tmpImages != '' && count($tmpImages) > 0) {
                    for ($i = 0; $i < count($tmpImages); $i++) {
                        $images[] = $this->getBaseUrl('pub/upload/forum/' . $tmpImages[$i]);
                    }
                }
                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                if ($collect['avatar']) {
                    $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $collect['avatar']);
                }
                $collect['images'] = $images;
                $collect['avatar'] = $avatar;
                $collects[] = $collect;
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $collects, 'message' => 'get forum bebollected list successfully'];
        return $this->responseData;
    }

    public function getFavoritedWithPosts($id, $token, $customerId, $page = 1, $limit = 20) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $query = ['page' => $page, 'limit' => $limit];
        $views = new Select('log_visitor');
        $views->columns(['count' => new Expression('count(1)')])
                ->group('post_id')
        ->where->equalTo('post_id', 'forum_post.id', 'identifier', 'identifier');
        $likedSelect = new Select();
        $likedSelect->from('forum_like');
        $likedSelect->columns(['liked' => new Expression('count(forum_like.post_id)')]);
        $likedSelect->where('`forum_like`.`customer_id`=' . $customerId . ' and `forum_like`.`post_id`=`forum_post`.`id`');
        $favorited = new PostCollection();
        $favoritedSelect = $favorited->getSelect();
        $favoritedSelect->join('forum_post_favorite', 'forum_post.id = forum_post_favorite.post_id', ['favorite_id' => 'id', 'favorite_created_at' => 'created_at'], 'left')
                ->join('customer_1_index', 'customer_1_index.id=forum_post_favorite.customer_id', ['author' => 'username', 'avatar' => 'avatar'], 'left')
                ->where(['forum_post_favorite.customer_id' => $customerId]);
        $favorited->columns(['*', 'views' => $views, 'liked' => $likedSelect])
                ->where(['forum_post.is_draft' => 0])
                ->order('forum_post.is_top DESC')
        ->where->greaterThan('forum_post.status', 0);
        //echo $favorited->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        $this->filter($favorited, $query);
        $favorites = [];
        if (count($favorited) > 0) {
            for ($l = 0; $l < count($favorited); $l++) {
                $favorite = $favorited[$l]->toArray();
                $images = [];
                $tmpImages = json_decode($favorite['images'], true);
                if ($tmpImages != '' && count($tmpImages) > 0) {
                    for ($i = 0; $i < count($tmpImages); $i++) {
                        $images[] = $this->getBaseUrl('pub/upload/forum/' . $tmpImages[$i]);
                    }
                }
                $favorite['images'] = $images;
                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                if ($favorite['avatar']) {
                    $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $favorite['avatar']);
                }
                $favorite['avatar'] = $avatar;
                $favorites[] = $favorite;
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $favorites, 'message' => 'get forum favorite list successfully'];
        return $this->responseData;
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param int $toLikeCustomerId
     * @return array
     */
    public function forumToLikeCustomer($id, $token, $customerId, $toLikeCustomerId) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        try {
            $model = new CustomerLike();
            ['data' => $model->setId($customerId)->like($toLikeCustomerId)];
            $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'follow the customer successfully'];
            return $this->responseData;
        } catch (Exception $e) {
            $this->rollback();
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please contact us or try again later' . $e];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param int $postId
     * @return array
     */
    public function forumFavoritePost($id, $token, $customerId, $postId) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        try {
            $model = new Model();
            $model->load($postId);
            $model->favorite($customerId);
            $this->responseData = ['statusCode' => '200', 'data' => $model->toArray(), 'message' => 'You have favorited this post successfully'];
            return $this->responseData;
        } catch (Exception $e) {
            $this->rollback();
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please contact us or try again later'];
            return $this->responseData;
        }
    }

    /**
     * @param string $id
     * @param string $token
     * @param int $customerId
     * @param int $currentCustomerId
     * @return array
     */
    public function forumSpaceData($id, $token, $customerId, $currentCustomerId = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        try {
            $customer = new customerCollection();

            $followingSelect = new Select();
            $followingSelect->from('forum_customer_like');
            $followingSelect->columns(['following' => new Expression('count(forum_customer_like.id)')]);
            $followingSelect->where('`forum_customer_like`.`customer_id`=' . $customerId);

            $fansSelect = new Select();
            $fansSelect->from('forum_customer_like');
            $fansSelect->columns(['following' => new Expression('count(forum_customer_like.id)')]);
            $fansSelect->where('`forum_customer_like`.`like_customer_id`=' . $customerId);

            $likedSelect = new Select();
            $likedSelect->from('forum_like');
            $likedSelect->columns(['liked' => new Expression('count(forum_like.id)')]);
            $likedSelect->where('`forum_like`.`customer_id`=' . $customerId);

            $belikedSelect = new Select();
            $belikedSelect->from('forum_like');
            $belikedSelect->columns(['beliked' => new Expression('count(forum_like.id)')]);
            $belikedSelect->where('`forum_like`.`author_id`=' . $customerId);

            $customerLikedSelect = new Select();
            $customerLikedSelect->from('forum_customer_like');
            $customerLikedSelect->columns(['customerliked' => new Expression('count(forum_customer_like.id)')]);
            $customerLikedSelect->where('`forum_customer_like`.`customer_id`=' . $currentCustomerId . ' and `forum_customer_like`.`like_customer_id`=' . $customerId);

            $befavoritedSelect = new Select();
            $befavoritedSelect->from('forum_post_favorite');
            $befavoritedSelect->columns(['befavorited' => new Expression('count(forum_post_favorite.id)')]);
            $befavoritedSelect->join('forum_post', 'forum_post.id=forum_post_favorite.post_id', [], 'left');
            $befavoritedSelect->where('`forum_post`.`customer_id`=' . $customerId);

            if ($currentCustomerId != '') {
                $customer->columns(['*', 'following' => $followingSelect, 'fans' => $fansSelect, 'liked' => $likedSelect, 'beliked' => $belikedSelect, 'befavorited' => $befavoritedSelect, 'customerliked' => $customerLikedSelect]);
            } else {
                $customer->columns(['*', 'following' => $followingSelect, 'fans' => $fansSelect, 'liked' => $likedSelect, 'beliked' => $belikedSelect, 'befavorited' => $befavoritedSelect,]);
            }

            $customer->where(['main_table.id' => $customerId, 'main_table.status' => 1]);
            $customer->load(true, true);
            if (count($customer) > 0) {
                $customerArray = $customer[0];
                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                if ($customerArray['avatar']) {
                    $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $customerArray['avatar']);
                }
                $customerArray['avatar'] = $avatar;
                unset($customerArray['password']);
                $this->responseData = ['statusCode' => '200', 'data' => $customerArray, 'message' => 'get customer data successfully'];
                return $this->responseData;
            } else {
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'do not exit the customer'];
                return $this->responseData;
            }
        } catch (Exception $e) {
            $this->rollback();
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please contact us or try again later'];
            return $this->responseData;
        }
    }

    public function getTime($time, $languageCode) {
        $dt = new DateTime($time);
        $days = $dt->diff($this->getCurrent())->format('%a');
        if ($days && $days > 1) {
            return $this->translate('%d Days Ago', [$days], null, $languageCode);
        } elseif ((int) $days == 1) {
            return $this->translate('Yesterday %d', [$days], null, $languageCode);
        } else {
            return $this->translate('Today', [$days], null, $languageCode);
        }
    }

    protected function getCurrent() {
        if (is_null($this->current)) {
            $this->current = new DateTime();
        }
        return $this->current;
    }

    public function getPostData($ids, $customerId, $languageId, $languageCode) {
        $postCollection = new PostCollection();
        $postCollection->join('forum_category', 'forum_post.category_id=forum_category.id', ['category_id' => 'id', 'parent_id'], 'left')
                ->join('forum_category_language', 'forum_category.id=forum_category_language.category_id', ['category_name' => 'name'], 'left');
        if ($customerId != '') {
            $likedSelect = new Select();
            $likedSelect->from('forum_like');
            $likedSelect->columns(['liked' => new Expression('count(forum_like.post_id)')]);
            $likedSelect->where('`forum_like`.`customer_id`=' . $customerId . ' and `forum_like`.`post_id`=`forum_post`.`id`');
            $favoritedSelect = new Select();
            $favoritedSelect->from('forum_post_favorite');
            $favoritedSelect->columns(['favorited' => new Expression('count(forum_post_favorite.post_id)')]);
            $favoritedSelect->where('`forum_post_favorite`.`customer_id`=' . $customerId . ' and `forum_post_favorite`.`post_id`=`forum_post`.`id`');
            $postCollection->columns(['*', 'liked' => $likedSelect, 'favorited' => $favoritedSelect]);
        } else {
            $postCollection->columns(['*']);
        }
        $postCollection->where("forum_post.id in (" . implode(',', $ids) . ")");
        $postCollection->where(['forum_category_language.language_id' => $languageId]);
        $posts = [];
        $postList = $postCollection->load(true, true);
        for ($p = 0; $p < count($postList); $p++) {
            $customer = new Customer();
            $customer->load($postList[$p]['customer_id']);
            $images = [];
            $tmpImages = json_decode($postList[$p]['images'], true);
            if ($tmpImages != '' && count($tmpImages) > 0) {
                for ($i = 0; $i < count($tmpImages); $i++) {
                    $images[] = $this->getBaseUrl('pub/upload/forum/' . $tmpImages[$i]);
                }
            }
            unset($postList[$p]['images']);
            $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
            if ($customer->offsetGet('avatar')) {
                $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $customer->offsetGet('avatar'));
            }
            $postList[$p]['customer_name'] = $customer->offsetGet('username');
            $postList[$p]['customer_avatar'] = $avatar;
            $postList[$p]['images'] = $images;
            $postList[$p]['created_at_string'] = $this->getTime($postList[$p]['created_at'], $languageCode);

            $reviews = new ReviewCollection();
            $reviews->join('customer_1_index', 'forum_post_review.customer_id=customer_1_index.id', ['avatar', 'username'], 'left');
            $reviews->where(['forum_post_review.post_id' => $postList[$p]['id']]);
            $reviews->order('forum_post_review.created_at DESC')->where->greaterThan('forum_post_review.status', 0);
            $reviews->offset(0)->limit(20);
            $reviewList = [];
            if (count($reviews) > 0) {
                for ($r = 0; $r < count($reviews); $r++) {
                    $tmpReview = $reviews[$r]->toArray();
                    $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                    if ($tmpReview['avatar']) {
                        $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $tmpReview['avatar']);
                    }
                    $tmpReview['avatar'] = $avatar;
                    $tmpReview['created_at_string'] = $this->getTime($tmpReview['created_at'], $languageCode);
                    $reviewList[] = $tmpReview;
                }
            }
            $postList[$p]["reviewlist"] = $reviewList;
            $likeList = [];
            $postlikeList = new likeCollecttion();
            $postlikeList->columns(['customer_id']);
            $postlikeList->join('customer_1_index', 'forum_like.customer_id=customer_1_index.id', ['avatar', 'username', 'created_at'], 'left');
            $postlikeList->where(['forum_like.post_id' => $postList[$p]['id']]);
            $postlikeList->order('forum_like.created_at DESC')->offset(0)->limit(10);
            if (count($postlikeList) > 0) {
                for ($l = 0; $l < count($postlikeList); $l++) {
                    $tmpLike = $postlikeList[$l]->toArray();
                    $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                    if ($tmpLike['avatar']) {
                        $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $tmpLike['avatar']);
                    }
                    $tmpLike['avatar'] = $avatar;
                    $tmpLike['created_at_string'] = $this->getTime($tmpLike['created_at'], $languageCode);
                    $likeList[] = $tmpLike;
                }
            }
            $postList[$p]["likelist"] = $likeList;
            $posts[] = $postList[$p];
        }
        return $posts;
    }

    public function getRandCustomer($id, $token, $customerId = '', $exclude = [], $random = 5, $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $customers = new customerCollection();
        $customersSelect = $customers->getSelect();
        if (!empty($customerId)) {
            $sub1 = new Select('forum_customer_like');
            $sub1->columns(['customer_id'])->where(['customer_id' => $customerId]);
            $customersSelect->where->notIn('id', $sub1);
        }
        $exclude[] = $customerId;
        $customersSelect->where->notIn('id', $exclude);
        $customers->where(['status' => 1]);
        $customers->order(new Expression('Rand()'))->limit($random);
        $customerList = [];
        if (count($customers) > 0) {
            for ($l = 0; $l < count($customers); $l++) {
                $customer = $customers[$l]->toArray();
                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                if ($customer['avatar']) {
                    $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $customer['avatar']);
                }
                $customer['avatar'] = $avatar;
                unset($customer["password"]);
                $customerList[] = $customer;
            }
        }
        $this->responseData = ['statusCode' => '200', 'data' => $customerList, 'message' => 'get forum rand customer list successfully'];
        return $this->responseData;
    }

    public function getForumPostRandList($id, $token, $customerId = '', $conditionData = [], $random = 5, $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $conditionKey = ['id', 'customer_id', 'category_id', 'language_id', 'product_id', 'poll_id', 'anonymous', 'status', 'uri_key', 'title', 'description', 'content', 'temp_content', 'images', 'like', 'dislike', 'reviews', 'collections', 'can_review', 'is_top', 'is_hot', 'is_draft', 'is_relate', 'created_at', 'updated_at'];
        $postCollection = new PostCollection();
        $postCollection->columns(["id"]);

        if (count($conditionData) > 0) {
            foreach ($conditionData as $conditionDataK => $conditionDataV) {
                if (in_array($conditionDataK, $conditionKey)) {
                    $postCollection->where(['forum_post.' . $conditionDataK => $conditionDataV]);
                }
            }
        }
        if ($languageId == 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $language = new Language();
        $language->load($languageId);
        $postCollection->order(new Expression('Rand()'))->limit($random);

        $postList = $postCollection->load(true, true);
        $ids = [];
        if (count($postList) > 0) {
            foreach ($postList as $post) {
                $ids[] = $post["id"];
            }
        }
        $posts = $this->getPostData($ids, $customerId, $languageId, $language["code"]);
        $this->responseData = ['statusCode' => '200', 'data' => $posts, 'message' => 'get random post list successfully'];
        return $this->responseData;
    }

    public function toDoLikeCustomerAction($id, $token, $customerId, $likeId, $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        try {
            $model = new CustomerLike();
            $data = ['data' => $model->setId($customerId)->like($likeId)];
            if (!empty($data["like_customer_id"])) {
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'You subscribe the customer successfully'];
            } else {
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'You unsubscribe the customer successfully'];
            }
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'You subscribe the customer failure' . $e->getMessage()];
        }

        return $this->responseData;
    }

    public function toDoLikePostAction($id, $token, $customerId, $postId, $type = 1, $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($type == 1) {
            try {
                $model = new Model();
                $model->load($postId);
                $model->like($customerId);
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'You like the post successfully'];
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'You like the post failure'];
            }
        } else {
            try {
                $model = new Post();
                $model->load($postId);
                $model->dislike($customerId);
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'You unlike the post successfully'];
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'You unlike the post failure'];
            }
        }
        return $this->responseData;
    }

    public function toDoFavoritePostAction($id, $token, $customerId, $postId, $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        try {
            $model = new Model();
            $model->load($postId);
            $favoriteBool = $model->favorite($customerId);
            if ($favoriteBool) {
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'You favorite the post successfully'];
            } else {
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'You cancel favorite the post successfully'];
            }
            $this->flushList('forum_post');
        } catch (Exception $e) {
            $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'You favorite the post failure'];
        }
        return $this->responseData;
    }

    public function toDoLikePostReviewAction($id, $token, $customerId, $reviewId, $type = 1, $languageId = 0) {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($type == 1) {
            try {
                $model = new Review();
                $model->Review($reviewId);
                $model->like($customerId);
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'You like the review successfully'];
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'You like the review failure'];
            }
        } else {
            try {
                $model = new Post();
                $model->load($reviewId);
                $model->dislike($customerId);
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'You unlike the review successfully'];
            } catch (Exception $e) {
                $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => 'You unlike the review failure'];
            }
        }
        return $this->responseData;
    }

    public function getRecommentPostByPostId($id, $token, $postId, $condition = [], $languageId = 0, $customerId = '') {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if (!isset($condition['page']) || $condition['page'] == '') {
            $condition['page'] = 1;
        } else {
            $condition['page'] = intval($condition['page']);
        }
        if (!isset($condition['taglimit']) || $condition['taglimit'] == '') {
            $condition['taglimit'] = 5;
        } else {
            $condition['taglimit'] = intval($condition['taglimit']);
        }
        if (!isset($condition['authorlimit']) || $condition['authorlimit'] == '') {
            $condition['authorlimit'] = 5;
        } else {
            $condition['authorlimit'] = intval($condition['authorlimit']);
        }
        if (!isset($condition['limit']) || $condition['limit'] == '') {
            $condition['limit'] = 20;
        } else {
            $condition['limit'] = intval($condition['limit']);
        }
        $model = new Model();
        $model->load($postId);
        $ids = [];
        if ($condition['page'] == 1) {
            $theSameAuthorCollection = new PostCollection();
            $theSameAuthorCollection->columns(["id"]);
            $theSameAuthorCollection->where(['customer_id' => $model["customer_id"]])->where->notEqualTo('id', $postId);
            $theSameAuthorCollection->order(new Expression('Rand()'))->limit($condition['taglimit']);
            if (count($theSameAuthorCollection) > 0) {
                foreach ($theSameAuthorCollection as $post) {
                    $ids[] = $post["id"];
                }
            }
            if (!empty($model["tags"])) {
                $tags = explode(",", $model["tags"]);
                $theSameTagCollection = new PostCollection();
                $likeArray = [];
                foreach ($tags as $tag) {
                    if (!empty($tag)) {
                        $likeArray[] = "tags like '%" . $tag . "%'";
                    }
                }
                if (count($likeArray) > 0) {
                    $theSameTagCollection->where("customer_id!=" . $model["customer_id"] . " and (" . implode(' or ', $likeArray) . ")")->where->notEqualTo('id', $postId);
                } else {
                    $theSameTagCollection->where("customer_id!=" . $model["customer_id"])->where->notEqualTo('id', $postId);
                }
                $theSameTagCollection->order('created_at')->limit($condition['authorlimit']);
                if (count($theSameTagCollection) > 0) {
                    foreach ($theSameTagCollection as $post) {
                        $ids[] = $post["id"];
                    }
                }
            }
        }
        $postCollection = new PostCollection();
        $postCollection->columns(["id"]);
        $postCollection->join('forum_category', 'forum_post.category_id=forum_category.id', [], 'left');
        if (!empty($model["category_id"])) {
            $postCollection->where("forum_post.category_id='" . $model["category_id"] . "'");
        }
        if ($languageId == 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $language = new Language();
        $language->load($languageId);
        $resultData = [];
        $total = $postCollection->count();
        $postCollection->offset(($condition['page'] - 1) * $condition['limit'])->limit((int) $condition['limit']);
        $postCollection->order('forum_post.created_at DESC');
        $last_page = ceil($total / $condition['limit']);
        $resultData['pagination'] = [
            "total" => $total,
            "per_page" => $condition['limit'],
            "current_page" => $condition['page'],
            "last_page" => $last_page,
            "next_page" => ($last_page > $condition['page'] ? $condition['page'] + 1 : $last_page),
            "previous_page" => ($condition['page'] > 1 ? $condition['page'] - 1 : 1),
            "has_next_page" => ($last_page > $condition['page'] ? true : false),
            "has_previous_page" => ($condition['page'] > 1 && $last_page > 1 ? true : false)
        ];

        $postList = $postCollection->load(true, true);
        if (count($postList) > 0) {
            foreach ($postList as $post) {
                $ids[] = $post["id"];
            }
        }
        $resultData["posts"] = [];
        if (count($ids) > 0) {
            $resultData["posts"] = $this->getPostData($ids, $customerId, $languageId, $language["code"]);
        }
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get post list successfully'];
        return $this->responseData;
    }

}
