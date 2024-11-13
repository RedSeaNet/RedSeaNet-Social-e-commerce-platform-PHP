<?php

namespace Redseanet\Retailer\ViewModel\Catalog\Grid;

use Redseanet\Retailer\ViewModel\Eav\Grid as PGrid;
use Redseanet\Retailer\Model\Retailer as Retailer;
use Redseanet\Catalog\Model\Collection\Product as Collection;
use Redseanet\Lib\Session\Segment;

class Product extends PGrid
{
    protected $editUrl = '';
    protected $deleteUrl = '';
    protected $action = ['getEditAction', 'getDeleteAction'];
    protected $messAction = ['getExportAction'];
    protected $translateDomain = 'catalog';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getEditUrl() . '?id=' . $item['id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getDeleteUrl() . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getExportAction()
    {
        return '<a href="javascript:void(0);" onclick="var id=\'\';$(\'.grid .table [type=checkbox][value]:checked\').each(function(){id+=$(this).val()+\',\';});location.href=\'' .
                $this->getAdminUrl('dataflow_product/export/?id=') . '\'+id.replace(/\,$/,\'\');" title="' . $this->translate('Export') .
                '"><span>' . $this->translate('Export') . '</span></a>';
    }

    public function getEditUrl()
    {
        if ($this->editUrl === '') {
            $this->editUrl = $this->getAdminUrl(':ADMIN/catalog_product/edit/');
        }
        return $this->editUrl;
    }

    public function getDeleteUrl()
    {
        if ($this->deleteUrl === '') {
            $this->deleteUrl = $this->getAdminUrl(':ADMIN/catalog_product/delete/');
        }
        return $this->deleteUrl;
    }

    protected function prepareColumns($columns = [])
    {
        return parent::prepareColumns([
            'id' => [
                'label' => 'ID',
            ]
        ]);
    }

    protected function prepareCollection($collection = null)
    {
        if (is_null($collection)) {
            $collection = new Collection();
            $user = (new Segment('customer'))->get('customer');
            $retailer = new Retailer();
            $retailer->load($user['id'], 'customer_id');
            if ($retailer['store_id']) {
                $collection->where(['store_id' => $retailer['store_id']]);
            }
        }
        return parent::prepareCollection($collection);
    }
}
