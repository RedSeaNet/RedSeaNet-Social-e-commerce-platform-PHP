<?php

namespace Redseanet\Admin\Controller;

use DOMDocument;
use DOMXPath;
use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Customer\Model\Customer;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\LiveChat\Exception\InvalidIdException;
use Redseanet\LiveChat\Model\Collection\Record;
use Redseanet\LiveChat\Model\Collection\Session as Collection;
use Redseanet\LiveChat\Model\Group;
use Redseanet\LiveChat\Model\Session;
use Redseanet\LiveChat\Model\Status;
use Redseanet\Sales\Model\Collection\Order;
use Laminas\Db\Sql\Expression;
use Laminas\Stdlib\SplQueue;

class LivechatController extends AuthActionController
{
    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    protected function mergeMsg($items, $partial)
    {
        ksort($partial, SORT_NUMERIC);
        $item = array_shift($partial);
        $item['msg'] .= implode('', $partial);
        $items->push($item);
    }

    protected function getRecords($from)
    {
        $record = new Record();
        $record->join('livechat_session', 'livechat_session.session_id=livechat_record.session_id', [], 'left')
                ->where(['livechat_session.customer_1' => null]);
        $record->order('livechat_record.created_at');
        $result = [];
        $partial = [];
        $record->load(false, true);
        $record->walk(function ($item) use (&$result, &$partial) {
            $item = [
                'type' => $item['type'],
                'sender' => $item['sender'],
                'session' => $item['session_id'],
                'msg' => $item['message'],
                'partial' => $item['partial'],
                'end' => 1
            ];
            $key = $item['session'] . '*' . $item['sender'];
            if (!isset($result[$item['session']])) {
                $result[$item['session']] = new SplQueue();
            }
            if (!is_null($item['partial'])) {
                if (!isset($partial[$key])) {
                    $partial[$key] = [$item];
                } elseif ($item['partial'] == 0) {
                    $this->mergeMsg($result[$item['session']], $partial[$key]);
                    $partial[$key] = [$item];
                } else {
                    $partial[$key][(int) $item['partial']] = $item['msg'];
                }
            } elseif (!empty($partial[$key])) {
                $this->mergeMsg($result[$item['session']], $partial[$key]);
                unset($partial[$key]);
            } else {
                $result[$item['session']]->push($item);
            }
            if ($result[$item['session']]->count() > 20) {
                $result[$item['session']]->shift();
            }
        });
        foreach ($partial as $key => $value) {
            $this->mergeMsg($result[explode('*', $key)[0]], $value);
        }
        foreach ($result as &$item) {
            $item = $item->toArray();
        }
        return $result;
    }

    public function prepareAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $collection = new Collection();
            $collection->where(['customer_1' => 0]);
            $collection->load(true, true);
            $content = [];
            foreach ($collection as $item) {
                $content[] = 0 . '-' . $item['customer_2'];
            }
            $fp = fopen(sys_get_temp_dir() . '/livechat-0', 'w');
            fwrite($fp, json_encode($content));
            fclose($fp);
            if ($this->getRequest()->isGet()) {
                return $this->getRecords(0);
            }
        }
        exit();
    }

    public function indexAction()
    {
        $from = 0;
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
        $root = $this->getLayout('livechat_admin');
        if ($error) {
            $root->getChild('messages', true)->addMessage($error, 'danger');
        } elseif (isset($id)) {
            $root->getChild('livechat', true)->setVariable('current', $id);
        }
        return $root;
    }

    protected function withSingle($from, $to)
    {
        if ($this->canChat($from, $to)) {
            $session = new Session();
            $data = ['customer_1' => null, 'customer_2' => $to];
            $id = 0 . '-' . $to;
            $session->load($data);
            if (!$session->getId()) {
                $session->setData($data + ['session_id' => '0-' . $data['customer_2']])->save([], true);
            }
            return $id;
        } else {
            throw new InvalidIdException('Invalid chat id');
        }
    }

    protected function inGroup($from, $to)
    {
        $group = new Group();
        $group->load(substr($to, 1));
        if (in_array($from, $group->getMembers())) {
            return 'g' . $to;
        } else {
            throw new InvalidIdException('Invalid chat id');
        }
    }

    protected function canChat($from, $to)
    {
        return true;
    }

    public function closeAction()
    {
        if ($id = $this->getRequest()->getPost('id')) {
            $session = new Session();
            $session->setId($id)->remove();
            $this->flushList(Customer::ENTITY_TYPE);
            $this->getTableGateway('livechat_record')->delete(['session_id' => $id]);
            $this->flushList('livechat_record');
        }
    }

    public function historyAction()
    {
        if ($this->getRequest()->isXmlHttpRequest() && ($id = $this->getRequest()->getQuery('id'))) {
            $id = explode('-', $id)[1];
            $customer = new Customer();
            $customer->load($id);
            $products = new Product();
            $products->join('log_visitor', 'log_visitor.product_id=main_table.id', ['viewed_at' => 'created_at'], 'left')
                    ->where(['log_visitor.customer_id' => $id])
                    ->order('log_visitor.created_at DESC')
                    ->limit(10);
            $products->load(false);
            $orders = new Order();
            $orders->columns(['count' => new Expression('count(1)'), 'total' => new Expression('sum(base_total)')])
                    ->join('sales_order_status', 'sales_order.status_id=sales_order_status.id', [], 'left')
                    ->join('sales_order_phase', 'sales_order_status.phase_id=sales_order_phase.id', [], 'left')
                    ->where(['sales_order_phase.code' => 'complete']);
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

    public function uploadAction()
    {
        if ($this->getRequest()->isPost()) {
            $file = $this->getRequest()->getUploadedFile();
            $class = new class () {
                use \Redseanet\Resource\Traits\Upload\Local;

                public static $options = [
                    'path' => 'pub/upload/livechat/',
                    'dir_mode' => 0755
                ];
                public $name;

                protected function setData($data)
                {
                    $this->name = $data['real_name'];
                }
            };
            if ($class->chunk($file['file'], $this->getRequest()->getHeader('HTTP_CONTENT_RANGE')['HTTP_CONTENT_RANGE'])) {
                return $this->getBaseUrl('pub/upload/livechat/' .
                                substr($file['file']->getClientMediaType(), 0, strpos($file['file']->getClientMediaType(), '/') + 1) . $class->name);
            }
        }
        exit();
    }
}
