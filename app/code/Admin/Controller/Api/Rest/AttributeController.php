<?php

namespace Redseanet\Admin\Controller\Api\Rest;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;

class AttributeController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    public function indexAction()
    {
        return $this->getLayout('admin_rest_attribute_list');
    }

    public function editAction()
    {
        if ($this->getRequest()->getQuery('id') !== '') {
            return $this->getLayout('admin_rest_attribute_edit');
        } else {
            return $this->redirect(':ADMIN/api_rest_attribute/');
        }
    }

    public function saveAction()
    {
        if ($this->getRequest()->getPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['role_id']);
            if ($result['error'] === 0) {
                $this->getTableGateway('api_rest_attribute');
                try {
                    $this->beginTransaction();
                    $this->delete(['role_id' => $data['role_id']]);
                    foreach ((array) $data['attribute'] as $resource => $privileges) {
                        if (!empty($privileges['readable'])) {
                            $this->upsert(['attributes' => implode(',', $privileges['readable'])], [
                                'role_id' => $data['role_id'], 'operation' => 1, 'resource' => $resource
                            ]);
                        }
                        if (!empty($privileges['writeable'])) {
                            $this->upsert(['attributes' => implode(',', $privileges['writeable'])], [
                                'role_id' => $data['role_id'], 'operation' => 0, 'resource' => $resource
                            ]);
                        }
                    }
                    $this->commit();
                    $this->flushList('api_rest_attribute');
                    $result['message'][] = ['message' => $this->translate('REST attribute rules have been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->rollback();
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], ':ADMIN/api_rest_attribute/');
    }
}
