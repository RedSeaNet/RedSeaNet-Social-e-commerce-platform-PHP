<?php

namespace Redseanet\Admin\Controller\Resource;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Resource\Model\Category;
use Redseanet\Resource\Model\Resource as Model;
use Redseanet\Resource\Model\Collection\Resource as Collection;
use Redseanet\Admin\Model\User;
use Redseanet\Resource\Lib\Factory as resourceFactory;

class ResourceController extends AuthActionController
{
    use \Redseanet\Resource\Traits\Resize;

    public function indexAction()
    {
        return $this->getLayout($this->getRequest()->isXmlHttpRequest() ? 'admin_resource_list' : 'admin_resource');
    }

    public function navAction()
    {
        return $this->getLayout('admin_resource_nav');
    }

    public function uploadAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $files = $this->getRequest()->getUploadedFile()['files'];
            $userArray = (new Segment('admin'))->get('user');
            $user = new User();
            $user->load($userArray['id']);
            $result = $this->validateForm($data);
            if ($result['error'] === 0) {
                try {
                    foreach ($files as $file) {
                        $name = $file->getClientFilename();
                        $model = new Model();
                        if (!empty($this->getRequest()->getHeader('CONTENT_RANGE')['CONTENT_RANGE']) && $range = $this->getRequest()->getHeader('CONTENT_RANGE')['CONTENT_RANGE']) {
                            if ($model->chunk($file, $range)) {
                                $model->setData([
                                    'store_id' => $user->offsetGet('store_id') ?: (empty($data['store_id']) ? null : $data['store_id']),
                                    'uploaded_name' => $name,
                                    'file_type' => $file->getClientMediaType(),
                                    'category_id' => empty($data['category_id']) ? null : $data['category_id']
                                ])->save();
                            }
                        } else {
                            $model->moveFile($file)
                                    ->setData([
                                        'store_id' => $user->offsetGet('store_id') ?: (empty($data['store_id']) ? null : $data['store_id']),
                                        'uploaded_name' => $name,
                                        'file_type' => $file->getClientMediaType(),
                                        'category_id' => empty($data['category_id']) ? null : $data['category_id']
                                    ])->save();
                            if (substr($file->getClientMediaType(), 0, 5) === 'image') {
                                $config = $this->getContainer()->get('config');
                                $fileName = BP . 'pub/resource/image/' . $model['real_name'];
                                foreach ($config['resource/resized'] as $sizeKey => $sizeValue) {
                                    $resized = BP . 'pub/resource/image/resized/' . $sizeKey . '/' . $model['real_name'];
                                    $path = dirname($resized);
                                    if (!is_dir($path)) {
                                        mkdir($path, 0777, true);
                                    }
                                    if ($config['resource/resized/' . $sizeKey]) {
                                        $image = $this->resize($fileName, (int) $config['resource/resized/' . $sizeKey]);
                                        $image->save($resized);
                                        if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
                                            $aliyunConfig = resourceFactory::getAliYunOSSConfig();
                                            $aliyunConfig['localfilepath'] = $resized;
                                            $aliyunConfig['ossobject'] = 'pub/resource/image/resized/' . $sizeKey . '/' . $model['real_name'];
                                            resourceFactory::aliYunOSSMoveFile($aliyunConfig);
                                        }
                                    }
                                }
                            }
                        }
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate($e->getMessage()), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER']);
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data);
            if (!isset($data['r'])) {
                $data['r'] = [];
            }
            if (!isset($data['f'])) {
                $data['f'] = [];
            }
            if ($result['error'] === 0) {
                $storeId = (new Segment('admin'))->get('user')['store_id'];
                try {
                    $path = BP . Model::$options['path'];
                    foreach ((array) $data['r'] as $id) {
                        $model = new Model();
                        $model->load($id);
                        $fileName = $model->offsetGet('real_name');
                        if ($model->getId() && (!$storeId || $model->offsetGet('store_id') == $storeId)) {
                            $type = $model->offsetGet('file_type');
                            if (file_exists($filename = $path . substr($type, 0, strpos($type, '/') + 1) . $fileName)) {
                                unlink($filename);
                            }
                            $model->remove();
                            if (substr($type, 0, 5) === 'image') {
                                $config = $this->getContainer()->get('config');
                                foreach ($config['resource/resized'] as $sizeKey => $sizeValue) {
                                    $resized = $path . substr($type, 0, strpos($type, '/') + 1) . 'resized/' . $sizeKey . '/' . $fileName;
                                    if (file_exists($resized)) {
                                        @unlink($resized);
                                    }
                                    if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
                                        $aliyunConfig = resourceFactory::getAliYunOSSConfig();
                                        $aliyunConfig['localfilepath'] = $resized;
                                        $ossobjectName = Model::$options['path'] . 'image/resized/' . $sizeKey . '/' . $fileName;
                                        resourceFactory::aliYunOSSObjectDelete($ossobjectName);
                                    }
                                }
                            }
                        }
                    }
                    foreach ((array) $data['f'] as $id) {
                        $model = new Category();
                        $model->load($id);
                        if (!$storeId || $model->offsetGet('store_id') == $storeId) {
                            $model->remove();
                        }
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while deleting.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], ':ADMIN/resource_resource/');
    }

