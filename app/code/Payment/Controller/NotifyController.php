<?php

namespace Redseanet\Payment\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Log\Model\Payment;
use SimpleXMLElement;

class NotifyController extends ActionController
{
    protected $tradeIndex = [
        'out_trade_no'
    ];

    protected function xmlToArray(SimpleXMLElement $xml)
    {
        $result = (array) $xml;
        foreach ($result as &$child) {
            if ($child instanceof SimpleXMLElement) {
                $child = $this->xmlToArray($child);
            } elseif (is_array($child) && count($child) === 1) {
                $child = $child[0];
            }
        }
        return $result;
    }

    public function indexAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            if (is_object($data)) {
                $data = $this->xmlToArray($data);
            }
            $tradeId = false;
            foreach ($this->tradeIndex as $index) {
                if (!empty($data[$index])) {
                    $tradeId = $data[$index];
                    break;
                }
            }
            if ($tradeId) {
                $log = new Payment();
                $log->load($tradeId, 'trade_id');
                $method = new $log['method']();
                $response = $method->asyncNotice($data);
                if ($response !== false) {
                    return $response;
                }
            }
        }
        return $this->notFoundAction();
    }
}
