<?php

namespace Redseanet\Payment\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Redseanet\Payment\Model\WeChatPay;

class WechatController extends ActionController
{
    public function indexAction()
    {
        return $this->getLayout('payment_wechat');
    }

    public function checkAction()
    {
        $segment = new Segment('payment');
        $model = new WeChatPay();
        return $this->getRequest()->isXmlHttpRequest() ?
                ($model->check($segment->get('wechatpay')[2] ?? null) ? 'true' : 'false') :
                $this->redirect($model->check($segment->get('wechatpay')[2] ?? null) ? 'checkout/success/' : 'payment/wechat/');
    }
}