    public function moveAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id', 'category_id', 'type']);
            if ($result['error'] === 0) {
                try {
                    if ($data['type'] === 'r') {
                        $model = new Model();
                        $model->setData([
                            'id' => $data['id'],
                            'category_id' => $data['category_id'] ?: null
                        ])->save();
                    } else {
                        $model = new Category();
                        $model->setData([
                            'id' => $data['id'],
                            'parent_id' => $data['category_id'] ?: null
                        ])->save();
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], ':ADMIN/resource_resource/');
    }

    public function renameAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id', 'name', 'type']);
            if ($result['error'] === 0) {
                try {
                    if ($data['type'] === 'r') {
                        $model = new Model();
                        $model->setData([
                            'id' => $data['id'],
                            'uploaded_name' => $data['name']
                        ])->save();
                    } else {
                        $model = new Category();
                        if ($data['id']) {
                            $model->load($data['id']);
                        } else {
                            $user = (new Segment('admin'))->get('user');
                            $model->setData([
                                'store_id' => $user['store_id'] ?: (empty($data['store_id']) ? null : $data['store_id']),
                                'parent_id' => ((int) $data['pid']) ?: null
                            ]);
                        }
                        $model->setData('name', [Bootstrap::getLanguage()->getId() => $data['name']])
                                ->save();
                        $result['data'] = ['id' => $model->getId()];
                    }
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], ':ADMIN/resource_resource/');
    }

    public function regenerateresizeAction()
    {
        $data = $this->getRequest()->getPost();
        $result = ['error' => 0, 'message' => []];
        if (!empty($data['key']) && !empty($data['value'])) {
            $config = $this->getContainer()->get('config');
            $configValue = $config['resource/resized/' . $data['key']];
            if (!empty($configValue)) {
                $collections = new Collection();
                foreach ($collections as $model) {
                    if (substr($model['file_type'], 0, 5) === 'image') {
                        $fileName = BP . 'pub/resource/image/' . $model['real_name'];
                        if (file_exists($fileName)) {
                            $resized = BP . 'pub/resource/image/resized/' . $data['key'] . '/' . $model['real_name'];
                            $path = dirname($resized);
                            if (!is_dir($path)) {
                                mkdir($path, 0777, true);
                            }
                            $image = $this->resize($fileName, (int) $data['value']);
                            $image->save($resized);
                            if (isset($config['resource/server/service']) && $config['resource/server/service'] == 'aliyunoss') {
                                $aliyunConfig = resourceFactory::getAliYunOSSConfig();
                                $aliyunConfig['localfilepath'] = $resized;
                                $aliyunConfig['ossobject'] = 'pub/resource/image/resized/' . $data['key'] . '/' . $model['real_name'];
                                resourceFactory::aliYunOSSMoveFile($aliyunConfig);
                            }
                        }
                    }
                }
                $result['error'] = 0;
                $result['message'][] = ['message' => $this->translate('regenerate successfully'), 'level' => 'success'];
            } else {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('params are error'), 'level' => 'danger'];
            }
        } else {
            $result['error'] = 1;
            $result['message'][] = ['message' => $this->translate('params are error'), 'level' => 'danger'];
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], ':ADMIN/config/resource/');
    }
}
