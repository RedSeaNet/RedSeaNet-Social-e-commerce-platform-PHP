<?php

namespace Redseanet\Sales\ViewModel;

use Redseanet\Customer\ViewModel\Account;
use Redseanet\Sales\Model\Collection\Rma;
use Redseanet\Sales\Source\Refund\Service;
use Redseanet\Sales\Source\Refund\Status;

class Refund extends Account
{
    protected $service = null;
    protected $orderUrl = 'sales/order/view/';
    protected $viewUrl = 'sales/refund/view/';
    protected $commentUrl = 'sales/refund/addcomment/';

    public function getApplication()
    {
        $collection = new Rma();
        $collection->where(['customer_id' => $this->getCustomer()->getId()])
                ->order('created_at DESC');
        if ($this->getQuery('status')) {
            $collection->getSelect()->where->notEqualTo('sales_rma.status', 5);
        }
        if ($this->getQuery('increment_id')) {
            $collection->join('sales_order', 'sales_order.id=sales_rma.order_id', [], 'left')
                    ->where(['sales_order.increment_id' => $this->getQuery('increment_id')]);
        }
        return $collection;
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
            '1-0', '2-0', '0-0',
            '1-1', '2-1', '2-4'
        ])) {
            $viewModel = new static();
            $viewModel->setTemplate('sales/refund/handler/frontend/' . $template);
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
        $status = (new Status())->getSourceArray($key < 0 ? $key : $service);
        return $status[$key] ?? '';
    }
}
