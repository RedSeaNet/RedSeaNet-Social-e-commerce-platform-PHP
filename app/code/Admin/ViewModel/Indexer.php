<?php

namespace Redseanet\Admin\ViewModel;

use Redseanet\Lib\Model\Collection\Eav\Type as Collection;

class Indexer extends Grid
{
    protected $action = [
        'getReindexAction' => 'Admin\\Indexer\\Rebuild',
        'getScheduleAction' => 'Admin\\Indexer\\Schedule'
    ];
    protected $messAction = [
        'getMessReindexAction' => 'Admin\\Indexer\\Rebuild',
        'getMessScheduleAction' => 'Admin\\Indexer\\Schedule'
    ];

    public function getReindexAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/indexer/rebuild/') . '" data-method="post" data-params="id=' . $item['id'] . '" title="' . $this->translate('Rebuild') .
                '"><span class="fa fa-fw fa-refresh" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Rebuild') . '</span></a>';
    }

    public function getScheduleAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/indexer/schedule/') . '" data-method="post" data-params="id=' . $item['id'] . '" title="' . $this->translate('Rebuild by Schedule') .
                '"><span class="fa fa-fw fa-calendar" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Rebuild by Schedule') . '</span></a>';
    }

    public function getMessReindexAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/indexer/rebuild/') . '" data-method="post" data-serialize=".grid .table" title="' . $this->translate('Rebuild') .
                '"><span>' . $this->translate('Rebuild') . '</span></a>';
    }

    public function getMessScheduleAction()
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/indexer/schedule/') . '" data-method="post" data-serialize=".grid .table" title="' . $this->translate('Rebuild by Schedule') .
                '"><span>' . $this->translate('Rebuild by Schedule') . '</span></a>';
    }

    protected function prepareColumns()
    {
        return [
            'code' => [
                'label' => 'Code',
                'use4filter' => false
            ]
        ];
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Collection();
        $collection->columns(['code']);
        $indexers = [];
        foreach ($collection as $type) {
            $indexers[] = ['code' => ucwords(str_replace('_', ' ', $type['code'])), 'id' => $type['code']];
        }
        $config = $this->getConfig();
        if (isset($config['indexer'])) {
            foreach ($config['indexer'] as $code => $info) {
                $indexers[] = ['code' => $info['title'] ?? $code, 'id' => $code];
            }
        }
        return $indexers;
    }
}
