<?php

namespace Redseanet\Customer\Controller;

use Exception;
use Gregwar\Captcha\PhraseBuilder;
use Gregwar\Captcha\CaptchaBuilder;
use Redseanet\Customer\Model\Address;
use Redseanet\Customer\Model\Collection\Customer as Collection;
use Redseanet\Customer\Model\Customer as Model;
use Redseanet\Customer\Model\Persistent;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Session\Segment;
use Laminas\Db\Sql\Where;
use Laminas\Math\Rand;
use Redseanet\Resource\Lib\Factory as resourceFactory;
use PHPMailer\PHPMailer\Exception as EmailException;

class AccountController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\Lib\Traits\Rabbitmq;

    protected $allowedAction = ['login', 'loginpost', 'forgotpwd', 'forgotpwdpost', 'captcha', 'confirm', 'resendconfirmemail'];

    public function __construct()
    {
        if ($this->getContainer()->get('config')['customer/registion/enabled']) {
            $this->allowedAction = array_merge($this->allowedAction, ['create', 'createpost']);
        }
    }

    public function doDispatch($method = 'notFoundAction')
    {
        $action = strtolower(substr($method, 0, -6));
        $session = new Segment('customer');
        if (!in_array($action, $this->allowedAction) && !$session->get('hasLoggedIn', false)) {
            return $this->redirect('customer/account/login/');
        } elseif (in_array($action, $this->allowedAction) && $session->get('hasLoggedIn', false)) {
            if ($url = $this->getRequest()->getQuery('success_url')) {
                $data['success_url'] = urldecode($url);
                $customer = $session->get('customer');
                $data['data'] = ['id' => $customer['id'], 'username' => $customer['username'], 'email' => $customer['email']];
                if ($this->useSso($data)) {
                    return $this->redirect($data['success_url']);
                }
            }
            return $this->redirect('customer/account/');
        }
        return ActionController::doDispatch($method);
    }

    public function createAction()
    {
        return $this->getLayout('customer_account_create');
    }

    public function loginAction()
    {
        return $this->getLayout('customer_account_login');
    }

    public function forgotPwdAction()
    {
        return $this->getLayout('customer_account_forgotpwd');
    }

    private function sendMail($template, $to, $params = [])
    {
        $config = $this->getContainer()->get('config');
        $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
        if (!empty($fromEmail)) {
            $collection = new TemplateCollection();
            $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                    ->where([
                        'code' => $config[$template],
                        'language_id' => Bootstrap::getLanguage()->getId()
                    ]);
            if (!is_array($to)) {
                $to = [$to, null];
            }
            if (count($collection)) {
                $mailer = $this->getContainer()->get('mailer');
                try {
                    $mailTemplate = new TemplateModel($collection[0]);
                    $recipients = [];
                    $recipients[] = [$to[0], $to[1]];
                    $subject = $mailTemplate['subject'];
                    $content = $mailTemplate->getContent($params);
                    $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
                    $mailer->send($recipients, $subject, $content, [], '', '', [], true, '', $from);
                } catch (EmailException $e) {
                    $this->getContainer()->get('log')->logException($e);
                }
            }
        }
    }

    public function captchaAction()
    {
        $config = $this->getContainer()->get('config');
        $builder = new CaptchaBuilder(null, new PhraseBuilder($config['customer/captcha/number'], $config['customer/captcha/symbol']));
        $builder->setBackgroundColor(0xff, 0xff, 0xff);
        $builder->build(105, 39);
        $segment = new Segment('customer');
        $segment->set('captcha', strtoupper($builder->getPhrase()));
        $this->getResponse()
                ->withHeader('Content-type', 'image/jpeg')
                ->withHeader('Cache-Control', 'no-store');
        return $builder->get();
    }

    public function createPostAction()
    {
        $config = $this->getContainer()->get('config');
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $attributes = new Attribute();
            $attributes->withSet()->where(['attribute_set_id' => $config['customer/registion/set']])
                    ->where('(is_required=1 OR is_unique=1)')
                    ->columns(['code', 'is_required', 'is_unique', 'type_id'])
                    ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id', [], 'right')
                    ->where(['eav_entity_type.code' => Model::ENTITY_TYPE]);
            $required = [];
            $unique = [];
            foreach ($attributes as $attribute) {
                if ($attribute['is_required']) {
                    $required[] = $attribute['code'];
                }
                if ($attribute['is_unique']) {
                    $unique[] = $attribute['code'];
                }
            }
            $result = $this->validateForm($data, $required, in_array('register', $config['customer/captcha/form']) ? 'customer' : false);
            if (!isset($data['cpassword']) || $data['password'] !== $data['cpassword']) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('The confirmed password is not equal to the password.'), 'level' => 'danger'];
            }
            $collection = new Collection();
            $collection->columns($unique);
            foreach ($unique as $code) {
                if (isset($data[$code])) {
                    $collection->where([$code => $data[$code]], 'OR');
                }
            }
            if (count($collection)) {
                foreach ($collection as $item) {
                    foreach ($unique as $code) {
                        if (isset($item[$code]) && isset($data[$code]) && ($item[$code] == $data[$code])) {
                            $result['error'] = 1;
                            $result['message'][] = ['message' => $this->translate('The %s field has been used.', [$this->translate(ucfirst($code))]), 'level' => 'danger'];
                        }
                    }
                    break;
                }
            }
            if ($result['error'] === 0) {
                $customer = new Model();
                $status = $config['customer/registion/confirm'];
                $languageId = Bootstrap::getLanguage()->getId();
                if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $data['avatar'], $fileResult)) {
                    $type = $fileResult[2];
                    if (!is_dir(BP . 'pub/upload/customer/avatar')) {
                        mkdir(BP . 'pub/upload/customer/avatar', 0777, true);
                    }
                    $name = 'avatar-' . date('YMd') . mt_rand(10000, 99999) . '.' . $type;
                    $path = BP . 'pub/upload/customer/avatar/' . $name;
                    if (file_put_contents($path, base64_decode(str_replace($fileResult[1], '', $data['avatar'])))) {
                        $data['avatar'] = $name;
                        resourceFactory::uploadFileToClound($path, 'pub/upload/customer/avatar/' . $name, $name);
                    }
                }
                $customer->setData([
                    'id' => null,
                    'attribute_set_id' => $config['customer/registion/set'],
                    'group_id' => $config['customer/registion/group'],
                    'type_id' => $attributes[0]['type_id'],
                    'store_id' => Bootstrap::getStore()->getId(),
                    'language_id' => $languageId,
                    'status' => 1
                ] + $data);
                $data['status'] = 1;
                $token = Rand::getString(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
                $data['token'] = $token;
                $language_id = Bootstrap::getLanguage()->getId();
                if ($status) {
                    $customer->setData([
                        'confirm_token' => $token,
                        'confirm_token_created_at' => date('Y-m-d H:i:s'),
                        'status' => 0
                    ])->save();
                    $url = 'customer/account/login/';
                    $result['message'][] = ['message' => $this->translate('You will receive an email with a confirming link.'), 'level' => 'success'];
                    $result['cookie'] = [['key' => 'referer', 'value' => '', 'path' => '/', 'expires' => 1]];
                    $data['status'] = 0;
                } else {
                    $customer->save();
                    $customer->login($data['username'], $data['password']);
                    $result['data'] = ['id' => $customer['id'], 'username' => $data['username'], 'email' => $customer['email']];
                    $url = 'customer/account/';
                    $result['message'][] = ['message' => $this->translate('Thanks for your registion.'), 'level' => 'success'];
                    $result['cookie'] = [['key' => 'referer', 'value' => '', 'path' => '/', 'expires' => 1]];
                    $this->useSso($result);
                }
                if (!empty($config['adapter']['mq'])) {
                    //mp
                    $this->getRabbitmqConnection();
                    $this->createRabbitmqChannel();
                    $this->declareRabbitmqQueue('customerlogin');
                    $this->declareRabbitmqExchange('customerlogin');
                    $data['language_id'] = $language_id;
                    $msgBody = ['eventName' => 'customer.register.after.mq', 'data' => $data];
                    $this->sendPublishMqMessage(json_encode($msgBody));
                }

                if (!empty($data['subscribe'])) {
                    //$this->getContainer()->get('eventDispatcher')->trigger('subscribe', ['data' => $data]);
                    if (!empty($config['adapter']['mq'])) {
                        //mp
                        $msgBody = ['eventName' => 'subscribe.mp', 'data' => $data];
                        $this->sendPublishMqMessage(json_encode($msgBody));
                    }
                    //$this->getContainer()->get('eventDispatcher')->trigger('subscribe', ['data' => $data]);
                }
                $this->sendMail($status ? 'email/customer/confirm_template' : 'email/customer/welcome_template', [$data['email'], $data['username']], ['username' => $data['username'], 'confirm' => $this->getBaseUrl('customer/account/confirm/?token=' . $token)]);
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $url ?? '/customer/account/create/', 'customer');
    }

    public function loginPostAction()
    {
        $url = 'customer/account/';
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $config = $this->getContainer()->get('config');
            $segment = new Segment('customer');
            $result = $this->validateForm($data, ['username', 'password'], (in_array('login', $config['customer/captcha/form']) && ($config['customer/captcha/mode'] == 0 || $config['customer/captcha/attempt'] <= $segment->get('fail2login'))) ? 'customer' : false);
            $key = $segment->get('reset_password_' . base64_encode($data['username']), ['key' => false, 'time' => 0]);
            if ($result['error'] == 0) {
                $customer = new Model();
                if ($customer->login($data['username'], $data['password'])) {
                    $result['success_url'] = !empty($data['success_url']) ? urldecode($data['success_url']) : '';
                    if (!empty($data['persistent'])) {
                        $persistent = new Persistent();
                        $key = md5(random_bytes(32) . $data['username']);
                        $persistent->setData([
                            'customer_id' => $customer->getId(),
                            'key' => $key
                        ])->save();
                        $result['cookie'] = [['key' => 'persistent', 'value' => $key, 'path' => '/', 'expires' => time() + 604800]];
                    }
                    $result['data'] = ['id' => $customer['id'], 'username' => $data['username'], 'email' => $customer['email']];
                    $result['message'][] = ['message' => $this->translate('Welcome %s.', [$customer['username']], 'customer'), 'level' => 'success'];

                    $this->getContainer()->get('eventDispatcher')->trigger('customer.login.after', ['model' => $customer]);
                    $language_id = Bootstrap::getLanguage()->getId();
                    $customer->setData('language_id', $language_id);
                    if (!empty($config['adapter']['mq'])) {
                        //mp
                        $this->getRabbitmqConnection();
                        $this->createRabbitmqChannel();
                        $this->declareRabbitmqQueue('customerlogin');
                        $this->declareRabbitmqExchange('customerlogin');
                        $msgBody = ['eventName' => 'customer.login.after.mq', 'data' => $customer->toArray()];
                        $this->sendPublishMqMessage(json_encode($msgBody));
                    }
                } elseif ($data['password'] === $key['key'] && $key['time'] >= strtotime('-1hour')) {
                    $segment->set('hasLoggedIn', true)
                            ->set('customer', (clone $customer)->toArray());
                    $this->getContainer()->get('eventDispatcher')->trigger('customer.login.after', ['model' => $customer]);
                    $language_id = Bootstrap::getLanguage()->getId();
                    $customer->setData('language_id', $language_id);
                    if (!empty($config['adapter']['mq'])) {
                        //mp
                        $this->getRabbitmqConnection();
                        $this->createRabbitmqChannel();
                        $this->declareRabbitmqQueue('customerlogin');
                        $this->declareRabbitmqExchange('customerlogin');
                        $msgBody = ['eventName' => 'customer.login.after.mq', 'data' => $customer->toArray()];
                        $this->sendPublishMqMessage(json_encode($msgBody));
                    }
                    $result['success_url'] = $this->getBaseUrl('customer/account/edit/');
                    $result['message'][] = ['message' => $this->translate('You are logged in with a temporary password. Please reset your password immdiately.'), 'level' => 'warning'];
                } elseif (!$customer->getId() || $customer['status']) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid username or password.'), 'level' => 'danger'];
                } else {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('This account is not confirmed.') . '<a href="' . $this->getBaseUrl('customer/account/resendconfirmemail/?id=' . $customer['id']) . '">(' . $this->translate('Resend') . ')</a>', 'level' => 'danger'];
                }
            }
            if ($result['error']) {
                $segment->set('fail2login', (int) $segment->get('fail2login') + 1);
            } else {
                $this->useSso($result);
                $segment->set('fail2login', 0);
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $url ?? 'customer/account/login/', 'customer');
    }

    public function forgotPwdPostAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['username'], in_array('forgotpwd', $this->getContainer()->get('config')['customer/captcha/form']) ? 'customer' : false);
            if ($result['error'] === 0) {
                $segment = new Segment('customer');
                $customer = new Model();
                $flag = false;
                foreach ($customer::$attrForLogin as $attr) {
                    $customer->load($data['username'], $attr);
                    if ($customer->getId()) {
                        $flag = true;
                        break;
                    }
                }
                if (!$flag) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid username.'), 'level' => 'danger'];
                    return $this->response($result, 'customer/account/login/', 'customer');
                }
                $key = $segment->get('reset_password_' . base64_encode($data['username']));
                if (empty($key) || $key['time'] < strtotime('-1hour')) {
                    $password = Rand::getString(8, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
                    $segment->set('reset_password_' . base64_encode($data['username']), [
                        'key' => $password,
                        'time' => time()
                    ]);
                } else {
                    $password = $key['key'];
                }
                try {
                    $this->sendMail('email/customer/forgot_template', [$customer->offsetGet('email'), $customer->offsetGet('username')], ['username' => $data['username'], 'password' => $password]);
                    $result['message'][] = ['message' => $this->translate('You will receive an email with a temporary password.'), 'level' => 'success'];
                } catch (EmailException $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while email transporting. Please try again later.'), 'level' => 'danger'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please try again later.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'customer/account/login/', 'customer');
    }

    public function logoutAction()
    {
        $segment = new Segment('customer');
        $customerId = $segment->get('customer')['id'];
        $segment->offsetUnset('customer');
        $segment->set('hasLoggedIn', false)
                ->set('hasLoggedOut', true);
        $result = ['error' => 0, 'message' => [[
            'message' => $this->translate('You have logged out successfully.'),
            'level' => 'success'
        ]]];
        $this->getContainer()->get('eventDispatcher')->trigger('customer.logout.after');
        if ($url = $this->getRequest()->getQuery('success_url')) {
            $result['success_url'] = urldecode($url);
        }
        return $this->response($result, 'customer/account/login/', 'customer', $customerId);
    }

    public function confirmAction()
    {
        if ($token = $this->getRequest()->getQuery('token')) {
            try {
                $customer = new Model();
                $customer->load(trim($token), 'confirm_token');
                if ($customer->getId() && $customer['status'] == 0) {
                    $languageId = Bootstrap::getLanguage()->getId();
                    $config = $this->getContainer()->get('config');
                    if (strtotime($customer['confirm_token_created_at']) < time() + 86400) {
                        $customer->setData([
                            'status' => 1,
                            'confirm_token' => null,
                            'confirm_token_created_at' => null
                        ])->save();
                        $result = ['error' => 0, 'message' => [[
                            'message' => $this->translate('Your account has been confirmed successfully.'),
                            'level' => 'success'
                        ]]];
                        $this->sendMail('email/customer/welcome_template', [$customer['email'], $customer['username']], ['username' => $customer['username'], 'confirm' => $this->getBaseUrl('customer/account/confirm/?token=' . $token)]);
                    } else {
                        $token = Rand::getString(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
                        $this->sendMail('email/customer/confirm_template', [$customer['email'], $customer['username']], ['username' => $customer['username'], 'confirm' => $this->getBaseUrl('customer/account/confirm/?token=' . $token)]);
                        $customer->setData([
                            'confirm_token' => $token,
                            'confirm_token_created_at' => date('Y-m-d H:i:s')
                        ])->save();
                        $result = ['error' => 0, 'message' => [['message' => $this->translate('The confirming link is expired.'), 'level' => 'danger']]];
                    }
                }
            } catch (Swift_TransportException $e) {
                $this->getContainer()->get('log')->logException($e);
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'customer/account/login/', 'customer');
    }

    public function indexAction()
    {
        return $this->getLayout('customer_account_dashboard');
    }

    public function editAction()
    {
        return $this->getLayout('customer_account_edit');
    }

    public function saveAction()
    {
        $result = ['error' => 0, 'message' => []];
        $config = $this->getContainer()->get('config');
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            unset($data['referer']);
            $segment = new Segment('customer');
            $customerArray = $segment->get('customer');
            $customer = new Model();
            $customer->load($customerArray['id']);
            $attributes = new Attribute();
            $attributes->withSet()->where([
                'is_unique' => 1,
                'attribute_set_id' => $data['attribute_set_id'] ?? $customer['attribute_set_id']
            ])->columns(['code'])
                    ->join('eav_entity_type', 'eav_attribute.type_id=eav_entity_type.id', [], 'right')
                    ->where(['eav_entity_type.code' => Model::ENTITY_TYPE])
            ->where->notEqualTo('input', 'password');
            $unique = [];
            $attributes->walk(function ($attribute) use (&$unique) {
                $unique[] = $attribute['code'];
            });
            $result = $this->validateForm($data, ['crpassword']);
            foreach ($customer::$attrForLogin as $attr) {
                $key = $segment->get('reset_password_' . base64_encode($customer[$attr]), ['key' => false, 'time' => 0]);
                if ($key['key']) {
                    break;
                }
            }
            if ($result['error'] === 0 && !$customer->valid($customer['username'], $data['crpassword']) && ($data['crpassword'] !== $key['key'] || $key['time'] < strtotime('-1hour'))) {
                $result['message'][] = ['message' => $this->translate('The current password is incorrect.'), 'level' => 'danger'];
                $result['error'] = 1;
            }
            if ($unique) {
                $collection = new Collection();
                $collection->columns($unique);
                $where = new Where();
                $flag = false;
                foreach ($unique as $code) {
                    if (isset($data[$code])) {
                        $predicate = new Where();
                        $predicate->equalTo($code, $data[$code]);
                        $where->orPredicate($predicate);
                        $flag = true;
                    }
                }
                $collection->getSelect()->where->notEqualTo('id', $customer['id'])->andPredicate($where);
                if ($flag && count($collection)) {
                    foreach ($collection as $item) {
                        foreach ($unique as $code) {
                            if (isset($item[$code]) && $item[$code]) {
                                $result['error'] = 1;
                                $result['message'][] = ['message' => $this->translate('The %s field has been used.', [$code]), 'level' => 'danger'];
                            }
                        }
                        break;
                    }
                }
            }
            if (!empty($data['edit_password'])) {
                if (empty($data['cpassword']) || empty($data['password']) || $data['cpassword'] !== $data['password']) {
                    $result['message'][] = ['message' => $this->translate('The confirm password is not equal to the password.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
                $data['modified_password'] = 1;
            } else {
                unset($data['cpassword'], $data['password']);
            }
            if ($result['error'] === 0) {
                try {
                    $files = $this->getRequest()->getUploadedFile();
                    foreach ($files as $key => $file) {
                        if ($file->getError() == 0) {
                            if (!is_dir(BP . 'pub/upload/customer/' . $key)) {
                                mkdir(BP . 'pub/upload/customer/' . $key, 0777, true);
                            }
                            $name = $customer['id'] . substr($file->getClientFilename(), strpos($file->getClientFilename(), '.'));
                            $path = BP . 'pub/upload/customer/' . $key . '/' . $name;
                            if (file_exists($path)) {
                                unlink($path);
                            }
                            $file->moveTo($path);
                            $data[$key] = $name;
                            if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
                                $aliyunConfig = resourceFactory::getAliYunOSSConfig();
                                $aliyunConfig['localfilepath'] = $path;
                                $aliyunConfig['ossobject'] = 'pub/upload/customer/' . $key . '/' . $name;
                                resourceFactory::aliYunOSSMoveFile($aliyunConfig);
                            }
                        } else {
                            unset($data[$key]);
                        }
                    }
                    $model = new Model();
                    $model->load($customer['id']);
                    $model->setData($data);
                    $this->getContainer()->get('eventDispatcher')->trigger('frontend.customer.save.before', ['model' => $model, 'data' => $data]);
                    $model->save();
                    $this->getContainer()->get('eventDispatcher')->trigger('frontend.customer.save.after', ['model' => $model, 'data' => $data]);
                    $segment->set('customer', (clone $model)->toArray());
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'customer/account/edit/', 'customer');
    }

    public function addressAction()
    {
        return $this->getLayout('customer_account_address');
    }

    public function deleteAddressAction()
    {
        if ($this->getRequest()->isDelete()) {
            $address = new Address();
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $address->setId($data['id'])->remove();
                    $result['removeLine'] = 1;
                    $result['message'][] = ['message' => $this->translate('The address has been deleted successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while deleting. Please contact us or try again later.'), 'level' => 'success'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'customer/account/address/', 'customer');
    }

    public function saveAddressAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $attribute = new Attribute();
            $attribute->withSet()
                    ->columns(['code'])
                    ->join('eav_entity_type', 'eav_entity_type.id=eav_attribute.type_id', [])
                    ->where(['eav_entity_type.code' => Address::ENTITY_TYPE, 'is_required' => 1]);
            $required = [];
            $setId = $attribute[0]['attribute_set_id'];
            $attribute->walk(function ($item) use (&$required) {
                $required[] = $item['code'];
            });
            foreach ($data as &$item) {
                $item = trim($item);
            }
            $result = $this->validateForm($data, $required);
            if ($result['error'] === 0) {
                $address = new Address();
                try {
                    $segment = new Segment('customer');
                    if (isset($data['id'])) {
                        $address->load($data['id']);
                    }
                    if (!empty($address->offsetGet('customer_id')) && $address->offsetGet('customer_id') != $segment->get('customer')['id']) {
                        throw new Exception('');
                    }
                    $address->setData($data + [
                        'attribute_set_id' => $setId,
                        'store_id' => Bootstrap::getStore()->getId(),
                        'customer_id' => $segment->get('hasLoggedIn') ? $segment->get('customer')['id'] : null
                    ])->save();
                    $result['message'][] = ['message' => $this->translate('The address has been saved successfully.'), 'level' => 'success'];
                    $result['data'] = ['id' => $address->getId(), 'content' => $address->display()];
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result, 'customer/account/address', 'customer');
    }

    public function defaultAddressAction()
    {
        $id = $this->getRequest()->getQuery('id');
        if ($id) {
            $address = new Address();
            $address->load($id)->setData('is_default', 1)->save();
        }
        return $this->response(['error' => 0, 'message' => []], 'customer/account/address/');
    }

    public function logviewAction()
    {
        $root = $this->getLayout('customer_account_logview');
        return $root;
    }

    public function updateAvatarAction()
    {
        $result = ['error' => 0, 'message' => []];
        $config = $this->getContainer()->get('config');
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $segment = new Segment('customer');
            $customer = $segment->get('customer');
            if (isset($data['avatar']) && $data['avatar'] != '') {
                try {
                    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $data['avatar'], $fileResult)) {
                        $type = $fileResult[2];
                        if (!is_dir(BP . 'pub/upload/customer/avatar')) {
                            mkdir(BP . 'pub/upload/customer/avatar', 0777, true);
                        }
                        $name = 'avatar-' . $customer['id'] . '-' . date('YmdHis') . '.' . $type;
                        $path = BP . 'pub/upload/customer/avatar/' . $name;
                        if (file_put_contents($path, base64_decode(str_replace($fileResult[1], '', $data['avatar'])))) {
                            $dataAvatar = ['avatar' => $name];
                            resourceFactory::uploadFileToClound($path, 'pub/upload/customer/avatar/' . $name, $name);
                            $model = new Model();
                            $model->load($customer['id']);
                            $model->setData($dataAvatar);
                            $this->getContainer()->get('eventDispatcher')->trigger('frontend.customer.save.before', ['model' => $model, 'data' => $data]);
                            $model->save();
                            $this->getContainer()->get('eventDispatcher')->trigger('frontend.customer.save.after', ['model' => $model, 'data' => $data]);
                            $segment->set('customer', (clone $model)->toArray());
                            $result['message'][] = ['message' => $this->translate('Avatar has been updated successfully.'), 'level' => 'success'];
                        } else {
                            $result['error'] = 1;
                            $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                        }
                    } else {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected while saving.'), 'level' => 'danger'];
                }
            } else {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('Avatar can not be null.'), 'level' => 'danger'];
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'customer/account/edit/', 'customer');
    }

    public function refereesAction()
    {
        $root = $this->getLayout('customer_account_referees');
        return $root;
    }

    public function referrerAction()
    {
        $root = $this->getLayout('customer_account_referrer');
        return $root;
    }

    public function resendconfirmemailAction()
    {
        $result = ['error' => 0, 'message' => []];
        $id = $this->getRequest()->getQuery('id');
        if (!empty($id)) {
            $customer = new Model();
            $customer->load($id);
            if (!empty($customer['email'])) {
                $token = Rand::getString(32, 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789');
                $this->sendMail('email/customer/confirm_template', [$customer['email'], $customer['username']], ['username' => $customer['username'], 'confirm' => $this->getBaseUrl('customer/account/confirm/?token=' . $token)]);
                $customer->setData([
                    'confirm_token' => $token,
                    'confirm_token_created_at' => date('Y-m-d H:i:s')
                ])->save();
                $result['message'][] = ['message' => $this->translate('You will receive an email with a confirming link.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, 'customer/account/login/', 'customer');
    }
}
