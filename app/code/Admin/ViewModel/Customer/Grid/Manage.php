<?php

namespace Redseanet\Admin\ViewModel\Customer\Grid;

use Redseanet\Admin\ViewModel\Eav\Grid as PGrid;
use Redseanet\Customer\Model\Collection\Customer as Collection;
use Redseanet\Log\Model\Collection\Customer as Log;
use Redseanet\Lib\Source\Store;
use Redseanet\Admin\Model\User;
use Redseanet\Lib\Session\Segment;

class Manage extends PGrid
{
    protected $action = [
        'getOrderAction' => 'Admin\\Sales\\Order::index',
        'getReviewAction' => 'Admin\\Catalog\\Product\\Review::index',
        'getEditAction' => 'Admin\\Customer\\Manage::edit',
        'getDeleteAction' => 'Admin\\Customer\\Manage::delete'
    ];
    protected $messAction = ['getExportAction' => 'Admin\\Dataflow\\Customer::export'];
    protected $translateDomain = 'customer';

    public function getEditAction($item)
    {
        $page = 1;
        if (!empty($this->query['page'])) {
            $page = $this->query['page'];
        }
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_manage/edit/?id=' . $item['id'] . '&page=' . $page) . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/customer_manage/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    public function getOrderAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/sales_order/?customer_id=') . $item['id'] . '" title="' . $this->translate('View Orders') .
                '"><span class="fa fa-fw fa-money" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('View Orders') . '</span></a>';
    }

    public function getReviewAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_product_review/?customer_id=') . $item['id'] . '" title="' . $this->translate('View Reviews') .
                '"><span class="fa fa-fw fa-reply-all" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('View Reviews') . '</span></a>';
    }

    public function getExportAction()
    {
        return '<a href="javascript:void(0);" onclick="var id=\'\';$(\'.grid .table [type=checkbox][value]:checked\').each(function(){id+=$(this).val()+\',\';});location.href=\'' .
                $this->getAdminUrl('dataflow_customer/export/?id=') . '\'+id.replace(/\,$/,\'\');" title="' . $this->translate('Export') .
                '"><span>' . $this->translate('Export') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        $userArray = (new Segment('admin'))->get('user');
        $user = new User();
        $user->load($userArray['id']);
        $columns = parent::prepareColumns([
            'increment_id' => [
                'label' => 'ID'
            ],
            'store_id' => ($user->getStore() ? [
                'type' => 'hidden',
                'value' => $user->getStore()->getId(),
                'use4sort' => false,
                'use4filter' => false
            ] : [
                'type' => 'select',
                'options' => (new Store())->getSourceArray(),
                'label' => 'Registered Store',
                'handler' => function ($id) {
                    return $this->hasPermission('Admin\I18n\Store::edit') ? ('<a href="' . $this->getAdminUrl('i18n_store/edit/?id=') . $id . '">' . $id . '</a>') : $id;
                }
            ]),
            'id' => [
                'label' => 'Level',
                'use4filter' => false,
                'use4sort' => false,
                'handler' => function ($id, &$item) {
                    $groups = $item->getGroup();
                    $result = [];
                    foreach ($groups as $group) {
                        $result[] = $group['name'];
                    }
                    $item['groups'] = $result;
                    $log = new Log();
                    $log->columns(['created_at'])
                            ->where(['customer_id' => $id])
                            ->order('id DESC')
                            ->limit(1);
                    $item['last_logged_in'] = count($log) ? $log[0]['created_at'] : '';
                    return $item->getLevel();
                }
            ],
            'avatar' => [
                'type' => 'text',
                'label' => 'Avatar',
                'use4filter' => false,
                'use4sort' => false,
                'handler' => function ($id, &$item) {
                    return '<img src="' . (!empty($id) ? $this->getBaseUrl('pub/upload/customer/avatar/' . $id) : $this->getPubUrl('backend/images/avatar-holderplace.jpg')) . '" style="width:60px; height: 60px;" />';
                }
            ]
        ]);
        return $columns + [
            'groups' => [
                'label' => 'Customer Group',
                'use4filter' => false,
                'use4sort' => false
            ],
            'last_logged_in' => [
                'type' => 'datetime',
                'label' => 'Last Logged in',
                'use4filter' => false,
                'use4sort' => false
            ],
            'created_at' => [
                'type' => 'daterange',
                'attrs' => [
                    'data-toggle' => 'datepicker'
                ],
                'label' => 'Registered at'
            ]];
    }

    protected function prepareCollection($collection = null)
    {
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'created_at';
        }
        return parent::prepareCollection(new Collection());
    }
}
