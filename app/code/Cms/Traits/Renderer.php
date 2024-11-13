<?php

namespace Redseanet\Cms\Traits;

use Redseanet\Catalog\Model\Category as CatalogCategory;
use Redseanet\Catalog\ViewModel\Category\ProductList;
use Redseanet\Cms\ViewModel\Block;
use Redseanet\Cms\ViewModel\Category;
use Redseanet\Cms\ViewModel\Bulk;
use Redseanet\Cms\ViewModel\Bargain;
use Redseanet\Cms\ViewModel\Banner;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Lib\ViewModel\Template;

/**
 * Replace variables from cms
 */
trait Renderer
{
    /**
     * Replace variables
     *
     * @param string $content
     * @param array $vars
     * @return string
     */
    protected function replace($content, array $vars = [])
    {
        if ($content) {
            $content = str_replace(['&quot;', '&#39;'], ['"', '\''], $content);
            preg_match_all('#{{\s*(?P<type>[^\s\}\'\"]+)(?P<param>(?:\s+[^\s\}]+)*)}}#', $content, $matches);
            $replace = [];
            if (count($matches[0])) {
                foreach ($matches[0] as $key => $src) {
                    if (isset($vars[$matches['type'][$key]])) {
                        $replace[$src] = $vars[$matches['type'][$key]];
                    } elseif (is_callable([$this, 'replace' . $matches['type'][$key]])) {
                        $replace[$src] = call_user_func([$this, 'replace' . $matches['type'][$key]], $matches['param'][$key], $vars);
                    } else {
                        $replace[$src] = '';
                    }
                }
            }
            if (count($replace)) {
                return str_replace(array_keys($replace), array_values($replace), $content);
            }
        }
        return $content;
    }

    /**
     * Replace block variables
     *
     * @param string $param
     * @param array $vars
     * @return string
     */
    protected function replaceBlock($param, $vars = [])
    {
        preg_match_all('#\s+(?P<key>[^\=]+)\=([\'\"])(?P<value>[^\2]+?)\2#', $param, $matches);
        $params = array_combine($matches['key'], $matches['value']);
        if ((!isset($params['type']) || trim($params['type'], '\\') === 'Redseanet\\Cms\\ViewModel\\Block') && isset($params['id'])) {
            $block = new Block();
            $block->setBlockId($params['id']);
        } elseif (isset($params['name']) && is_callable([$this, 'getChild'])) {
            $block = $this->getChild($params['name']);
        } elseif (isset($params['name'], $params['template'])) {
            $block = new Template();
            $block->setTemplate($params['template'])->setVariables($vars[$params['name']]);
        } else {
            return '';
        }
        return $block ? $block->__toString() : '';
    }

    protected function replaceCategory($param)
    {
        preg_match_all('#\s+(?P<key>[^\=]+)\=([\'\"])(?P<value>[^\2]+?)\2#', $param, $matches);
        $params = array_combine($matches['key'], $matches['value']);
        if (empty($params['id'])) {
            return '';
        }
        $category = new Category();
        if (!empty($params['template'])) {
            $category->setTemplate($params['template']);
            unset($params['template']);
        }
        $category->setVariables($params);
        return $category->__toString();
    }

    protected function replaceProducts($param)
    {
        preg_match_all('#\s+(?P<key>[^\=]+)\=([\'\"])(?P<value>[^\2]+?)\2#', $param, $matches);
        $params = array_combine($matches['key'], $matches['value']);
        if (empty($params['category'])) {
            return '';
        }
        $category = new CatalogCategory();
        $category->load($params['category']);
        unset($params['category']);
        $products = $category->getProducts();
        $products->order('product_in_category.sort_order desc');
        $products->order('main_table.created_at desc');
        $list = new ProductList();
        if (isset($params['template'])) {
            $list->setTemplate($params['template']);
            unset($params['template']);
        }
        $list->setCategory($category)
                ->setProducts($products)
                ->setVariables($params);
        return $list->__toString();
    }

    protected function replaceStore($param)
    {
        preg_match_all('#\s+(?P<key>[^\=]+)\=([\'\"])(?P<value>[^\2]+?)\2#', $param, $matches);
        $params = array_combine($matches['key'], $matches['value']);
        if (empty($params['id'])) {
            return '';
        }
        $retailer = new Retailer();
        $retailer->load($params['id'], 'store_id');
        if (!$retailer->getId()) {
            return '';
        }
        unset($params['id']);
        return $this->getBaseUrl('store/' . (!empty($retailer['uri_key']) ? rawurlencode($retailer['uri_key']) : '') . '.html');
    }

    protected function replaceBulk($param)
    {
        //var_dump($param);
        //exit('99999-----------');
        preg_match_all('#\s+(?P<key>[^\=]+)\=([\'\"])(?P<value>[^\2]+?)\2#', $param, $matches);
        $params = array_combine($matches['key'], $matches['value']);
        $bulk = new Bulk();
        if (!empty($params['template'])) {
            $bulk->setTemplate($params['template']);
            unset($params['template']);
        }
        $bulk->setVariables($params);
        return $bulk->__toString();
    }

    protected function replaceBargain($param)
    {
        //var_dump($param);
        //exit('99999-----------');
        preg_match_all('#\s+(?P<key>[^\=]+)\=([\'\"])(?P<value>[^\2]+?)\2#', $param, $matches);
        $params = array_combine($matches['key'], $matches['value']);
        $bargain = new Bargain();
        if (!empty($params['template'])) {
            $bargain->setTemplate($params['template']);
            unset($params['template']);
        }
        $bargain->setVariables($params);
        return $bargain->__toString();
    }

    protected function replaceBanner($param)
    {
        preg_match_all('#\s+(?P<key>[^\=]+)\=([\'\"])(?P<value>[^\2]+?)\2#', $param, $matches);
        $params = array_combine($matches['key'], $matches['value']);
        $banner = new Banner();
        if (!empty($params['template'])) {
            $banner->setTemplate($params['template']);
            unset($params['template']);
        }
        $banner->setVariables($params);
        return $banner->__toString();
    }
}
