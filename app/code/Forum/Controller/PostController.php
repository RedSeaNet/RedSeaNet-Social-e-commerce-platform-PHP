<?php

namespace Redseanet\Forum\Controller;

use Exception;
use Gregwar\Captcha\CaptchaBuilder;
use Redseanet\Customer\Controller\AuthActionController;
use Redseanet\Forum\Model\Post;
use Redseanet\Forum\Model\Poll;
use Redseanet\Forum\Model\Poll\Option;
use Redseanet\Forum\Model\CustomerLike;
use Redseanet\Forum\Model\Post\Link;
use Redseanet\Forum\Model\Post\Favorite;
use Redseanet\Forum\Model\Tags;
use Redseanet\Forum\Model\Collection\Post\Favorite as favoriteCollection;
use Redseanet\Forum\Model\Collection\Post\Review as reviewCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Exception\FileSizeExceedLimitException;
use Laminas\Math\Rand;
use Redseanet\Resource\Lib\Factory as resourceFactory;
use Redseanet\Forum\Model\Collection\Tags as TagsCollection;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler as rpcAbstractHandler;
use Redseanet\Catalog\Model\Product;

class PostController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Forum\Traits\Breadcrumb;
    use \Redseanet\Notifications\Traits\NotificationsMethod;

    protected $allowedAction = ['index', 'uploadvideo'];

    public function dispatch($request = null, $routeMatch = null)
    {
        $cors = $this->getContainer()->get('config')['adapter']['cors'] ?? [];
        if ($cors && in_array($this->getRequest()->getUri()->getHost(), (array) $cors)) {
            $this->getResponse()->withHeader('Access-Control-Allow-Origin', $this->getRequest()->getUri()->getHost());
        }
        if ($this->getRequest()->isOptions() && $this->getRequest()->getHeader('Access-Control-Request-Method')['Access-Control-Request-Method']) {
            $this->getResponse()->withHeader('Access-Control-Allow-Methods', 'GET, POST');
            return $this->getResponse();
        } else {
            return parent::dispatch($request, $routeMatch);
        }
    }

    public function indexAction()
    {
        $post = $this->getOption('post');
        if (empty($post)) {
            return $this->notFoundAction();
        }
        if (!empty($post['poll_id'])) {
            $pollId = $post['poll_id'];
        } else {
            $pollId = $this->getRequest()->getQuery('poll', false);
        }
        $customer = new Segment('customer');
        $segment = new Segment('forum');
        if ($customer->get('hasLoggedIn')) {
            if ($segment->offsetExists('forum_like_user_id')) {
                $customer_id = $customer->get('customer')['id'];
                $user_id = $segment->get('forum_like_user_id');
                if ($customer_id != $user_id && !empty($user_id)) {
                    $model = new CustomerLike();
                    $model->setId($customer_id)->justLike($user_id);
                }
                $segment->offsetUnset('forum_like_user_id');
            }
        }
        $root = $this->getLayout('forum_post');
        $images = $post->getThumbnails();
        $root->getChild('head')->setTitle($post['title'])
                ->setDescription($post['description'])
                ->addOgMeta('og:title', $post['title'])
                ->addOgMeta('og:description', $post['description'])
                ->addOgMeta('og:type', 'article')
                ->addOgMeta('og:url', $post->getUrl())
                ->addOgMeta('og:image', (count($images) > 0 ? $this->getUploadedUrl('pub/upload/forum/' . $images[0]) : $this->getPubUrl('frontend/images/placeholder.png')));
        $root->getChild('main', true)->setVariable('post', $post);
        $root->getChild('log', true)->setVariable('post_id', $post['id']);
        $this->generateCrumbs($root->getChild('breadcrumb', true), $post->getCategory());
        if ($pollId) {
            $poll = new Poll();
            $poll->load(intval($pollId));
            $root->getChild('poll', true)->setVariable('poll', $poll);
        }
        return $root;
    }

    public function publishAction()
    {
        $segment = new Segment('customer');
        $segment->offsetUnset('form_data_forum_product_relation');
        return $this->getLayout('forum_post_publish');
    }

    public function relatedProductAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->getLayout('forum_post_related');
        }
        return $this->notFoundAction();
    }

    public function draftAction()
    {
        return $this->getLayout('forum_post_draft');
    }

    public function editAction()
    {
        $post = new Post();
        $isPoll = (bool) $this->getRequest()->getQuery('poll', false);
        if ($id = $this->getRequest()->getQuery('id', false)) {
            $post->load($id);
            if ($post->getId()) {
                $isPoll = (bool) $post['poll_id'];
            }
        }
        $root = $this->getLayout($isPoll ? 'forum_poll_edit' : 'forum_post_edit');
        if ($post->getId()) {
            $root->getChild('head')->setTitle('Edit Thread');
            $root->getChild('main', true)->setVariable('post', $post)
                    ->setVariable('title', 'Edit Thread');
        } else {
            $root->getChild('head')->setTitle($isPoll ? 'Post New Poll' : 'Post New Thread');
            $root->getChild('main', true)->setVariable($isPoll ? 'Post New Poll' : 'Post New Thread');
        }
        $segment = new Segment('customer');
        $segment->offsetUnset('form_data_forum_product_relation');
        return $root;
    }

    public function postEditAction()
    {
        $post = new Post();
        $id = $this->getRequest()->getQuery('id', false);
        if ($id) {
            $post->load($id);
        }
        $root = $this->getLayout('forum_post_edit');
        if ($post->getId()) {
            $root->getChild('head', true)->setTitle('Edit Thread');
            $root->getChild('main', true)->setVariable('post', $post);
            $segment = new Segment('customer');
            $segment->offsetUnset('form_data_forum_product_relation');
            return $root;
        }
        return $this->notFoundAction();
    }

    public function draftPublishAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Post();
                    $model->setId($data['id']);
                    $model->setData('is_draft', 0)->save();
                    $result['removeLine'] = $data['id'];
                    $result['message'][] = ['message' => $this->translate('Successful publish the current post.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Post();
                    $model->setId($data['id'])->remove();
                    $result['reload'] = 1;
                } catch (Exception $e) {
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
    }

    public function likeAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($segment->get('hasLoggedIn') && $result['error'] === 0) {
                try {
                    $model = new Post();
                    $model->load($data['id']);
                    $customer = $segment->get('customer');
                    $customerId = $customer['id'];
                    $postUrl = $model->getUrl();
                    ['data' => $model->like($customerId)];
                    $result['reload'] = 1;
                    $notificationsData = ['params' => json_encode(['postid' => $data['id'], 'customerid' => $customerId, 'urlkey' => 'customerid']), 'area' => 'forum', 'level' => 'success', 'is_app' => 1, 'status' => 0, 'customer_id' => $model['customer_id'], 'sender_id' => $customerId, 'type' => 0];
                    if (!$model->offsetGet('like')) {
                        $result['message'][] = ['message' => $this->translate('Cancel Like'), 'level' => 'success'];
                        $notificationsData['title'] = $this->translate('Your post %s just cancelled liked by %s', [$model['title'], $customer['username']]) . '.';
                        $notificationsData['content'] = $this->translate('Your post %s just cancelled liked by %s', [$model['title'], $customer['username']]) . '.';
                    } else {
                        $result['message'][] = ['message' => $this->translate('You have successfully read this post.'), 'level' => 'success'];
                        $notificationsData['title'] = $this->translate('Your post %s just liked by %s', [$model['title'], $customer['username']]) . '.';
                        $notificationsData['content'] = $this->translate('Your post %s just liked by %s', [$model['title'], $customer['username']]) . '.';
                    }
                    $this->addNotifications($notificationsData);
                } catch (Exception $e) {
                    $this->rollback();
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
            return $this->response($result ?? ['error' => 0, 'message' => []], 'forum/post/', 'forum');
        }
        return $this->notFoundAction();
    }

    public function collectAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($segment->get('hasLoggedIn') && $result['error'] === 0) {
                try {
                    $model = new Post();
                    $customerId = $segment->get('customer')['id'];
                    ['data' => $model->setId($data['id'])->collect($customerId)];
                    $result['reload'] = 1;
                    $notificationsData = ['url' => $model->getUrl(), 'area' => 'forum', 'level' => 'success', 'is_app' => 1, 'status' => 0, 'customer_id' => $model['customer_id'], 'sender_id' => $customerId, 'type' => 0];

                    if (!$model->offsetGet('collections')) {
                        $result['message'][] = ['message' => $this->translate('You have cancelled the collection of current posts.'), 'level' => 'success'];
                        $notificationsData['title'] = $this->translate('Your post has been cancelled favorited');
                        $notificationsData['content'] = $this->translate('Your post has been cancelled favorited');
                    } else {
                        $result['message'][] = ['message' => $this->translate('You have succeeded in collecting this post.'), 'level' => 'success'];
                        $notificationsData['title'] = $this->translate('Your post has been favorited');
                        $notificationsData['content'] = $this->translate('Your post has been favorited');
                    }
                    $this->addNotifications($notificationsData);
                } catch (Exception $e) {
                    $this->rollback();
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
            return $this->response($result ?? ['error' => 0, 'message' => []], 'forum/post/', 'forum');
        }
        return $this->notFoundAction();
    }

    public function dislikeAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($segment->get('hasLoggedIn') && $result['error'] === 0) {
                try {
                    $model = new Post();
                    ['data' => $model->setId($data['id'])->dislike($segment->get('customer')['id'])];
                    $result['reload'] = 1;
                } catch (Exception $e) {
                }
            }
            return [];
        }
        return $this->notFoundAction();
    }

    public function voteAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $config = $this->getContainer()->get('config');
            $result = $this->validateForm($data, ['option_id', 'poll_id']);
            if ($result['error'] === 0) {
                try {
                    $poll = new Poll();
                    $poll->load(intval($data['poll_id']));
                    if ($poll->voted()) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('You have already voted.'), 'level' => 'danger'];
                    } else {
                        $poll->vote($data['option_id']);
                        $result['message'][] = ['message' => $this->translate('You have voted successfully.'), 'level' => 'success'];
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
    }

    public function searchAction()
    {
        $root = $this->getLayout('forum_search' . ($this->getRequest()->isXmlHttpRequest() ? '_ajax' : ''));
        return $root;
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $config = $this->getContainer()->get('config');
            $required = ['title', 'content'];
            if ($config['forum/post/description'] == 1) {
                $required[] = 'description';
            }
            if ($config['forum/post/anonymous'] == 0) {
                unset($data['anonymous']);
            }
            $result = $this->validateForm($data, $required, $config['forum/post/captcha'] ? 'forum_post' : false);
            $customer = (new Segment('customer'))->get('customer');
            if ((isset($customer['forum_banned']) && $customer['forum_banned']) || !empty($data['id']) && (new Post())->load($data['id'])['customer_id'] !== $customer['id']) {
                $result['error'] = 1;
                $result['message'][] = ['message' => 'You are not allowed to post. Please contact us if you have any doubt.', 'level' => 'danger'];
            }
            if ($result['error'] === 0) {
                if (empty($data['category'])) {
                    $data['category_id'] = 1;
                } else {
                    $data['category_id'] = $data['category'];
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
                $data['customer_id'] = $customer['id'];
                $data['language_id'] = Bootstrap::getLanguage()->getId();
                $data['status'] = $config['forum/post/status'];
                try {
                    $this->beginTransaction();
                    $images = [];
                    $path = BP . 'pub/upload/forum/';
                    if (!is_dir($path)) {
                        mkdir($path, 0644, true);
                    }
                    if (isset($data['uploaded']) && !empty($data['uploaded'])) {
                        foreach ($data['uploaded'] as $item) {
                            $images[] = $item;
                        }
                    }
                    $count = 0;
                    $files = $this->getRequest()->getUploadedFile();
                    if (!empty($files['image'][0])) {
                        foreach ($files['image'] as $file) {
                            if ($file[0]->getError() === UPLOAD_ERR_OK && $count++ < 5) {
                                if ($file[0]->getSize() > 2097152) {
                                    throw new FileSizeExceedLimitException('The size of the uploaded file exceed the limitation.');
                                }
                                $newName = $file[0]->getClientFilename();
                                while (file_exists($path . $newName)) {
                                    $newName = preg_replace('/(\.[^\.]+$)/', random_int(0, 9) . '$1', $newName);
                                    if (strlen($newName) >= 120) {
                                        throw new Exception('The file is existed.');
                                    }
                                }
                                $file[0]->moveTo($path . $newName);
                                $images[] = $newName;
                            }
                        }
                    }
                    if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
                        $aliyunConfig = resourceFactory::getAliYunOSSConfig();
                        $aliyunVideoConfig = $aliyunConfig;
                        for ($uploaded_image = 0; $uploaded_image < count($images); $uploaded_image++) {
                            $aliyunConfig['localfilepath'] = $path . $images[$uploaded_image];
                            $aliyunConfig['ossobject'] = 'pub/upload/forum/' . $images[$uploaded_image];
                            resourceFactory::aliYunOSSMoveFile($aliyunConfig);
                        }
                    } elseif (isset($config['resource/server/service']) && $config['resource/server/service'] == 'awss3') {
                        $awsConfig = resourceFactory::getAwsS3Config();
                        $awsVideoConfig = $awsConfig;
                        for ($uploaded_image = 0; $uploaded_image < count($images); $uploaded_image++) {
                            $awsConfig['localfilepath'] = $path . $images[$uploaded_image];
                            $awsConfig['ossobject'] = 'pub/upload/forum/' . $images[$uploaded_image];
                            resourceFactory::awss3MoveFile($awsConfig);
                        }
                    }

                    $data['images'] = json_encode($images);
                    $video = '';
                    $videosPath = BP . 'pub/upload/forum/videos/';
                    if (!is_dir($videosPath)) {
                        mkdir($videosPath, 0644, true);
                    }
                    if (!empty($files['video'])) {
                        if ($files['video']->getError() === UPLOAD_ERR_OK && $count++ < 5) {
                            if ($files['video']->getSize() > 209715200) {
                                throw new FileSizeExceedLimitException('The size of the uploaded file exceed the limitation.');
                            }
                            $videoNewName = $files['video']->getClientFilename();
                            while (file_exists($videosPath . $videoNewName)) {
                                $videoNewName = preg_replace('/(\.[^\.]+$)/', random_int(0, 9) . '$1', $videoNewName);
                                if (strlen($videoNewName) >= 120) {
                                    throw new Exception('The file is existed.');
                                }
                            }
                            $files['video']->moveTo($videosPath . $videoNewName);
                            $video = $videoNewName;
                            if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
                                $aliyunVideoConfig['localfilepath'] = $videosPath . $videoNewName;
                                $aliyunVideoConfig['ossobject'] = 'pub/upload/forum/videos/' . $videoNewName;
                                resourceFactory::aliYunOSSMoveFile($aliyunVideoConfig);
                            } elseif (isset($config['resource/server/service']) && $config['resource/server/service'] == 'awss3') {
                                $awsVideoConfig['localfilepath'] = $videosPath . $videoNewName;
                                $awsVideoConfig['ossobject'] = 'pub/upload/forum/videos/' . $videoNewName;
                                resourceFactory::awss3MoveFile($awsVideoConfig);
                            }
                        }
                    }
                    $data['videos'] = $video;

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
                        if (count($newPoll['description']) > 0) {
                            $pollData = ['max_choices' => $newPoll['max_choices'], 'title' => $newPoll['title']];

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
                    $model = new Post($data);
                    $model->save();

                    if (isset($data['tags']) && is_array($data['tags']) && count($data['tags']) > 0) {
                        $tagsTableGateway = $this->getTableGateway('forum_post_tags_post');
                        $tagsTableGateway->delete(['post_id' => $model->getId()]);
                        for ($t = 0; $t < count($data['tags']); $t++) {
                            $tagsTableGateway->insert(['post_id' => $model->getId(), 'tag_id' => (int) $data['tags'][$t]]);
                        }
                        $this->flushList('forum_post_tags_post');
                    }
                    if (isset($data['link_name']) && is_array($data['link_name']) && count($data['link_name']) > 0) {
                        $linkTableGateway = $this->getTableGateway('forum_post_recommend_link');
                        $linkTableGateway->delete(['post_id' => $model->getId()]);
                        for ($l = 0; $l < count($data['link_name']); $l++) {
                            if (isset($data['link_name'][$l]) && isset($data['link_url'][$l]) && $data['link_name'][$l] != '' && $data['link_url'][$l] != '') {
                                $linkModel = new Link(['post_id' => $model->getId(), 'name' => $data['link_name'][$l], 'link' => $data['link_url'][$l]]);
                                $linkModel->save();
                            }
                        }
                        $this->flushList('forum_post_recommend_link');
                    }
                    if (isset($data['forum_product_relation'])) {
                        $tableGateway = $this->getTableGateway('forum_product_relation');
                        if (count($data['forum_product_relation'])) {
                            $tableGateway->delete(['post_id' => $model->getId()]);
                        }
                        foreach ($data['forum_product_relation'] as $key => $value) {
                            if ((int) $value != 0) {
                                $tableGateway->insert([
                                    'post_id' => $model->getId(),
                                    'product_id' => (int) $value
                                ]);
                            }
                        }
                        $this->flushList('forum_product_relation');
                    }
                    $this->commit();
                    $this->flushList('post');
                    $this->flushList(Product::ENTITY_TYPE);
                    $result['reload'] = 1;
                    $result['message'][] = ['message' => $this->translate($data['status'] ? 'The thread has been posted successfully.' : 'The thread has been posted successfully. It would been viewed after approval.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->rollback();
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
        }
        $redirectUrl = 'forum/account/';
        if ($data['is_draft'] == 1) {
            $redirectUrl = 'forum/post/draft/';
        }
        return $this->response($result, $redirectUrl, 'forum');
    }

    public function removeAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                $model = new Post();
                try {
                    $model->setId($data['id'])->remove();
                    $result['reload'] = 1;
                    $result['message'][] = ['message' => $this->translate('Successful deletion of current post.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
        }

        return $this->response($result, 'forum/account/', 'forum');
    }

    public function captchaAction()
    {
        $config = $this->getContainer()->get('config');
        $phrase = Rand::getString($config['customer/captcha/number'], $config['customer/captcha/symbol']);
        $file = BP . 'var/captcha/' . md5($phrase) . '.jpg';
        if (file_exists($file)) {
            $result = file_get_contents($file);
        } else {
            if (!is_dir(BP . 'var/captcha')) {
                mkdir(BP . 'var/captcha', 0777);
            }
            $builder = new CaptchaBuilder($phrase);
            $builder->setBackgroundColor(0xff, 0xff, 0xff);
            $builder->build(105, 39);
            $builder->save($file);
            $result = $builder->get();
        }
        $segment = new Segment('forum_post');
        $segment->set('captcha', strtoupper($phrase));
        $this->getResponse()
                ->withHeader('Content-type', 'image/jpeg')
                ->withHeader('Cache-Control', 'no-store');
        return $result;
    }

    public function validCaptchaAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && ($id = $this->getRequest()->getQuery('captcha', ''))) {
            return $this->validateCaptcha($id, 'forum_post') ? 'true' : 'false';
        }
        return $this->notFoundAction();
    }

    public function impeachAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Post();
                    $model->setData(['id' => $data['id'], 'status' => 3])->save();
                } catch (Exception $ex) {
                }
                exit();
            }
        }
        return $this->notFoundAction();
    }

    public function favoriteAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($segment->get('hasLoggedIn') && $result['error'] === 0) {
                try {
                    $model = new Post();
                    $customer = $segment->get('customer');
                    $customerId = $customer['id'];
                    $model->load($data['id']);
                    $postUrl = $model->getUrl();
                    $favoriteData = $model->favorite($customerId);
                    $result['reload'] = 1;
                    $notificationsData = ['params' => json_encode(['postid' => $data['id'], 'customerid' => $customerId, 'urlkey' => 'customerid']), 'area' => 'forum', 'level' => 'success', 'is_app' => 1, 'status' => 0, 'customer_id' => $model['customer_id'], 'sender_id' => $customerId, 'type' => 0];
                    if ($favoriteData) {
                        $result['message'][] = ['message' => $this->translate('You have successfully favorite this post.'), 'level' => 'success'];
                        $notificationsData['title'] = $this->translate('Your post %s just favorited by %s', [$model['title'], $customer['username']]) . '.';
                        $notificationsData['content'] = $this->translate('Your post %s just favorited by %s', [$model['title'], $customer['username']]) . '.';
                    } else {
                        $result['message'][] = ['message' => $this->translate('Cancel favorite'), 'level' => 'success'];
                        $notificationsData['title'] = $this->translate('Your post %s just cancelled favorited by %s', [$model['title'], $customer['username']]) . '.';
                        $notificationsData['content'] = $this->translate('Your post %s just cancelled favorited by %s', [$model['title'], $customer['username']]) . '.';
                    }
                    $this->addNotifications($notificationsData);
                } catch (Exception $e) {
                    $this->rollback();
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
            return $this->response($result ?? ['error' => 0, 'message' => []], 'forum/post/', 'forum');
        }
        return $this->notFoundAction();
    }

    public function getTagsAction()
    {
        $tags = new TagsCollection();
        $query = $this->getRequest()->getQuery();
        $tags->withName();
        $tags->where(['forum_post_tags.sys_recommended' => 1]);
        if (isset($query['term']) && $query['term'] != '') {
            $tags->where('forum_post_tags_language.name like "%' . $query['term'] . '%"');
        }
        $result = [];
        foreach ($tags as $tag) {
            $result[] = $tag->name;
        }
        return json_encode($result);
    }

    public function getAwsS3PresiginUrlAction()
    {
        if ($this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['files']);
            $returnData = [];
            if ($segment->get('hasLoggedIn') && $result['error'] === 0) {
                $customerId = $segment->get('customer')['id'];
                if (is_array($data['files']) && count($data['files']) > 0) {
                    $newFiles = [];
                    for ($f = 0; $f < count($data['files']); $f++) {
                        $newName = 'post-' . $customerId . '-' . date('YmdHis') . '-' . mt_rand(10000, 99999) . '.' . substr(strrchr($data['files'][$f]['key'], '.'), 1);
                        if (substr($data['files'][$f]['type'], 0, strpos($data['files'][$f]['type'], '/') + 1) == 'video/') {
                            $tmpFile = $data['files'][$f];
                            $tmpFile['key'] = 'pub/upload/forum/' . $customerId . '/original-videos/' . $newName;
                            $tmpFile['newName'] = $newName;
                            $tmpFile['originalName'] = $data['files'][$f]['key'];
                            $newFiles[] = $tmpFile;
                        } else {
                            $tmpFile = $data['files'][$f];
                            $tmpFile['key'] = 'pub/upload/forum/' . $customerId . '/' . $newName;
                            $tmpFile['newName'] = $newName;
                            $tmpFile['originalName'] = $data['files'][$f]['key'];
                            $newFiles[] = $tmpFile;
                        }
                    }
                    $preSignUrl = resourceFactory::getAwsS3PresignedUrl($newFiles);
                    $returnData['preSignUrl'] = $preSignUrl;
                }
            }
            return $returnData;
        }
        return [];
    }

    public function uploadvideoAction()
    {
        $data = $this->getRequest()->getPost();
        if (empty($data['id']) || empty($data['token'])) {
            $responseData = ['statusCode' => '403', 'data' => [], 'message' => 'id, token can not be null'];
            return json_encode($responseData);
        }
        $rpcAbstract = new rpcAbstractHandler();
        $responseData = $rpcAbstract->validateToken($data['id'], $data['token'], __FUNCTION__, false);
        if ($responseData['statusCode'] != '200') {
            return json_encode($responseData);
        }
        try {
            $files = $this->getRequest()->getUploadedFile();

            $video = '';
            $videosPath = BP . 'pub/upload/forum/videos/';
            if (!is_dir($videosPath)) {
                mkdir($videosPath, 0644, true);
            }
            if (!empty($files['video'])) {
                if ($files['video']->getSize() > 209715200) {
                    $responseData = ['statusCode' => '400', 'data' => [], 'message' => 'The size of the uploaded file exceed the limitation.'];
                    return json_encode($responseData);
                }
                $videoNewName = $files['video']->getClientFilename();
                $files['video']->moveTo($videosPath . $videoNewName);
                $video = $videoNewName;
                if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
                    $aliyunVideoConfig['localfilepath'] = $videosPath . $videoNewName;
                    $aliyunVideoConfig['ossobject'] = 'pub/upload/forum/videos/' . $videoNewName;
                    resourceFactory::aliYunOSSMoveFile($aliyunVideoConfig);
                } elseif (isset($config['resource/server/service']) && $config['resource/server/service'] == 'awss3') {
                    $awsVideoConfig['localfilepath'] = $videosPath . $videoNewName;
                    $awsVideoConfig['ossobject'] = 'pub/upload/forum/videos/' . $videoNewName;
                    resourceFactory::awss3MoveFile($awsVideoConfig);
                }
            }
            $responseData = ['statusCode' => '200', 'data' => ['video' => $videoNewName], 'message' => 'upload successfully'];
        } catch (Exception $e) {
            $responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please contact us or try again later'];
            return json_encode($responseData);
        }
        return json_encode($responseData);
    }

    public function jsonAction()
    {
        //if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
        //$data = $this->getRequest()->getPost();
        $data = $this->getRequest()->getQuery();

        if (!empty($data['id'])) {
            $post = new Post();
            $post->load($data['id']);
            $author = $post->getCustomer();
            $images = $post->getThumbnails();
            $video = $post->getVideo();
            $reviews = $post->getReviews();
            $count = $post->getLikeCount();
            $session = new Segment('customer');
            $customer = $session->get('customer');
            $customer_id = !empty($customer['id']) ? $customer['id'] : 0;
            $liked = $post->liked($customer_id);
            $favorited = $post->favorited($customer_id);
            $postUrl = $post->getUrl();
            if ($video != '') {
                if (isset($type) && $type == 'image') {
                    $type = 'image';
                } elseif (isset($type) && $type == 'video') {
                    $type = 'video';
                } else {
                    $type = 'video';
                }
            } else {
                $type = 'image';
            }
            $linksa = '';
            foreach ($post->getLinks() as $link) {
                $link_url = $post->checkLink($link['link']);
                $linksa .= '&nbsp;<a href="' . $link_url . '" target="_blank" class="sys-blue sys-underline">' . $link['name'] . '</a>';
            }
            $tagsa = '';
            if (!empty($post['tags'])) {
                $tags = array_filter(explode(',', $post['tags']));
                if (count($tags) > 0) {
                    foreach ($tags as $tag) {
                        $tagsa .= '&nbsp;#' . $tag . '';
                    }
                }
            }
            $parsedImagesUrl = [];
            if (isset($images) && is_array($images)) {
                foreach ($images as $image) {
                    $parsedImagesUrl[] = $this->getUploadedUrl('forum/' . $image);
                }
            }
            $video_screenshot = '';
            if ($post['videos_screenshot']) {
                $video_screenshot = $this->getUploadedUrl('forum/videosscreenshot/' . $post['videos_screenshot']);
            }
            $video_source = '';
            $video_msg = '';
            $video360_source = '';
            $video1080_source = '';
            if ($type === 'video') {
                $video_source = $this->getUploadedUrl('forum/videos/' . $post['videos']);
                if ((strtotime($post['created_at']) + 900 > time())) {
                    $video_msg = $this->translate('If the video can not load, because you just published the video post, The serve need to deal the video, Please waiting');
                }
                $video360_source = $video_source;
                $video1080_source = $video_source;
            }

            $parsedReviews = [];
            if (isset($reviews)) {
                foreach ($reviews as $review) {
                    $reviewer = $review->getCustomer();
                    $parsedReviews[] = [
                        'id' => $review['id'],
                        'post_id' => $review['post_id'],
                        'reviewer' => [
                            'customer_id' => $review['customer_id'],
                            'username' => $reviewer['username'],
                            'avatar' => $reviewer['avatar'] != '' ? $this->getUploadedUrl('pub/upload/customer/avatar/' . $reviewer['avatar']) : $this->getPubUrl('frontend/images/avatar-holderplace.jpg'),
                            'space_url' => $this->getBaseUrl('forum/space/' . $reviewer['username']),
                        ],
                        'reference' => $review['reference'],
                        'subject' => $review['subject'],
                        'content' => $review['content'],
                        'like' => $review['like'],
                        'dislike' => $review['dislike'],
                        'anonymous' => $review['anonymous'],
                        'status' => $review['status'],
                        'is_top' => $review['is_top'],
                        'created_at' => $review['created_at'],
                        'updated_at' => $review['updated_at']
                    ];
                }
            }
            // poll
            $pollData = [];
            if ('' != $post['poll_id'] && $customer_id !== 0) {
                $pollId = $post['poll_id'];
                $poll = new Poll();
                $poll->load(intval($pollId));
                $optionsData = [];
                foreach ($poll->getOptions() as $option) {
                    $count = count($option->getVoters());
                    $optionsData[] = [
                        'id' => $option['id'],
                        'description' => $option['description'],
                        'total_votes' => $count
                    ];
                }
                $pollData = [
                    'id' => $post['poll_id'],
                    'title' => $poll['title'],
                    'max_choices' => intval($poll['max_choices']),
                    'total_votes' => count($poll->getVoters()),
                    'is_voted' => $poll->voted(),
                    'selected' => $poll->getOptionsByCustomer(),
                    'options' => $optionsData
                ];
            }
            $responseData = [
                'locale' => [
                    'To subscribe' => $this->translate('To subscribe'),
                    'Forward' => $this->translate('Forward'),
                    'Reply to' => $this->translate('Reply to'),
                    'Like' => $this->translate('Like'),
                    'Favited' => $this->translate('Favited'),
                    'Dislike' => $this->translate('Dislike'),
                    'Reply' => $this->translate('Reply'),
                    'Remove' => $this->translate('Remove'),
                    'Tips' => $this->translate('Tips'),
                    'Has tipped' => $this->translate('Has tipped'),
                    'Maximum Choices' => $this->translate('Maximum Choices'),
                    'Release your Comments' => $this->translate('Release your Comments'),
                    'No review available right now.' => $this->translate('No review available right now.'),
                    'Are you sure to delete this record?' => $this->translate('Are you sure to delete this record?'),
                    'You have now reached your daily view limit (20 posts)' => $this->translate('You have now reached your daily view limit (20 posts)')
                ],
                'customer' => [
                    'id' => $customer_id,
                    'is_liked' => $liked,
                    'is_favorited' => $favorited
                ],
                'author' => [
                    'id' => $author->id,
                    'avatar' => $author['avatar'] != '' ? $this->getUploadedUrl('pub/upload/customer/avatar/' . $author['avatar']) : $this->getPubUrl('frontend/images/avatar-holderplace.jpg'),
                    'username' => $author->username,
                    'space_url' => $this->getBaseUrl('forum/space/' . $author->username)
                ],
                'post' => [
                    'id' => $post->id,
                    'poll_id' => $post['poll_id'],
                    'url' => $postUrl,
                    'anonymous' => $post->anonymous,
                    'can_review' => $post->can_review,
                    'category_id' => $post->category_id,
                    'collections' => $post->collections,
                    'content' => $post->content,
                    'created_at' => $post->created_at,
                    'updated_at' => $post->updated_at,
                    'customer_id' => $post->customer_id,
                    'description' => $post->description,
                    'dislike' => $post->dislike,
                    'fee' => $post->fee,
                    'is_draft' => $post->is_draft,
                    'is_hot' => $post->is_hot,
                    'is_relate' => $post->is_relate,
                    'is_top' => $post->is_top,
                    'language_id' => $post->language_id,
                    'like' => $post->like,
                    'status' => $post->status,
                    'tags' => $post->tags,
                    'who_can_view' => $post->who_can_view,
                    'title' => $post->title,
                    'visitor_count' => $post['visitor_count'],
                    'images' => [
                        'placeholder_image' => $this->getPubUrl('frontend/images/placeholder.png'),
                        'image_source' => $parsedImagesUrl
                    ],
                    'video' => [
                        'video_source' => $video_source,
                        'video360_source' => $video360_source,
                        'video1080_source' => $video1080_source,
                        'video_screenshot' => $video_screenshot,
                        'msg' => $video_msg
                    ],
                    'type' => $type,
                    'links' => $linksa
                ],
                'poll' => $pollData,
                'reviews' => $parsedReviews
            ];

            return $responseData;
        }
        //}
        //return $this->notFoundAction();
    }
}
