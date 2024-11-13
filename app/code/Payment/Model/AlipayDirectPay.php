<?php

namespace Redseanet\Payment\Model;

use DOMDocument;
use Redseanet\I18n\Model\Currency;
use Redseanet\Lib\Bootstrap;
use Redseanet\Log\Model\Collection\Payment as Collection;
use Redseanet\Log\Model\Payment as Model;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Collection\Order\Status;

class AlipayDirectPay extends AbstractMethod
{
    public const METHOD_CODE = 'alipay_direct_pay';

    public function preparePayment($orders, $data = [])
    {
        $config = $this->getContainer()->get('config');
        $params = [
            'service' => Bootstrap::isMobile() ? 'alipay.wap.create.direct.pay.by.user' : 'create_direct_pay_by_user',
            'partner' => $config['payment/alipay_direct_pay/partner'],
            'sign_type' => 'MD5',
            '_input_charset' => 'utf-8',
            'out_trade_no' => '',
            'subject' => Bootstrap::getMerchant()->offsetGet('name'),
            'notify_url' => $this->getBaseUrl('payment/notify/'),
            'return_url' => $this->getBaseUrl('payment/return/'),
            $config['payment/alipay_direct_pay/seller_type'] => $config['payment/alipay_direct_pay/seller_id'],
            'payment_type' => '1',
            'total_fee' => 0
        ];
        $ids = [];
        $logs = [];
        $currency = new Currency();
        $currency->load('CNY', 'code');
        foreach ($orders as $order) {
            $ids[] = $order['increment_id'];
            if ($order->offsetGet('currency') !== 'CNY') {
                $total = $currency->convert($order->offsetGet('base_total'));
            } else {
                $total = (float) $order->offsetGet('total');
            }
            $params['total_fee'] += $total;
            $log = new Model();
            $log->setData(['order_id' => $order->getId()]);
            $logs[] = $log;
            foreach ($order->getItems() as $item) {
                $params['subject'] = $item['product_name'];
                break;
            }
        }
        sort($ids);
        $params['out_trade_no'] = $ids[0];
        if ($config['payment/alipay_direct_pay/anti_phishing'] && !empty($_SERVER['REMOTE_ADDR'])) {
            $params['anti_phishing_key'] = $this->queryTimestamp();
            $params['exter_invoke_ip'] = $_SERVER['REMOTE_ADDR'];
        }
        $query = http_build_query($params) . '&sign=' . $this->getSign($params);
        foreach ($logs as $log) {
            $log->setData([
                'trade_id' => $params['out_trade_no'],
                'params' => $query,
                'is_request' => 1,
                'method' => __CLASS__
            ])->save();
        }
        return $config['payment/alipay_direct_pay/gateway'] . '?' . $query;
    }

    public function refund($orderIds)
    {
        $config = $this->getContainer()->get('config');
        $logs = new Collection();
        $logs->columns(['order_id', 'trade_id'])
                ->where(['order_id' => $orderIds, 'is_request' => 0, 'method' => __CLASS__])
                ->group(['order_id', 'trade_id']);
        $logs->load(true, true);
        if (!count($logs)) {
            return false;
        }
        $detail = [];
        $currency = new Currency();
        $currency->load('CNY', 'code');
        foreach ($logs as $log) {
            $order = new Order();
            $order->load($log['order_id']);
            if (isset($detail[$log['trade_id']])) {
                $detail[$log['trade_id']][1] = sprintf('%.2f', $detail[$log['trade_id']][1] +
                        ($order->offsetGet('currency') !== 'CNY' ?
                        $currency->convert($order->offsetGet('base_total')) :
                        $order->offsetGet('total')));
            } else {
                $detail[$log['trade_id']] = [
                    $log['trade_id'],
                    sprintf('%.2f', $order->offsetGet('currency') !== 'CNY' ?
                            $currency->convert($order->offsetGet('base_total')) :
                            $order->offsetGet('total')),
                    'Cancel'];
            }
        }
        $params = [
            'service' => 'refund_fastpay_by_platform_pwd',
            'partner' => $config['payment/alipay_direct_pay/partner'],
            'sign_type' => 'MD5',
            '_input_charset' => 'utf-8',
            $config['payment/alipay_direct_pay/seller_type'] => $config['payment/alipay_direct_pay/seller_id'],
            'refund_date' => date('Y-m-d H:i:s'),
            'batch_no' => date('Y-m-d') . '',
            'batch_num' => count($detail),
            'detail_data' => implode('#', $detail)
        ];
        $query = http_build_query($params) . '&sign=' . $this->getSign($params);
        foreach ($logs as $log) {
            $log->setData([
                'id' => null,
                'trade_id' => $params['out_trade_no'],
                'params' => $query,
                'is_request' => 1,
                'method' => __CLASS__ . '::refund'
            ])->save();
        }
        $client = curl_init($config['payment/alipay_direct_pay/gateway'] . '?' . $query);
        curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
        curl_exec($client);
        curl_close($client);
        return true;
    }

