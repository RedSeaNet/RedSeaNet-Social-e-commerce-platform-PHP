<?php

namespace Redseanet\Payment\Model;

use DOMDocument;
use Error;
use Exception;
use Redseanet\I18n\Model\Currency;
use Redseanet\Lib\Bootstrap;
use Redseanet\Log\Model\Collection\Payment as Collection;
use Redseanet\Log\Model\Payment as Model;
use Redseanet\Lib\Session\Segment;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Collection\Order\Status;
use Laminas\Math\Rand;
use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;

class WeChatPay extends AbstractMethod
{
    public const METHOD_CODE = 'wechat_pay';

    public function preparePayment($orders, $data = [])
    {
        $config = $this->getContainer()->get('config');
        if (isset($orders['openid'])) {
            $tradeType = 'JSAPI';
            $params = [
                'appid' => $config['payment/wechat_pay/jsapi_id'],
                'openid' => $orders['openid']
            ];
            unset($orders['openid']);
        } else {
            $tradeType = 'NATIVE';
            $params = [];
        }
        $params += [
            'appid' => $config['payment/wechat_pay/app_id'],
            'mch_id' => $config['payment/wechat_pay/mch_id'],
            'notify_url' => $this->getBaseUrl('payment/notify/'),
            'nonce_str' => Rand::getString(30),
            'body' => Bootstrap::getMerchant()->offsetGet('name'),
            'out_trade_no' => '',
            'total_fee' => 0,
            'spbill_create_ip' => $_SERVER['X-REAL-IP'] ?? $_SERVER['REMOTE_ADDR'],
            'trade_type' => $tradeType,
            'product_id' => ''
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
                $params['body'] = $item['product_name'];
                break;
            }
        }
        sort($ids);
        $params['total_fee'] = (int) ($params['total_fee'] * 100);
        $params['product_id'] = md5(implode(',', $ids));
        $params['out_trade_no'] = count($ids) > 1 ?
                md5(implode('', $ids) . Rand::getString(Rand::getInteger(1, 10), 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ')) :
                $ids[0] . Rand::getString(Rand::getInteger(1, 10), 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ');
        $params['sign'] = $this->getSign($params);
        $query = http_build_query($params);
        foreach ($logs as $log) {
            $log->setData([
                'trade_id' => $params['out_trade_no'],
                'params' => $query,
                'is_request' => 1,
                'method' => __CLASS__
            ])->save();
        }
        $this->getContainer()->get('log')->logException(new \Exception(json_encode($params)));
        // 从本地文件中加载「商户API私钥」，「商户API私钥」会用来生成请求的签名
        $merchantPrivateKeyFilePath = 'file://' . BP . 'var/cert/wechatpay/apiclient_key.pem';
        $this->getContainer()->get('log')->logException(new \Exception($merchantPrivateKeyFilePath));
        $merchantPrivateKeyInstance = Rsa::from($merchantPrivateKeyFilePath, Rsa::KEY_TYPE_PRIVATE);
        // 「商户API证书」的「证书序列号」
        $merchantCertificateSerial = 'xxxxx';
        // 从本地文件中加载「微信支付平台证书」，用来验证微信支付应答的签名
        $platformCertificateFilePath = 'file://' . BP . 'var/cert/wechatpay/wechatpay_xxxxxx.pem';
        $this->getContainer()->get('log')->logException(new \Exception($platformCertificateFilePath));
        $platformPublicKeyInstance = Rsa::from($platformCertificateFilePath, Rsa::KEY_TYPE_PUBLIC);
        // 从「微信支付平台证书」中获取「证书序列号」
        $platformCertificateSerial = PemUtil::parseCertificateSerialNo($platformCertificateFilePath);
        // 构造一个 APIv3 客户端实例
        $instance = Builder::factory([
            'mchid' => $params['mch_id'],
            'serial' => $merchantCertificateSerial,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs' => [
                $platformCertificateSerial => $platformPublicKeyInstance,
            ],
        ]);
        $result = [];
        try {
            $resp = $instance->chain('v3/pay/transactions/native')
                    ->post(['json' => [
                        'mchid' => $params['mch_id'],
                        'out_trade_no' => $params['out_trade_no'],
                        'appid' => '------',
                        'description' => $params['body'],
                        'notify_url' => $params['notify_url'],
                        'amount' => [
                            'total' => $params['total_fee'],
                            'currency' => 'CNY'
                        ]
                    ]]);
            $this->getContainer()->get('log')->logException(new \Exception(json_encode($resp)));
            $result['status_code'] = $resp->getStatusCode();
            $body = $resp->getBody();
            $result['body'] = json_decode((string) $body, true);
        } catch (\Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            // 进行错误处理
            if ($e instanceof \GuzzleHttp\Exception\RequestException && $e->hasResponse()) {
                $r = $e->getResponse();
                $result['status_code'] = $r->getStatusCode();
                $result['body'] = $r->getBody();
            }
            $result['TraceAsString'] = $e->getTraceAsString();
            $result['message'] = $e->getMessage();
            throw new Exception('An error detected.');
        }
        $this->getContainer()->get('log')->logException(new \Exception(json_encode($result)));
        $segment = new Segment('payment');
        if (!empty($result['body']['code_url'])) {
            $segment->set('wechatpay', [$tradeType, $result['body']['code_url'], $params['out_trade_no'], $params['total_fee'] / 100]);
        } elseif (($prepay = $result->getElementsByTagName('prepay_id')) && $prepay->length) {
            $segment->set('wechatpay', [$tradeType, $prepay->item(0)->nodeValue, $params['out_trade_no']]);
        } else {
            $segment->set('wechatpay', [$tradeType, $result === false ? false : true, $params['out_trade_no'], $params['total_fee'] / 100]);
        }

        return $this->getBaseUrl('payment/wechat/');
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
        $requests = [];
        $params = [
            'appid' => $config['payment/wechat_pay/app_id'],
            'mch_id' => $config['payment/wechat_pay/mch_id'],
            'nonce_str' => Rand::getString(30),
            'out_trade_no' => '',
            'out_refund_no' => '',
            'total_fee' => 0,
            'refund_fee' => 0
        ];
        $currency = new Currency();
        $currency->load('CNY', 'code');
        foreach ($logs as $log) {
            $order = new Order();
            $order->load($log['order_id']);
            $fee = (int) (100 * ($order->offsetGet('currency') !== 'CNY' ?
                    $currency->convert($order->offsetGet('base_total')) :
                    $order->offsetGet('total')));
            if (!isset($requests[$log['trade_id']])) {
                $requests[$log['trade_id']] = $params;
                $requests[$log['trade_id']]['out_trade_no'] = $log['trade_id'];
                $ids = [];
                foreach ($order->getCreditMemo() as $memo) {
                    $ids[] = $memo['increment_id'];
                }
                $requests[$log['trade_id']]['out_refund_no'] = md5(implode('', $ids));
            }
            $requests[$log['trade_id']]['total_fee'] += $fee;
            $requests[$log['trade_id']]['refund_fee'] = $requests[$log['trade_id']]['total_fee'];
        }
        foreach ($requests as $request) {
            $request['sign'] = $this->getSign($request);
            $this->request($config['payment/wechat_pay/gateway'] . 'secapi/pay/refund', $request);
        }
        return true;
    }

    public function check($id)
    {
        if (!$id) {
            return false;
        }
        $config = $this->getContainer()->get('config');
        $params = [
            'appid' => $config['payment/wechat_pay/app_id'],
            'mch_id' => $config['payment/wechat_pay/mch_id'],
            'out_trade_no' => $id,
            'nonce_str' => Rand::getString(30)
        ];
        $params['sign'] = $this->getSign($params);
        $result = $this->request($config['payment/wechat_pay/gateway'] . 'pay/orderquery', $params);
        if ($result === false) {
            return false;
        }
        $state = $result->getElementsByTagName('trade_state');
        return $state->length && $state->item(0)->nodeValue === 'SUCCESS';
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
            if (empty($data['result_code']) || $data['result_code'] !== 'SUCCESS') {
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
            $total = $currency->rconvert($data['total_fee'] / 100);
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
            return '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
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
        return strtoupper(md5(trim($str, '&') . '&key=' . $this->getContainer()->get('config')['payment/wechat_pay/app_secret']));
    }

    public function request($url, $params)
    {
        $xml = new DOMDocument();
        $root = $xml->createElement('xml');
        foreach ($params as $key => $value) {
            $node = $xml->createElement($key, $value);
            $root->appendChild($node);
        }
        $xml->appendChild($root);
        $retry = 5;
        do {
            $client = curl_init($url);
            curl_setopt($client, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($client, CURLOPT_POST, 1);
            curl_setopt($client, CURLOPT_HTTPHEADER, ['Content-Type: text/xml; charset=UTF-8']);
            curl_setopt($client, CURLOPT_POSTFIELDS, $xml->saveXML());
            $xml = curl_exec($client);
            $this->getContainer()->get('log')->logException(new \Exception($xml));
            if ($xml) {
                $result = new DOMDocument();
                $result->loadXML($xml);
                curl_close($client);
                break;
            } else {
                $retry--;
            }
        } while ($retry);
        if (!$retry) {
            $this->getContainer()->get('log')->logException(new Exception(curl_error($client)));
        }
        return $result;
    }
}
