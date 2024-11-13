<?php

namespace Redseanet\Admin\Controller\Catalog;

use Exception;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Collection\Language;
use Redseanet\Lib\Session\Segment;
use Laminas\Math\Rand;
use Laminas\Db\TableGateway\TableGateway;
use Redseanet\Catalog\Model\Product as Model;

class InventoryController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Lib\Traits\DB;

    public function indexAction()
    {
        $root = $this->getLayout('admin_catalog_inventory_list');
        return $root;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isDelete()) {
            $redirect = ':ADMIN/catalog_inventory/';
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);

            if ($result['error'] === 0) {
                try {
                    $count = 0;
                    $tableGateways = new TableGateway('warehouse_inventory', $this->getContainer()->get('dbAdapter'));
                    foreach ((array) $data['id'] as $id) {
                        $idArray = json_decode(base64_decode($id), true);
                        $tableGateways->delete(['warehouse_id' => $idArray['warehouse_id'], 'product_id' => $idArray['product_id'], 'sku' => $idArray['sku']]);
                        $count++;
                    }
                    $this->flushList('warehouse_inventory');
                    $result['message'][] = ['message' => $this->translate('%d item(s) have been deleted successfully.', [$count]), 'level' => 'success'];
                    $result['removeLine'] = (array) $data['id'];
                    $result['reload'] = 1;
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while deleting. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], is_null($redirect) ? $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'] : $redirect);
    }

    public function editAction()
    {
        $root = $this->getLayout('admin_catalog_inventory_edit');
        $root->getChild('edit', true)->setVariable('save_url', 'catalog_inventory/save/');
        return $root;
    }

    public function saveAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();

            $result = $this->validateForm($data, $required);
            if ($result['error'] === 0) {
                $model = new Model($this->getRequest()->getQuery('language_id', Bootstrap::getLanguage()->getId()), $data);
                try {
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result, ':ADMIN/catalog_product/?page=' . (!empty($data['page']) ? $data['page'] : 1));
    }

    public function quickSaveAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $required = ['id', 'column', 'value'];
            $result = $this->validateForm($data, $required);
            if ($result['error'] === 0) {
                $idArray = json_decode(base64_decode($data['id']), true);
                try {
                    $tableGateways = new TableGateway('warehouse_inventory', $this->getContainer()->get('dbAdapter'));
                    $tableGateways->update([$data['column'] => $data['value']], ['warehouse_id' => $idArray['warehouse_id'], 'product_id' => $idArray['product_id'], 'sku' => $idArray['sku']]);
                    $this->flushList('warehouse');
                    $this->flushList('warehouse_inventory');
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result, ':ADMIN/catalog_inventory/?page=' . (!empty($data['page']) ? $data['page'] : 1));
    }
}
