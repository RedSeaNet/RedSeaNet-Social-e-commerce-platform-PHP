<?php

namespace Redseanet\Payment\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Lib\Session\Segment;
use Exception;
use Redseanet\Log\Model\Collection\Payment as Collection;
use Redseanet\Log\Model\Payment as Model;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Collection\Order\Status;

class CoinPaymentsNetController extends ActionController
{
    public function indexAction()
    {
        return $this->notFoundAction();
    }

    public function callbackAction()
    {
        $data = $this->getRequest()->getPost();
        $config = $this->getContainer()->get('config');
        $this->getContainer()->get('log')->logException(new \Exception('CoinPaymentsNetcallbackAction:'));
        //$this->getContainer()->get('log')->logException($e);
        $this->getContainer()->get('log')->logException(new \Exception('post-data:' . json_encode($data)));

        $cp_merchant_id = $config['payment/coinpayments_net/merchant_id'];
        $cp_ipn_secret = $config['payment/coinpayments_net/ipn_secret'];

        if (!isset($_POST['ipn_mode']) || $_POST['ipn_mode'] != 'hmac') {
            $this->getContainer()->get('log')->logException(new \Exception('IPN Mode is not HMAC.'));
            $this->getContainer()->get('log')->logException(new \Exception('ipn_mode:' . $_POST['ipn_mode']));
        }

        if (!isset($_SERVER['HTTP_HMAC']) || empty($_SERVER['HTTP_HMAC'])) {
            $this->getContainer()->get('log')->logException(new \Exception('No HMAC signature sent.'));
            $this->getContainer()->get('log')->logException(new \Exception('HTTP_HMAC:' . $_SERVER['HTTP_HMAC']));
        }

        $request = file_get_contents('php://input');
        if ($request === false || empty($request)) {
            $this->getContainer()->get('log')->logException(new \Exception('Error reading POST data'));
            $this->getContainer()->get('log')->logException(new \Exception('request:' . $request));
        }
        $this->getContainer()->get('log')->logException(new \Exception('request:' . $request));
        if (!isset($data['merchant']) || $data['merchant'] != trim($cp_merchant_id)) {
            $this->getContainer()->get('log')->logException(new \Exception('No or incorrect Merchant ID passed'));
            $this->getContainer()->get('log')->logException(new \Exception('merchat id:' . $data['merchant']));
        }

        $hmac = hash_hmac('sha512', $request, trim($cp_ipn_secret));
        if (!hash_equals($hmac, $_SERVER['HTTP_HMAC'])) {
            $this->getContainer()->get('log')->logException(new \Exception('HMAC signature does not match'));
            $this->getContainer()->get('log')->logException(new \Exception('HTTP_HMAC:' . $_SERVER['HTTP_HMAC']));
        }

        $ipn_type = $_POST['ipn_type'];
        $txn_id = $_POST['txn_id'];
        $item_name = $_POST['item_name'];
        $item_number = $_POST['item_number'];
        $amount1 = floatval($_POST['amount1']);
        $amount2 = floatval($_POST['amount2']);
        $currency1 = $_POST['currency1'];
        $currency2 = $_POST['currency2'];
        $status = intval($_POST['status']);
        $status_text = $_POST['status_text'];
        if ($status >= 100 || $status == 2 || $status == 1) {
            // payment is complete or queued for nightly payout, success
            $this->getContainer()->get('log')->logException(new \Exception('payment is complete or queued for nightly payout, success'));
            $this->getContainer()->get('log')->logException(new \Exception('status:' . $status));
            $collection = new Collection();
            $collection->where(['trade_id' => $txn_id])->where->isNotNull('order_id');
            $status = new Status();
            $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id')
                    ->where([
                        'is_default' => 1,
                        'sales_order_phase.code' => 'complete'
                    ])->limit(1);
            $orders = [];
            foreach ($collection as $log) {
                $order = new Order();
                $order->load($log['order_id']);
                $order->setData([
                    'status_id' => $status[0]['id'],
                    'base_total_paid' => $order->offsetGet('base_total'),
                    'total_paid' => $order->offsetGet('total')
                ]);
                $orders[] = $order;
            }
            foreach ($orders as $order) {
                $order->save();
            }
        } elseif ($status < 0) {
            //payment error, this is usually final but payments will sometimes be reopened if there was no exchange rate conversion or with seller consent
            $this->getContainer()->get('log')->logException(new \Exception('payment error, this is usually final but payments will sometimes be reopened if there was no exchange rate conversion or with seller consent'));
            $this->getContainer()->get('log')->logException(new \Exception('status:' . $status));
        } else {
            //payment is pending, you can optionally add a note to the order page
            $this->getContainer()->get('log')->logException(new \Exception('payment is pending, you can optionally add a note to the order page'));
            $this->getContainer()->get('log')->logException(new \Exception('status:' . $status));
        }
        return $this->getRequest()->isXmlHttpRequest() ?
                'true' :
                $this->redirect('/');
    }
}