    public function syncNotice($data)
    {
        if ($data['sign'] === $this->getSign($data)) {
            $log = new Model();
            $log->setData([
                'order_id' => null,
                'trade_id' => $data['out_trade_no'],
                'params' => http_build_query($data),
                'is_request' => 0,
                'method' => __CLASS__
            ])->save();
            return $this->getBaseUrl($data['is_success'] === 'T' ? 'checkout/success/' : 'checkout/success/');
        }
        return false;
    }

    public function asyncNotice(array $data)
    {
        if ($data['sign'] === $this->getSign($data)) {
            $config = $this->getContainer()->get('config');
            $responseText = file_get_contents($config['payment/alipay_direct_pay/gateway'] .
                    '?service=notify_verify&partner=' .
                    $config['payment/alipay_direct_pay/partner'] .
                    '&notify_id=' . $data['notify_id']);
            if (!preg_match('/true$/i', $responseText)) {
                return false;
            }
            $log = new Model();
            $log->setData([
                'order_id' => null,
                'trade_id' => $data['out_trade_no'],
                'params' => http_build_query($data),
                'is_request' => 0,
                'method' => __CLASS__
            ])->save();
            $collection = new Collection();
            $collection->where(['trade_id' => $data['out_trade_no']])
            ->where->isNotNull('order_id');
            $status = new Status();
            $status->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id')
                    ->where([
                        'is_default' => 1,
                        'sales_order_phase.code' => 'processing'
                    ])->limit(1);
            $currency = new Currency();
            $currency->load('CNY', 'code');
            $total = $currency->rconvert($data['total_fee']);
            $orders = [];
            foreach ($collection as $log) {
                $order = new Order();
                $order->load($log['order_id']);
                if ($order->getPhase()->getId() < 3) {
                    $order->setData([
                        'status_id' => $status[0]['id'],
                        'base_total_paid' => $order->offsetGet('base_total'),
                        'total_paid' => $order->offsetGet('total')
                    ]);
                    $orders[] = $order;
                    $total -= $order->offsetGet('base_total');
                }
            }
            if ($total == 0) {
                foreach ($orders as $order) {
                    $order->save();
                }
            }
            return 'success';
        }
        return false;
    }

    public function getSign(array $params)
    {
        unset($params['sign'], $params['sign_type']);
        ksort($params);
        $str = '';
        foreach ($params as $key => $param) {
            if ($param !== '') {
                $str .= $key . '=' . $param . '&';
            }
        }
        return md5(trim($str, '&') . $this->getContainer()->get('config')['payment/alipay_direct_pay/security_key']);
    }

    public function queryTimestamp()
    {
        $config = $this->getContainer()->get('config');
        $url = $config['payment/alipay_direct_pay/gateway'] .
                '?service=query_timestamp&partner=' .
                $config['payment/alipay_direct_pay/partner'] .
                '&_input_charset=UTF-8';
        $doc = new DOMDocument();
        $doc->load($url);
        $encryptKey = $doc->getElementsByTagName('encrypt_key');
        $result = $encryptKey->item(0)->nodeValue;
        return $result ?: '';
    }
}
