<?php

namespace Redseanet\LiveChat\Controller;

use DOMDocument;
use DOMXPath;
use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Customer\Controller\AuthActionController;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\LiveChat\Exception\InvalidIdException;
use Redseanet\LiveChat\Model\Collection\Record;
use Redseanet\LiveChat\Model\Collection\Session as Collection;
use Redseanet\LiveChat\Model\Group;
use Redseanet\LiveChat\Model\Session;
use Redseanet\LiveChat\Model\Status;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Sales\Model\Collection\Order;
use Laminas\Db\Sql\Expression;
use Laminas\Stdlib\SplQueue;

class IndexController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;
    use \Redseanet\LiveChat\Traits\Workman;
    use \Redseanet\Lib\Traits\DataCache;
    use \Redseanet\Lib\Traits\Container;

    public function prepareAction()
    {
        $config = $this->getContainer('config');
        if ($this->getRequest()->isXmlHttpRequest()) {
            $segment = new Segment('customer');
            $customer = $segment->get('customer');

            $from = $customer['id'];
            $collection = new Collection();
            $collection->where(['customer_1' => $from, 'customer_2' => $from], 'OR');
            $collection->load(true, true);
            $content = [];
            foreach ($collection as $item) {
                $content[] = (int) $item['customer_1'] < $item['customer_2'] ?
                        (int) $item['customer_1'] . '-' . $item['customer_2'] :
                        $item['customer_2'] . '-' . $item['customer_1'];
            }
            $records = [];
            $redis = new \Redis();
            $redis->connect($config['config']['adapter']['cache']['host'], 6379);
            $redis->select($config['config']['adapter']['cache']['db']);
            for ($r = 0; $r < count($content); $r++) {
                $records[$content[$r]] = [];
                $sessionRecords = $redis->hvals('livechat_record_' . $content[$r]);
                if (count($sessionRecords) > 0) {
                    for ($m = 0; $m < count($sessionRecords); $m++) {
                        $records[$content[$r]][] = unserialize(@gzdecode($sessionRecords[$m]));
                    }
                }
            }
            $result = ['records' => $records, 'customer' => ['id' => $customer['id'], 'username' => $customer['username']]];
            return $result;
        } else {
            return [];
        }
        return [];
        //exit();
    }

    public function indexAction()
    {
        $segment = new Segment('customer');
        $from = $segment->get('customer')['id'];
        $error = '';
        if (($to = $this->getRequest()->getQuery('chat')) && ($from != $to)) {
            try {
                if (substr($to, 0, 1) === 'g') {
                    $id = $this->inGroup($from, $to);
                } else {
                    $id = $this->withSingle($from, $to);
                }
            } catch (InvalidIdException $e) {
                $error = $this->translate($e->getMessage());
            }
        }
        $root = $this->getLayout('livechat');
        if ($error) {
            $root->getChild('messages', true)->addMessage($error, 'danger');
        } elseif (isset($id)) {
            $root->getChild('livechat', true)->setVariable('current', $id);
        }
        return $root;
    }

    public function closeAction()
    {
        if ($id = $this->getRequest()->getPost('id')) {
            $session = new Session();
            $session->setId($id)->remove();
            $this->flushList(Customer::ENTITY_TYPE);
        }
    }

    public function logoutAction()
    {
        if ($this->getRequest()->isPost() && ($from = $this->getRequest()->getPost('id'))) {
            $status = new Status();
            $status->load($from);
            $isUpdate = (bool) $status->getId();
            $status->setData([
                'id' => $from,
                'status' => (int) (!$status['status'])//(((int) $status['status']) > 1 ? $status['status'] : 1) - 1
            ])->save([], !$isUpdate);
        }
        exit;
    }

    public function historyAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && ($id = $this->getRequest()->getQuery('id'))) {
            $segment = new Segment('customer');
            $customer = $segment->get('customer');
            foreach (explode('-', $id) as $i) {
                if ($i != $customer->getId()) {
                    $id = $i;
                }
            }
            $retailer = new Retailer();
            $retailer->load($customer->getId(), 'customer_id');
            $products = new Product();
            $products->join('log_visitor', 'log_visitor.product_id=main_table.id', ['viewed_at' => 'created_at'], 'left')
                    ->where([
                        'main_table.store_id' => $retailer['store_id'],
                        'log_visitor.customer_id' => $customer->getId()
                    ])->order('log_visitor.created_at DESC')
                    ->limit(10);
            $products->load(false);
            $orders = new Order();
            $orders->columns(['count' => new Expression('count(1)'), 'total' => new Expression('sum(base_total)')])
                    ->join('sales_order_status', 'sales_order.status_id=sales_order_status.id', [], 'left')
                    ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left')
                    ->where(['store_id' => $retailer['store_id'], 'sales_order_phase.code' => 'complete']);
            $orders->load(true, true);
            $result = ['products' => []];
            $result['customer'] = ['name' => $customer['username'], 'avatar' => $customer['avatar']];
            $count = 3;
            $products->walk(function ($item) use (&$result, &$count) {
                if (!isset($result['products'][$item['id']]) && $count--) {
                    $result['products'][$item['id']] = [
                        'name' => $item['name'],
                        'url' => $item->getUrl(),
                        'thumbnail' => $item->getThumbnail(),
                        'viewed_at' => $item['viewed_at']
                    ];
                }
            });
            $currency = $this->getContainer()->get('currency');
            $result['orders'] = count($orders) ? [
                'count' => $orders[0]['count'] ?? 0,
                'total' => $currency->convert($orders[0]['total'] ?? 0, true)
            ] : [
                'count' => 0,
                'total' => $currency->format(0)
            ];
            $template = new Template();
            $template->setTemplate('livechat/modalContent')->setVariable('data', $result);
            return $template;
        }
        return '';
    }

    public function previewAction()
    {
        $root = $this->getLayout('livechat_preview');
        if ($url = base64_decode($this->getRequest()->getQuery('url'))) {
            $backup = libxml_disable_entity_loader(true);
            $dom = new DOMDocument();
            @$dom->loadHTML(file_get_contents($url));
            $dom->normalize();
            libxml_disable_entity_loader($backup);
            $xpath = new DOMXPath($dom);
            $image = $xpath->evaluate('//img[@src]');
            $image = $image->length ? $image->item(0)->getAttribute('src') : '';
            if (($imgs = $xpath->evaluate('//div[@id="product-media"]//img')) && $imgs->length) {
                $attrs = ['src', 'data-bttrlazyloading-sm-src', 'data-bttrlazyloading-md-src', 'data-bttrlazyloading-lg-src', 'data-bttrlazyloading-xs-src'];
                foreach ($imgs as $img) {
                    foreach ($attrs as $attr) {
                        if ($value = $img->getAttribute($attr)) {
                            $image = $value;
                            break 2;
                        }
                    }
                }
            }
            $des = $xpath->evaluate('/html/head/meta[@name="description"]');
            $title = $dom->getElementsByTagName('title');
            if (!$image || !$title->length) {
                die();
            }
            $root->getChild('main', true)->setVariables([
                'title' => $title->item(0)->nodeValue,
                'description' => $des->length ? $des->item(0)->getAttribute('content') : '',
                'image' => $image,
                'url' => $url
            ]);
        }
        return $root;
    }

    public function testAction()
    {
        //$segment = new Segment('customer');
        ///$from = $segment->get('customer')['id];
        $error = '';
        $root = $this->getLayout('livechat_test');

        return $root;
    }
}
