<?php

namespace Redseanet\Admin\Controller\Dataflow;

use finfo;
use Redseanet\Catalog\Model\Category;
use Redseanet\Catalog\Model\Product;
use Redseanet\Catalog\Model\Product\Type;
use Redseanet\Dataflow\Exception\InvalidCellException;
use Redseanet\Lib\Http\UploadedFile;
use Redseanet\Lib\Session\Segment;
use Redseanet\Resource\Model\Resource;

class ProductController extends AbstractController
{
    public const NAME = 'product';

    protected $columns = ['ID', 'Attribute Set', 'Product Type', 'Store', 'Category', 'Related Products', 'Up-sells', 'Cross-sells'];
    protected $columnsKey = ['id', 'attribute_set_id', 'product_type_id', 'store_id', 'category', 'product_link', 'product_link', 'product_link'];
    protected $handler = [
        'Attribute Set' => 'getAttributeSet',
        'Product Type' => 'getProductType',
        'Store' => 'getStore',
        'Category' => 'getCategory',
        'Related Products' => 'getRelatedProducts',
        'Up-sells' => 'getUpSells',
        'Cross-sells' => 'getCrossSells',
        'images' => 'getImages'
    ];
    protected $exportData = [
        'Category' => 'processCategory',
        'Related Products' => 'processRelatedProducts',
        'Up-sells' => 'processUpSells',
        'Cross-sells' => 'processCrossSells'
    ];

    public function importAction()
    {
        return $this->getLayout('dataflow_product_import');
    }

    public function exportAction()
    {
        return $this->getLayout('dataflow_product_export');
    }

    public function processImportAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->doImport((int) $this->getRequest()->getQuery('p', 0), '\\Redseanet\\Catalog\\Model\\Product', 'product_entity', Product::ENTITY_TYPE);
        }
        return $this->notFoundAction();
    }

    public function processExportAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            return $this->doExport((int) $this->getRequest()->getQuery('p', 0), '\\Redseanet\\Catalog\\Model\\Collection\\Product', Product::ENTITY_TYPE);
        }
        return $this->notFoundAction();
    }

    public function templateAction()
    {
        return $this->getTemplate(Product::ENTITY_TYPE);
    }

    protected function getProductType($value)
    {
        if (is_numeric($value)) {
            return ['product_type_id' => (int) $value];
        }
        $model = new Type();
        $model->load($value, 'code');
        if ($model->getId()) {
            return ['product_type_id' => $model->getId()];
        } else {
            $model->load($value, 'name');
            if ($model->getId()) {
                return ['product_type_id' => $model->getId()];
            } else {
                throw new InvalidCellException($this->translate('Invalid product type name %s', [$value]));
            }
        }
    }

    protected function getImages($value)
    {
        if (!($images = json_decode($value, true))) {
            $images = explode(',', $value);
        }
        $result = [];
        foreach ($images as $info) {
            if (is_scalar($info)) {
                $info = ['id' => $info, 'label' => '', 'group' => ''];
            }
            if (file_exists($fileName = BP . 'pub/resource/import/' . $info['id'])) {
                if (extension_loaded('fileinfo')) {
                    $finfo = new finfo();
                    $type = $finfo->file($fileName);
                } else {
                    $type = 'image/' . substr($info['id'], strrpos($info['id'], '.'));
                }
                $resource = new Resource();
                $resource->moveFile(new UploadedFile($fileName, $info['id'], 'image'))
                        ->setData([
                            'store_id' => null,
                            'uploaded_name' => $info['id'],
                            'file_type' => $type,
                            'category_id' => null
                        ])->save();
                $info['id'] = $resource->getId();
                $result[] = $info;
            } elseif (is_numeric($info['id'])) {
                $result[] = $info;
            }
        }
        return ['images' => json_encode($result)];
    }

    protected function getCategory($value)
    {
        if (strpos($value, ',')) {
            $value = explode(',', $value);
        }
        $result = [];
        $languageId = (new Segment('dataflow'))->get('language_id');
        foreach ((array) $value as $v) {
            $category = new Category($languageId);
            $category->load($v);
            if ($category->getId()) {
                $result[] = $category->getId();
            } else {
                $category->load($v, 'uri_key');
                if ($category->getId()) {
                    $result[] = $category->getId();
                } else {
                    $category->load($v, 'name');
                    if ($category->getId()) {
                        $result[] = $category->getId();
                    }
                }
            }
        }
        return ['category' => $result];
    }

    protected function getRelatedProducts($value, $type, $model)
    {
        if (strpos($value, ',')) {
            $value = explode(',', $value);
        }
        $result = [];
        $languageId = (new Segment('dataflow'))->get('language_id');
        foreach ((array) $value as $v) {
            $product = new Product($languageId);
            $product->load($v);
            if ($product->getId()) {
                $result[] = $product->getId();
            } else {
                $product->load($v, 'uri_key');
                if ($product->getId()) {
                    $result[] = $product->getId();
                } else {
                    $product->load($v, 'name');
                    if ($product->getId()) {
                        $result[] = $product->getId();
                    }
                }
            }
        }
        return ['product_link' => ['related' => $result] + (array) $model->offsetGet('product_link')];
    }

    protected function getUpSells($value, $type, $model)
    {
        if (strpos($value, ',')) {
            $value = explode(',', $value);
        }
        $result = [];
        $languageId = (new Segment('dataflow'))->get('language_id');
        foreach ((array) $value as $v) {
            $product = new Product($languageId);
            $product->load($v);
            if ($product->getId()) {
                $result[] = $product->getId();
            } else {
                $product->load($v, 'uri_key');
                if ($product->getId()) {
                    $result[] = $product->getId();
                } else {
                    $product->load($v, 'name');
                    if ($product->getId()) {
                        $result[] = $product->getId();
                    }
                }
            }
        }
        return ['product_link' => ['upsells' => $result] + (array) $model->offsetGet('product_link')];
    }

    protected function getCrossSells($value, $type, $model)
    {
        if (strpos($value, ',')) {
            $value = explode(',', $value);
        }
        $result = [];
        $languageId = (new Segment('dataflow'))->get('language_id');
        foreach ((array) $value as $v) {
            $product = new Product($languageId);
            $product->load($v);
            if ($product->getId()) {
                $result[] = $product->getId();
            } else {
                $product->load($v, 'uri_key');
                if ($product->getId()) {
                    $result[] = $product->getId();
                } else {
                    $product->load($v, 'name');
                    if ($product->getId()) {
                        $result[] = $product->getId();
                    }
                }
            }
        }
        return ['product_link' => ['crosssells' => $result] + (array) $model->offsetGet('product_link')];
    }

    protected function processCategory($product)
    {
        $result = [];
        foreach ($product->getCategories() as $category) {
            $result[] = $category['id'];
        }
        return implode(',', $result);
    }

    protected function processRelatedProducts($product)
    {
        $result = [];
        foreach ($product->getLinkedProducts('r') as $link) {
            $result[] = $link['id'];
        }
        return implode(',', $result);
    }

    protected function processUpSells($product)
    {
        $result = [];
        foreach ($product->getLinkedProducts('u') as $link) {
            $result[] = $link['id'];
        }
        return implode(',', $result);
    }

    protected function processCrossSells($product)
    {
        $result = [];
        foreach ($product->getLinkedProducts('c') as $link) {
            $result[] = $link['id'];
        }
        return implode(',', $result);
    }
}
