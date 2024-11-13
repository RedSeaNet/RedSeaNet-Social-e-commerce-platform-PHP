<?php

namespace Redseanet\Admin\ViewModel\Catalog\Grid;

use Redseanet\Admin\ViewModel\Grid as PGrid;
use Redseanet\Catalog\Model\Collection\PostRelation as Collection;
use Redseanet\Catalog\Model\Collection\Product;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\Bootstrap;

class Post extends PGrid
{
    protected $action = [
        'getEditAction' => 'Admin\\Catalog\\Forum::edit',
        'getDeleteAction' => 'Admin\\Catalog\\Forum::delete',
    ];
    protected $messAction = [
    ];
    protected $translateDomain = 'catalog';

    public function getEditAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_forum/edit/?id=') . $item['product_id'] . '" title="' . $this->translate('Edit') .
                '"><span class="fa fa-fw fa-file-text-o" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Edit') . '</span></a>';
    }

    public function getDeleteAction($item)
    {
        return '<a href="' . $this->getAdminUrl(':ADMIN/catalog_forum/delete/') . '" data-method="delete" data-params="id=' . $item['id'] .
                '&csrf=' . $this->getCsrfKey() . '" title="' . $this->translate('Delete') .
                '"><span class="fa fa-fw fa-remove" aria-hidden="true"></span><span class="sr-only">' .
                $this->translate('Delete') . '</span></a>';
    }

    protected function prepareColumns($columns = [])
    {
        $columns = [
            'id' => [
                'label' => 'ID',
                'use4sort' => false,
                'use4filter' => false,
            ],
            'product_id' => [
                'label' => 'Product ID',
                'type' => 'text',
            ],
            'name' => [
                'label' => 'Product Name',
                'type' => 'text',
                'handler' => function ($id, &$item) {
                    return $this->hasPermission('Admin\Catalog\Product::edit') ? '<a href="' . $this->getAdminUrl('catalog_product/edit/?id=' . $item['product_id']) . '">' . $id . '</a>' : $id;
                }
            ],
            'post_id' => [
                'label' => 'Post ID',
                'type' => 'text',
            ],
            'title' => [
                'label' => 'Post Title',
                'type' => 'text',
                'handler' => function ($id, &$item) {
                    return $this->hasPermission('Admin\Forum\Post::edit') ? '<a href="' . $this->getAdminUrl('forum_post/edit/?id=' . $item['post_id']) . '">' . $id . '</a>' : $id;
                }
            ],
            'username' => [
                'label' => 'Poster',
                'type' => 'text',
            ],
        ];
        $columns['created_at'] = [
            'type' => 'daterange',
            'label' => 'Created at',
            'attrs' => [
                'data-toggle' => 'datepicker',
            ],
            'use4sort' => false,
            'use4filter' => false,
        ];

        return $columns;
    }

    protected function prepareCollection($collection = null)
    {
        $collection = new Product();
        $collection->columns(['name']);
        $collection->join('forum_product_relation', 'forum_product_relation.product_id=main_table.id', ['id', 'product_id', 'post_id', 'created_at'], 'inner');
        $collection->join('forum_post', 'forum_post.id=forum_product_relation.post_id', ['title'], 'inner');
        $collection->join('customer_1_index', 'customer_1_index.id=forum_post.customer_id', ['username'], 'inner');
        $user = (new Segment('admin'))->get('user');
        if (!$this->getQuery('desc')) {
            $this->query['desc'] = 'forum_product_relation.created_at';
        }
        if ($this->getQuery('title')) {
            $collection->where("forum_post.title like '%" . $this->getQuery('title') . "%'");
            $this->bannedFields[] = 'title';
        }
        if ($this->getQuery('username')) {
            $collection->where("customer_1_index.username like '%" . $this->getQuery('username') . "%'");
            $this->bannedFields[] = 'username';
        }
        if ($this->getQuery('product_id')) {
            $collection->where('forum_product_relation.product_id=' . $this->getQuery('product_id'));
            $this->bannedFields[] = 'product_id';
        }
        if ($this->getQuery('name')) {
            $collection->where("main_table.name like '%" . $this->getQuery('name') . "%'");
            $this->bannedFields[] = 'name';
        }
        return parent::prepareCollection($collection);
    }
}
