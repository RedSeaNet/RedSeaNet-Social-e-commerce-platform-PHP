<?php

namespace Redseanet\Admin\Controller;

use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Admin\Model\User;

class ConfigController extends AuthActionController
{
    protected $key = null;
    protected $config = null;

    public function __call($name, $arguments)
    {
        $config = $this->getContainer()->get('config')['system'];
        $key = strtolower(substr($name, 0, -6));
        if (isset($config[$key])) {
            $this->key = $key;
            $this->config = $config[$key];
            return $this->indexAction();
        } else {
            return $this->notFoundAction();
        }
    }

    public function indexAction()
    {
        if (is_null($this->key)) {
            return $this->notFoundAction();
        }
        $root = $this->getLayout('admin_config');
        $content = $root->getChild('content');
        $content->getChild('edit')->setKey($this->key)->setElements($this->config['children']);
        $root->getChild('head')->setTitle($this->config['label'] . ' / ' . 'System Configuration');
        $content->getChild('breadcrumb')->addCrumb(['link' => ':ADMIN/config/' . $this->key . '/', 'label' => $this->translate('System Configuration') . ' > ' . $this->translate($this->config['label'])]);
        return $root;
    }

    public function saveAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data);
            if ($result['error'] === 0) {
                $key = $data['key'];
                $scope = substr($data['scope'], 0, 1);
                $scope_id = substr($data['scope'], 1);
                $where = $scope === 's' ? ['store_id' => $scope_id] :
                        ['merchant_id' => $scope_id];
                $segment = new Segment('admin');
                $userArray = $segment->get('user');
                $user = new User();
                $user->load($userArray['id']);
                $store = $user->getStore();
                if ($store && ($scope !== 's' || $scope_id != $store->getId())) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Failed to save configuration.'), 'level' => 'danger'];
                } else {
                    $files = $this->getRequest()->getUploadedFile();
                    if (count($files)) {
                        if (!is_dir(BP . 'pub/upload/config/')) {
                            mkdir(BP . 'pub/upload/config/', 0777, true);
                        }
                        foreach ($files as $path => $file) {
                            if ($file->getError() == 0) {
                                $name = $key . '_' . str_replace('/', '_', $path) . substr($file->getClientFilename(), strrpos($file->getClientFilename(), '.'));
                                if (file_exists(BP . 'pub/upload/config/' . $name)) {
                                    unlink(BP . 'pub/upload/config/' . $name);
                                }
                                $file->moveTo(BP . 'pub/upload/config/' . $name);
                                $data[$path] = 'pub/upload/config/' . $name;
                            }
                        }
                    }
                    try {
                        $this->beginTransaction();
                        $this->getTableGateway('core_config');
                        foreach ($data as $path => $value) {
                            if (!in_array($path, ['key', 'csrf', 'scope', '_changed_fields'])) {
                                $this->upsert(['value' => is_array($value) ? implode(',', $value) : $value], $where + ['path' => $key . '/' . $path]);
                                $this->getContainer()->get('eventDispatcher')->trigger('system.config.' . $key . '/' . $path . '.save.after', ['value' => $value, 'scope' => $where]);
                            }
                        }
                        $this->commit();
                        $this->getContainer()->get('cache')->delete('', 'SYSTEM_CONFIG');
                        $result['message'][] = ['message' => $this->translate('Configuration saved successfully.'), 'level' => 'success'];
                    } catch (Exception $e) {
                        $this->getContainer()->get('log')->logException($e);
                        $this->rollback();
                        $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                        $result['error'] = 1;
                    }
                }
            }
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }
}
