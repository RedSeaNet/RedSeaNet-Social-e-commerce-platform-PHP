<?php

namespace Redseanet\Retailer\ViewModel;

use Redseanet\Sales\Model\Collection\Rma;
use Redseanet\Sales\Source\Refund\Service;
use Redseanet\Sales\Source\Refund\Status;

class Refund extends AbstractViewModel
{
    protected $status = null;
    protected $service = null;
    protected $orderUrl = 'retailer/sales_order/view/';
    protected $viewUrl = 'retailer/refund/view/';
    protected $commentUrl = 'retailer/refund/addcomment/';

    public function getApplication()
    {
        $collection = new Rma();
        $collection->join('sales_order', 'sales_order.id=sales_rma.order_id', [], 'left')
                ->join('retailer', 'sales_order.store_id=retailer.store_id', [], 'left')
                ->where(['retailer.id' => $this->getRetailer()->getId()])
                ->order('created_at DESC');
        if ($this->getQuery('status')) {
            $collection->getSelect()->where->notEqualTo('sales_rma.status', 5);
        }
        if ($this->getQuery('increment_id')) {
            $collection->where(['sales_order.increment_id' => $this->getQuery('increment_id')]);
        }
        return $collection;
    }

    public function getCurrency()
    {
        return $this->getContainer()->get('currency');
    }

    public function getService($key)
    {
        if (is_null($this->service)) {
            $this->service = (new Service())->getSourceArray();
        }
        return $this->service[$key] ?? '';
    }

    public function getHandler($service, $status)
    {
        $template = $service . '-' . $status;
        if (in_array($template, [
            '0-0', '1-0', '2-0',
            '1-2', '1-3', '2-2', '2-3'
        ])) {
            $viewModel = new static();
            $viewModel->setTemplate('sales/refund/handler/backend/' . $template);
        }
        return $viewModel ?? '';
    }

    public function getOrderUrl()
    {
        return $this->orderUrl;
    }

    public function getViewUrl()
    {
        return $this->viewUrl;
    }

    public function getCommentUrl()
    {
        return $this->getBaseUrl($this->commentUrl);
    }

    public function getStatus($service, $key)
    {
        $status = (new Status())->getSourceArray($key == -1 ? $key : $service);
        return $status[$key] ?? '';
    }
}
