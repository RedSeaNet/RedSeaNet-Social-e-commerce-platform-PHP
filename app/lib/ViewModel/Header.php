<?php

namespace Redseanet\Lib\ViewModel;

use CssMin;
use Error;
use Exception;
use JShrink\Minifier;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Stdlib\Singleton;
use Redseanet\Catalog\Model\Collection\Category as ProductCategory;
use Redseanet\Lib\Session\Segment;

/**
 * Header view model
 */
final class Header extends Template implements Singleton
{
    protected static $instance = null;
    protected $base = null;

    private function __construct()
    {
    }

    public static function instance()
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    public function getBase()
    {
        return $this->base;
    }

    public function setBase($base)
    {
        $this->base = $base;
        return $this;
    }

    public function getNavCategoriesTwoLevel()
    {
        $navCategoriesReturn = [];
        $navCategories = new ProductCategory();
        $navCategories->where(['include_in_menu' => 1]);
        $navCategories->load(true, true);
        if (count($navCategories) > 0) {
            for ($c = 0; $c < count($navCategories); $c++) {
                $navSubCategories = new ProductCategory();
                $navSubCategories->where(['include_in_menu' => 1, 'parent_id' => $navCategories[$c]['id']]);
                $navSubCategories->load(true, true);
                $navCategories[$c]['sub_categories'] = $navSubCategories;

                $result = $this->getContainer()->get('indexer')->select('catalog_url', $this->getContainer()->get('language')['id'], ['product_id' => null, 'category_id' => $navCategories[$c]['id']]);
                $navCategories[$c]['path'] = $this->getBaseUrl(isset($result[0]) ? $result[0]['path'] . '.html' : '');

                $navCategoriesReturn[] = $navCategories[$c];
            }
            return $navCategoriesReturn;
        } else {
            return [];
        }
    }

    public function getCustomer()
    {
        $segment = new Segment('customer');
        return $segment->get('customer');
    }
}
