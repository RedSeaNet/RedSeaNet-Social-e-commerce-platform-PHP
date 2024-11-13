<?php

namespace Redseanet\TrackingMore\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Sales\Model\Collection\Shipment\Track as Collection;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Shipment\Track as Model;

class TrackingMore implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    private $gateway = 'https://api.trackingmore.com/v2/';
    private $config;

    public function __construct()
    {
        $this->config = $this->getContainer()->get('config');
    }

    private function prepareCurl($path)
    {
        $ch = curl_init($this->gateway . $path);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Trackingmore-Api-Key: ' . $this->config['tracking/trackingmore/api_key'],
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        return $ch;
    }

    private function getCollection()
    {
        $collection = new Collection();
        $collection->columns(['carrier_code', 'tracking_number'])
                ->join('sales_order', 'sales_order.id=sales_order_shipment_track.order_id', [], 'left')
                ->join('sales_order_status', 'sales_order.status_id=sales_order_status.id', [], 'left')
                ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left')
                ->group(['carrier_code', 'tracking_number'])
                ->order('created_at DESC')
                ->where(['sales_order_phase.code' => 'success'])
                ->where->notEqualTo('sales_order_status.is_default', 1)
                ->isNotNull('carrier_code');
        return $collection;
    }

    private function getLatestTrackInfo($code, $number)
    {
        $collection = new Collection();
        $collection->where([
            'carrier_code' => $code,
            'tracking_number' => $number
        ])
                ->order('id DESC')
                ->limit(1);
        return $collection->count() ? $collection->toArray()[0] : [];
    }

    private function doDelete($code, $number)
    {
        $ch = $this->prepareCurl('trackings/' . $code . '/' . $number);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_exec($ch);
        curl_close($ch);
    }

    public function request()
    {
        if (empty($this->config['tracking/trackingmore/enable']) || empty($this->config['tracking/trackingmore/api_key'])) {
            return;
        }
        $page = ceil($this->getCollection()->count() / 100);
        $adapter = $this->getContainer()->get('dbAdapter');
        for ($p = 1; $p <= $page; $p++) {
            $ch = $this->prepareCurl('trackings/get?limit=100&page=' . $p);
            $result = json_decode(curl_exec($ch));
            curl_close($ch);
            foreach ($result['data']['items'] as $item) {
                $latest = $this->getLatestTrackInfo($item['carrier_code'], $item['tracking_number']);
                if (strtotime($item['updated_at']) <= strtotime($latest['Date'])) {
                    continue;
                }
                foreach ($item['origin_info']['trackinfo'] as $info) {
                    if (strtotime($info['Date']) > strtotime($latest['Date'])) {
                        $model = new Model();
                        $model->setData([
                            'id' => null,
                            'description' => $info['StatusDescription'],
                            'created_at' => $info['Date']
                        ] + $latest);
                    }
                }
                foreach ($item['destination_info']['trackinfo'] as $info) {
                    if (strtotime($info['Date']) > strtotime($latest['Date'])) {
                        $model = new Model();
                        $model->setData([
                            'id' => null,
                            'description' => $info['StatusDescription'],
                            'created_at' => $info['Date']
                        ] + $latest);
                    }
                }
                if (in_array($item['status'], ['delivered', 'undelivered', 'exception', 'expired'])) {
                    $this->doDelete($item['carrier_code'], $item['tracking_number']);
                }
            }
        }
    }

    public function schedule($e)
    {
        if (empty($this->config['tracking/trackingmore/enable']) || empty($this->config['tracking/trackingmore/api_key'])) {
            return;
        }
        $track = $e['model'];
        if ($track['schedule'] && $track['carrier_code']) {
            $order = new Order();
            $order->load($track['order_id']);
            $ch = $this->prepareCurl('trackings/post');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'tracking_number' => $track['tracking_number'],
                'carrier_code' => $track['carrier_code'],
                'title' => $track['shipment_id'],
                'lang' => $order->getLanguage()['code']
            ]));
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
