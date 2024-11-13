<?php

namespace Redseanet\Balance\Controller;

use Exception;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Sales\Model\Cart;

class IndexController extends ActionController
{
    public function loadAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->getLayout('checkout_order_balance');
        }
        return $this->notFoundAction();
    }

    public function applyAction()
    {
        try {
            $cart = Cart::instance();
            $this->getContainer()->get('eventDispatcher')->trigger('balances.apply', ['model' => $cart]);
            $cart->collateTotals();
            $result = ['error' => 0, 'message' => []];
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            $result = ['error' => 1, 'message' => [['title' => 'An error detected while saving. Please contact us or try again later.', 'content' => 'An error detected while saving. Please contact us or try again later.', 'level' => 'danger']]];
        }
        return $result;
    }

    public function cancelAction()
    {
        try {
            $cart = Cart::instance();
            $this->getContainer()->get('eventDispatcher')->trigger('balances.cancel', ['model' => $cart]);
            $cart->collateTotals();
            $result = ['error' => 0, 'message' => []];
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            $result = ['error' => 1, 'message' => [['title' => 'An error detected while saving. Please contact us or try again later.', 'content' => 'An error detected while saving. Please contact us or try again later.', 'level' => 'danger']]];
        }
        return $result;
    }
}
