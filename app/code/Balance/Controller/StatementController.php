<?php

namespace Redseanet\Balance\Controller;

use Exception;
use Redseanet\Balance\Source\DrawType;
use Redseanet\Catalog\Exception\OutOfStock;
use Redseanet\Catalog\Model\Product;
use Redseanet\Customer\Controller\AuthActionController;
use Redseanet\Customer\Model\Balance;
use Redseanet\Customer\Model\Balance\Account;
use Redseanet\Customer\Model\Balance\Draw;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Cart;

class StatementController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('balance_statement');
    }

    public function rechargeAction()
    {
        return $this->getLayout('balance_statement_recharge');
    }

    public function cancelAction()
    {
        if ($this->getRequest()->isDelete()) {
            $address = new Balance();
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $address->setId($data['id'])->remove();
                    $result['removeLine'] = 1;
                    $result['message'][] = ['message' => $this->translate('Cancel recharge successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'success'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'balance/statement/', 'customer');
    }

    public function addAction()
    {
        $data = $this->getRequest()->isGet() ? $this->getRequest()->getQuery() : $this->getRequest()->getPost();
        $result = $this->validateForm($data, ['product_id', 'qty', 'warehouse_id']);
        if ($result['error'] === 0) {
            try {
                $product = new Product();
                $options = $product->load($data['product_id']);
                if ($result['error'] === 1) {
                    return $this->response($result, $product->getUrl(), 'checkout');
                }
                $cart = Cart::instance();
                $items = $cart->getItems(true);
                foreach ($items as $item) {
                    if ($item['is_virtual'] != 1) {
                        $item->setData('status', 0)->save();
                    }
                }
                $cart->addItem($data['product_id'], $data['qty'], $data['warehouse_id']);
                $cart->collateTotals();
                $result['reload'] = 1;
            } catch (OutOfStock $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('The requested quantity for "%s" is not available.', [(new Product())->load($data['product_id'])['name']]),  'level' => 'danger'];
            } catch (Exception $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('Prohibit the purchase of goods sold.'), 'level' => 'danger'];
                $this->getContainer()->get('log')->logException($e);
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'checkout/order/', 'customer');
    }

    public function drawAction()
    {
        return $this->getLayout('balance_draw');
    }

    public function drawPostAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['amount', 'account_id']);
            if ($result['error'] === 0) {
                try {
                    $segment = new Segment('customer');
                    $data['customer_id'] = $segment->get('customer')['id'];
                    $draw = new Draw($data);
                    $draw->save();
                    $result['message'][] = ['message' => $this->translate('We have received your application. The audit will be arranged in 3 days. Thanks for your support.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                    $this->getContainer()->get('log')->logException($e);
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'balance/statement/', 'customer');
    }

    public function cancelDrawAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $draw = new Draw();
                    $draw->load($data['id']);
                    $segment = new Segment('customer');
                    if ($segment->get('customer')['id'] == $draw['customer_id']) {
                        $draw->setData('status', -1)->save();
                        $result['reload'] = 1;
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('Your application has been canceled.'), 'level' => 'success'];
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                    $this->getContainer()->get('log')->logException($e);
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'balance/statement/', 'customer');
    }

    public function saveAccountAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['type', 'detail']);
            if ($result['error'] === 0) {
                $type = (new DrawType())->getSourceArray()[$data['type']] ?? null;
                if ($type) {
                    try {
                        $segment = new Segment('customer');
                        $data['customer_id'] = $segment->get('customer')['id'];
                        $data['detail'] = json_encode($data['detail']);
                        $account = new Account($data);
                        $account->save();
                        if (empty($data['id'])) {
                            $result['reload'] = 1;
                        } else {
                            $result['data'] = ['type_name' => $this->translate($type)] + $account->toArray();
                            unset($result['data']['csrf']);
                        }
                    } catch (Exception $e) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                        $this->getContainer()->get('log')->logException($e);
                    }
                } else {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('Invalid account type.'), 'level' => 'danger'];
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'balance/statement/draw/', 'customer');
    }

    public function deleteAccountAction()
    {
        if ($this->getRequest()->isDelete()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['id']);
            if ($result['error'] === 0) {
                try {
                    $account = new Account();
                    $account->load($data['id']);
                    $segment = new Segment('customer');
                    if ($segment->get('customer')['id'] == $account['customer_id']) {
                        $account->remove();
                        $result['removeLine'] = $data['id'];
                    }
                } catch (Exception $e) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
                    $this->getContainer()->get('log')->logException($e);
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'balance/statement/draw/', 'customer');
    }
}
