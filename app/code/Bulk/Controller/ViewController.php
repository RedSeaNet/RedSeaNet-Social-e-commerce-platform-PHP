<?php

namespace Redseanet\Bulk\Controller;

use Redseanet\Bulk\Model\Bulk;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Catalog\Model\Collection\Product;

class ViewController extends ActionController
{
    public function indexAction()
    {
        if ($id = $this->getRequest()->getQuery('bulk')) {
            $bulk = new Bulk();
            $bulk->load($id);
            if ($bulk->getId()) {
                $root = $this->getLayout('bulk_sale_view');
                $root->getChild('head')->setDescription(preg_replace('/\<[^\>]+\>/', '', $bulk['description']));
                $root->getChild('main', true)->setVariable('model', $bulk);
                return $root;
            }
        }
        return $this->redirectReferer();
    }

    public function listAction()
    {
        $root = $this->getLayout('bulk_sale_view_products');
        $root->getChild('head')->setDescription('Bulk');
        $limit = 20;
        $collection = new Product();
        $collection->where("bulk_price!=''");
        $collection->limit($limit);
        $products = $collection->load();
        $content = $root->getChild('content');

        $content->getChild('main')->setVariable('products', $products);
        return $root;
    }
}
