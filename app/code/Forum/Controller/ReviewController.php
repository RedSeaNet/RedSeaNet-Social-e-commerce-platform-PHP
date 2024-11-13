<?php

namespace Redseanet\Forum\Controller;

use Exception;
use Gregwar\Captcha\CaptchaBuilder;
use Redseanet\Customer\Controller\AuthActionController;
use Redseanet\Forum\Model\Post;
use Redseanet\Forum\Model\Post\Review;
use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;

class ReviewController extends AuthActionController
{
    use \Redseanet\Notifications\Traits\NotificationsMethod;

    protected $allowedAction = ['index'];

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
        if ($this->getRequest()->isXmlHttpRequest() && ($id = $this->getRequest()->getQuery('id'))) {
            $root = $this->getLayout('forum_review');
            $content = $root->getChild('content');
            foreach ($content->getChild() as $child) {
                $child->setVariable('post_id', $id);
            }
            return $root;
        }
        return $this->notFoundAction();
    }

    public function likeAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && $this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($segment->get('hasLoggedIn') && $result['error'] === 0) {
                try {
                    $model = new Review();
                    return ['data' => $model->setId($data['id'])->like($segment->get('customer')['id'])];
                } catch (Exception $e) {
                }
            }
            return [];
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
                    $model = new Review();
                    return ['data' => $model->setId($data['id'])->dislike($segment->get('customer')['id'])];
                } catch (Exception $e) {
                }
            }
            return [];
        }
        return $this->notFoundAction();
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $config = $this->getContainer()->get('config');
            $required = ['post_id', 'content'];
            if ($config['forum/review/subject'] == 1) {
                $required[] = 'subject';
            }
            if ($config['forum/review/anonymous'] == 0) {
                unset($data['anonymous']);
            }
            $result = $this->validateForm($data, $required, $config['forum/review/captcha'] ? 'forum_review' : false);
            $customer = (new Segment('customer'))->get('customer');
            if ((!empty($customer['forum_banned']) && $customer['forum_banned']) || !empty($data['id']) && (new Review())->load($data['id'])['customer_id'] !== $customer['id']) {
                $result['error'] = 1;
                $result['message'][] = ['message' => 'You are not allowed to review. Please contact us if you have any doubt.', 'level' => 'danger'];
            }
            if ($result['error'] === 0) {
                $post = new Post();
                $post->load($data['post_id']);
                $postUrl = $post->getUrl();
                if ($post['can_review']) {
                    $data['customer_id'] = $customer['id'];
                    $data['status'] = $config['forum/review/status'];
                    try {
                        $model = new Review($data);
                        $model->save();
                        if ($customer['id'] != $post['customer_id']) {
                            $notificationsData = ['params' => json_encode(['postid' => $data['post_id'], 'customerid' => $customer['id'], 'urlkey' => 'customerid']), 'area' => 'forum', 'level' => 'success', 'is_app' => 1, 'status' => 0, 'customer_id' => $post['customer_id'], 'sender_id' => $customer['id'], 'type' => 0];
                            $notificationsData['title'] = $this->translate('Your post %s just has been reviewed by %s', [$post['title'], $customer['username']]) . '.';
                            $notificationsData['content'] = $this->translate('Your post %s just has been reviewed by %s', [$post['title'], $customer['username']]) . '.';
                            $this->addNotifications($notificationsData);
                        }
                        if (!empty($data['reference']) && $customer['id'] != $post['customer_id']) {
                            $modelrRference = new Review();
                            $modelrRference->load($data['reference']);
                            if (!empty($modelrRference['customer_id']) && $modelrRference['customer_id'] != $post['customer_id'] && $modelrRference['customer_id'] != $data['customer_id']) {
                                $notificationsData = ['params' => json_encode(['postid' => $data['id'], 'customerid' => $customer['id'], 'urlkey' => 'customerid']), 'area' => 'forum', 'level' => 'success', 'is_app' => 1, 'status' => 0, 'customer_id' => $modelrRference['customer_id'], 'sender_id' => $customer['id'], 'type' => 0];
                                $notificationsData['title'] = $this->translate('Your review just has been replied by %s in %s', [$customer['username'], $post['title']]) . '.';
                                $notificationsData['content'] = $this->translate('Your review just has been replied by %s in %s', [$customer['username'], $post['title']]) . '.';
                                $this->addNotifications($notificationsData);
                            }
                        }
                        $result['message'][] = ['message' => $this->translate($data['status'] ? 'The review has been posted successfully.' : 'The review has been posted successfully. It would been viewed after approval.'), 'level' => 'success'];
                    } catch (Exception $e) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                    }
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
    }

    public function removeAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $model = new Review();
                    $model->setId($data['id'])->remove();
                    $result['removeLine'] = $data['id'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'forum');
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
        $segment = new Segment('forum_review');
        $segment->set('captcha', strtoupper($phrase));
        $this->getResponse()
                ->withHeader('Content-type', 'image/jpeg')
                ->withHeader('Cache-Control', 'no-store');
        return $result;
    }

    public function validCaptchaAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && ($id = $this->getRequest()->getQuery('captcha', ''))) {
            return $this->validateCaptcha($id, 'forum_review') ? 'true' : 'false';
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
                    $model = new Review();
                    $model->setData(['id' => $data['id'], 'status' => 3])->save();
                } catch (Exception $ex) {
                }
                exit();
            }
        }
        return $this->notFoundAction();
    }

    public function getReviewAction()
    {
        $post_id = $this->getRequest()->getQuery('post_id');
        $post = new Post();
        $post->load($post_id);
        $segment = new Segment('customer');
        $customer = $segment->get('customer');
        $current = $segment->get('hasLoggedIn') ? $customer['id'] : 0;
        $reviews = $post->getReviews();
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
                'Maximum Choices' => $this->translate('Maximum Choices'),
                'Release your Comments' => $this->translate('Release your Comments'),
                'No review available right now.' => $this->translate('No review available right now.'),
                'Are you sure to delete this record?' => $this->translate('Are you sure to delete this record?')
            ],
            'customer' => [
                'id' => $current,
            ],
            'post' => [
                'id' => $post_id,
            ],
            'reviews' => $parsedReviews,
        ];
        return $responseData;
    }
}
