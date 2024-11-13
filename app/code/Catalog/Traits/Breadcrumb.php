<?php

namespace Redseanet\Catalog\Traits;

use Redseanet\Catalog\Model\Category;
use Redseanet\Catalog\Model\Product;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\ViewModel\Breadcrumb as ViewModel;

trait Breadcrumb
{
    /**
     * @param ViewModel $breadcrumb
     * @param int $categoryId
     * @param int $productId
     */
    protected function generateCrumbs(ViewModel $breadcrumb, $categoryId, $productId = null)
    {
        $indexer = $this->getContainer()->get('indexer');
        $languageId = Bootstrap::getLanguage()->getId();
        $this->addCrumb($breadcrumb, $categoryId, $indexer, $languageId);
        if ($productId) {
            $result = $indexer->select('catalog_url', Bootstrap::getLanguage()->getId(), [
                'category_id' => $categoryId,
                'product_id' => $productId
            ]);
            $product = new Product($languageId);
            $product->load($productId);
            $breadcrumb->addCrumb([
                'link' => $result[0]['path'] . '.html',
                'label' => $product['name']
            ]);
        }
    }

    protected function addCrumb(ViewModel $breadcrumb, $categoryId, $indexer = null, $languageId = null)
    {
        if (is_null($indexer)) {
            $indexer = $this->getContainer()->get('indexer');
        }
        if (is_null($languageId)) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $category = new Category($languageId);
        $category->load($categoryId);
        if ($category['parent_id']) {
            $this->addCrumb($breadcrumb, $category['parent_id'], $indexer);
            $breadcrumb->addCrumb([
                'link' => $indexer->select('catalog_url', $languageId, [
                    'category_id' => $categoryId,
                    'product_id' => null
                ])[0]['path'] . '.html',
                'label' => $category['name']
            ]);
        }
    }
}
