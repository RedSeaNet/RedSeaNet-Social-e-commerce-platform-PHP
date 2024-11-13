<?php

namespace Redseanet\Retailer\Controller;

use Exception;
use Redseanet\Catalog\Model\Product\Review;
use Redseanet\Lib\Session\Segment;

class TransactionController extends AuthActionController
{
    public function indexAction()
    {
        $segment = new Segment('customer');

        if ($customerId = $segment->get('customer')['id']) {
            $customer = new Cmodel();
            $customer->load($customerId);
            $root = $this->getLayout('retailer_store_settings');
            $root->getChild('main', true)->setVariable('customer', $customer);
            return $root;
        }
        return $root;
    }

    public function productAction()
    {
        $root = $this->getLayout('retailer_product');
        $order = [
            'type' => 'sold'
        ];
        $root->getChild('main', true)->setVariable('subtitle', 'Sold Product')->setVariable('filter', $this->getRequest()->getQuery());
        return $root;
    }

    public function orderviewAction()
    {
        $order_id = $this->getRequest()->getQuery('id');
        if (empty($order_id) || !is_numeric($order_id)) {
            return $this->redirect('retailer/transaction/products/');
        }
        $root = $this->getLayout('retailer_order_view');
        return $root;
    }

    public function reviewAction()
    {
        return $this->getLayout('retailer_review');
    }

    public function replyAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id', 'reply']);
            if ($result['error'] === 0) {
                try {
                    $review = new Review();
                    $review->setData([
                        'id' => $data['id'],
                        'reply' => htmlspecialchars($data['reply'])
                    ])->save();
                    $result['message'][] = ['message' => $this->translate('Replied successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'retailer');
    }
}
