<?php

namespace Redseanet\Retailer\Controller;

use Exception;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Session\Segment;
use Redseanet\Resource\Model\Category;
use Redseanet\Resource\Model\Resource as Model;
use Redseanet\Resource\Model\Collection\Resource as Collection;
use Redseanet\Resource\Lib\Factory as resourceFactory;

class ResourceController extends AuthActionController
{
    use \Redseanet\Resource\Traits\Resize;

    public function indexAction()
    {
        return $this->getLayout($this->getRequest()->isXmlHttpRequest() ? 'retailer_resource_list' : 'retailer_resource');
    }

    public function navAction()
    {
        return $this->getLayout('retailer_resource_nav');
    }

    public function uploadAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $files = $this->getRequest()->getUploadedFile()['files'];
            $retailer = $this->getRetailer();
            $result = $this->validateForm($data);
            if ($result['error'] === 0) {
                try {
                    foreach ($files as $file) {
                        $name = $file->getClientFilename();
                        $model = new Model();
                        $range = !empty($this->getRequest()->getHeader('CONTENT_RANGE')['CONTENT_RANGE']) ? $this->getRequest()->getHeader('CONTENT_RANGE')['CONTENT_RANGE'] : '';
                        if (!empty($range)) {
                            if ($model->chunk($file, $range)) {
                                $model->setData([
                                    'store_id' => $retailer->getId() ? $retailer->offsetGet('store_id') : (empty($data['store_id']) ? null : $data['store_id']),
                                    'uploaded_name' => $name,
                                    'file_type' => $file->getClientMediaType(),
                                    'category_id' => empty($data['category_id']) ? null : $data['category_id']
                                ])->save();
                            }
                        } else {
                            $model->moveFile($file)
                                    ->setData([
                                        'store_id' => $retailer->getId() ? $retailer->offsetGet('store_id') : (empty($data['store_id']) ? null : $data['store_id']),
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
                $storeId = $this->getRetailer()->offsetGet('store_id');
                try {
                    $path = BP . Model::$options['path'];
                    foreach ((array) $data['r'] as $id) {
                        $model = new Model();
                        $model->load($id);
                        if ($model->getId() && $model->offsetGet('store_id') == $storeId) {
                            $type = $model->offsetGet('file_type');
                            $collection = new Collection();
                            $collection->where(['md5' => $model['md5']])
                            ->where->notEqualTo('id', $id);
                            if (count($collection) === 0 && file_exists($filename = $path . substr($type, 0, strpos($type, '/') + 1) . $model->offsetGet('real_name'))) {
                                unlink($filename);
                            }
                            $model->remove();
                        }
                    }
                    foreach ((array) $data['f'] as $id) {
                        $model = new Category();
                        $model->load($id);
                        if ($model->getId() && $model->offsetGet('store_id') == $storeId) {
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
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/resource/');
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
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/resource/');
    }

    public function renameAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $data['name'] = htmlspecialchars($data['name']);
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
                            $retailer = $this->getRetailer();
                            $model->setData([
                                'store_id' => $retailer ? $retailer->offsetGet('store_id') : (empty($data['store_id']) ? null : $data['store_id']),
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
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/resource/');
    }
}
