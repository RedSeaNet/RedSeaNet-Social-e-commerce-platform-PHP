<?php

namespace Redseanet\Retailer\Controller;

class ShippingController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('retailer_shipping');
    }

    public function saveAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data);
            if ($result['error'] === 0) {
                $where = ['store_id' => $this->getRetailer()['store_id']];
                try {
                    $this->beginTransaction();
                    $this->getTableGateway('core_config');
                    foreach ($data as $path => $value) {
                        if (!in_array($path, ['key', 'csrf', 'scope'])) {
                            $this->upsert(['value' => is_array($value) ? implode(',', $value) : $value], $where + ['path' => 'shipping/' . $path]);
                            $this->getContainer()->get('eventDispatcher')->trigger('system.config.' . $path . '.save.after', ['value' => $value, 'scope' => $where]);
                        }
                    }
                    $this->commit();
                    $this->getContainer()->get('cache')->delete('', 'SYSTEM_CONFIG');
                    $result['message'][] = ['message' => $this->translate('Configuration saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $this->rollback();
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please contact us or try again later.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'retailer');
    }
}
