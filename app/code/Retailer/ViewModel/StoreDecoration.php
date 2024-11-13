<?php

namespace Redseanet\Retailer\ViewModel;

use Redseanet\Lib\ViewModel\Template;
use Redseanet\Retailer\ViewModel\SalesProducts;
use Redseanet\Retailer\Model\StoreTemplate;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Retailer\Model\Collection\Retailer as RetailerCollection;
use Redseanet\Retailer\Model\Collection\StoreTemplateCollection;
use Redseanet\Retailer\Model\Collection\StorePicInfoCollection;
use Redseanet\Lib\Session\Segment;
use Redseanet\Retailer\Model\Collection\Category as categoryCollection;
use Redseanet\Retailer\Source\Category as categoryResource;

class StoreDecoration extends Template
{
    /**
     * Get store template view
     *
     * @access public
     * @return object
     */
    public function judge_store_id($current_store_id = null)
    {
        if (empty($current_store_id)) {
            $segment = new Segment('customer');
            $r = new Retailer();
            $r->load($segment->get('customer')['id'], 'customer_id');
            $store_id = $r['store_id'];
        } else {
            $store_id = $current_store_id;
        }
        return $store_id;
    }

    public function getRetailerByStoreId($storeId)
    {
        $r = new Retailer();
        $r->load($storeId, 'store_id');
        return $r;
    }

    public function getTemplateView($current_store_id = null, $model = 0)
    {
        $templateView = [];
        $id = $this->getQuery('id');
        $key = $this->getQuery('key');
        $store_id = $this->judge_store_id($current_store_id);
        if ($model != 0) {
            $id = $model;
        }
        if (!empty($id)) {
            if ($id != '-1') {
                $template = new StoreTemplate();
                $templateView = $template->load($id);
            }
        } else {
            if (empty($key)) {
                $template = new StoreTemplateCollection();
                $templateViewCollection = $template->storeTemplateList($store_id, 1);
                if (!empty($templateViewCollection)) {
                    $templateView = $templateViewCollection[0];
                } else {
                    $templateView = [];
                }
            } else {
                $key = base64_decode($key);
                $storeTemplate = new storeTemplate();
                $templateView = $storeTemplate->load($key);
            }
        }
        if (!empty($templateView)) {
            $templateView['code_model'] = $this->changeModel($templateView['code_model'], $store_id);
            $templateView['src_model'] = $this->changeModel($templateView['src_model'], $store_id);
            $templateView['stable_params'] = $this->changeStableParams($templateView['stable_params'], $store_id);
        }

        if (!empty($templateView) && $templateView['store_id'] != $store_id && $templateView['store_id'] != 0 && is_null($current_store_id)) {
            $templateView = [];
        }

        return $templateView;
    }

    public function getProductDetailTemplateView($store_id)
    {
        $template = new StoreTemplateCollection();
        $template->storeTemplateList($store_id, 1);
        $template_id = count($template) > 0 ? $template[0]['id'] : -2;
        $view = '';
        if ($template_id > 0) {
            $template = new StoreTemplateCollection();
            $template->storeCustomizeTemplate($store_id, $template_id, 2);
            $final_id = count($template) > 0 ? $template[0]['id'] : -2;
            $view = $this->getTemplateView($store_id, $final_id);
        }
        return $view;
    }

    public function getTemplateByIdView($id)
    {
        $templateView = [];
        $template = new StoreTemplate();
        $template->load($id);
        if (!empty($template['store_id'])) {
            $templateView = $template->toArray();
        } else {
            $templateView = [];
        }
        if (!empty($templateView['store_id'])) {
            $templateView['code_model'] = $this->changeModel($templateView['code_model'], $templateView['store_id']);
            $templateView['src_model'] = $this->changeModel($templateView['src_model'], $templateView['store_id']);
            $templateView['stable_params'] = $this->changeStableParams($templateView['stable_params'], $templateView['store_id']);
        }
        return $templateView;
    }

    public function changeModel($view, $current_store_id = null)
    {
        $final_view = $view;
        $tempParams = [
            'long_search',
            'short_search',
            'product_class',
            'sales_amount',
            'hot_product',
            'store_recommend',
            'product_recommend',
            'pic_carousel'];
        foreach ($tempParams as $value) {
            $params = $this->divideParam($value, $final_view);
            foreach ($params as $key1 => $value1) {
                $func = 'template_' . $value;
                $final_view = str_replace('{{' . $value . ':' . $value1 . '}}', $this->$func($value1, $current_store_id), $final_view);
            }
        }
        return $final_view;
    }

    public function changeStableParams($params, $current_store_id = null)
    {
        $params = !empty($params) ? urldecode($params) : '';
        $params = json_decode($params, true);
        return $params;
    }

    public function divideParam($value, $str)
    {
        if (!empty($str)) {
            preg_match_all('|{{' . $value . ':([^^]*?)}}|u', $str, $matches);
            return $matches[1];
        } else {
            return [];
        }
    }

    /**
     * Get store template list
     *
     * @access public
     * @return object
     * @param  judge 1:theme template; 0:customer template
     */
    public function getTemplateList($judge = 0)
    {
        $segment = new Segment('customer');
        $template = new StoreTemplateCollection();
        $r = new Retailer();
        $r->load($segment->get('customer')['id'], 'customer_id');
        if ($judge == 0) {
            $template->storeTemplateList($r['store_id']);
        } else {
            $template->storeTemplateList(0);
        }
        return $template;
    }

    public function getProductDetailPageID($template_id)
    {
        $id = 0;
        $store_id = $this->judge_store_id();
        $template = new StoreTemplateCollection();
        $template->storeCustomizeTemplate($store_id, $template_id, 2);
        if (count($template)) {
            $id = $template[0]['id'];
        } else {
            $template = new StoreTemplate();
            $data = ['parent_id' => $template_id, 'store_id' => $store_id, 'page_type' => 2];
            $template->setData($data);
            $template->save();
            $id = $template->getId();
        }
        return $id;
    }

    public function getStorePicInfo($code, $current_store_id = null, $current_template_id = null, $part_id = null)
    {
        $Scollection = new StorePicInfoCollection();
        $store_id = $this->judge_store_id($current_store_id);
        $filters = ['resource_category_code' => $code, 'store_decoration_picinfo.store_id' => $store_id];
        if (!empty($current_template_id)) {
            $filters['template_id'] = $current_template_id;
        } else {
            $filters['template_id'] = null;
        }
        if (!empty($part_id)) {
            $filters['part_id'] = $part_id;
        } else {
            $filters['part_id'] = null;
        }
        $Scollection->where($filters)
                ->join('resource', 'store_decoration_picinfo.resource_id = resource.id', ['real_name'], 'left')->order(['resource.created_at' => 'DESC']);
        return $Scollection;
    }

    public function getCustomizeInfo($template_id, $page_type, $current_store_id = null)
    {
        $store_id = $this->judge_store_id($current_store_id);
        $template = new StoreTemplateCollection();
        $template->storeCustomizeTemplate($store_id, $template_id, $page_type);
        $r = new Retailer();
        $r->load($store_id, 'store_id');
        $url = $this->getBaseUrl() . $r->getStoreUrl();
        foreach ($template as $key => $value) {
            $template[$key]['url'] = $url . '?key=' . urlencode(base64_encode($value['id']));
        }
        return $template;
    }

    public function getStoreBanner($current_store_id = null, $current_retailer = null)
    {
        $store_id = $this->judge_store_id($current_store_id);
        if (empty($current_retailer)) {
            $segment = new Segment('customer');
            $customer_id = $segment->get('customer')['id'];
        } else {
            $customer_id = $current_retailer['customer_id'];
        }
        $retailer = new RetailerCollection();
        $retailer->join('retailer_manager', 'retailer_manager.retailer_id=retailer.id', [], 'left')
                ->where(['retailer_manager.customer_id' => $customer_id, 'retailer.store_id' => $store_id])
                ->join('resource', 'retailer.banner = resource.id', ['real_name'], 'left')->order(['resource.created_at' => 'DESC']);
        return empty($retailer) ? [] : $retailer[0];
    }

    public function getStoreCategories($current_store_id = null)
    {
        $collection = new categoryCollection();
        $categories = [];
        if (!empty($current_store_id)) {
            $collection->where(['store_id' => $current_store_id]);
            foreach ($collection as $category) {
                $categories[] = $category;
            }
        }
        return $categories;
    }

    public function getStoreCategoriesTree($current_store_id = null)
    {
        $resource = new categoryResource();
        $collection = $resource->getSourceArrayTree($current_store_id);
        return $collection;
    }

    public function getSearchProducts()
    {
        $condition = $this->getQuery();
        $products = new SalesProducts();
        $productsData = $products->getRetailerSalesProducts($condition);
        $content = '';
        foreach ($productsData as $key => $value) {
            $product = new \Redseanet\Catalog\Model\Product();
            $product->load($value['id']);
            $urls = $product->getUrl();
            $thumbnail = $products->getProduct($value['id'])->getThumbnail();
            if (strpos($thumbnail, 'http') === false) {
                $picURL = $this->getResourceUrl('image/' . $thumbnail);
            } else {
                $picURL = $thumbnail;
            }
            $content .= '<li class="col-md-3 col-6" >
                            <div>
                                <a href="' . $urls . '" target=_blank ><img class="pic" src="' . $picURL . '" width="100%" /></a>
                                <p class="price"><span class="actural">' . $products->getCurrency()->convert($value['price'], true) . ' </span><span class="discount">' . $products->getCurrency()->convert($value['price'], true) . '</span></p>
                                <h6 class="product-name"><a href="' . $urls . '" target=_blank >' . $value['name'] . '</a></h6>
                                <p class="paid-count"></p>
                            </div>
             			</li>';
        }
        return $content;
    }

    /*
     * template content
     *
     *
     */

    public function template_logo_top($params = '', $current_store_id = null, $current_retailer = null)
    {
        if (!empty($params)) {
            $height = $params['heightSet'] . 'px';
        } else {
            $width = '1024px';
            $height = '200px';
        }
        $retailer = $this->getStoreBanner($current_store_id, $current_retailer);
        if (!empty($retailer['real_name'])) {
            $content = $this->getBaseUrl('/pub/resource/image/' . $retailer['real_name']);
        } else {
            $content = $this->getBaseUrl('/pub/theme/default/frontend/dragResource/images/text1.jpg');
        }
        return $content;
    }

    public function template_menu($params = '', $current_store_id = null)
    {
        $store_id = $this->judge_store_id($current_store_id);
        $r = new Retailer();
        $r->load($store_id, 'store_id');
        $url = $this->getBaseUrl() . $r->getStoreUrl();
        $result = $this->getStorePicInfo('menu', $current_store_id);
        $content = '<li class="menu" ><a  href="' . $url . '">首 页</a></li>';
        foreach ($result as $key => $value) {
            if (!empty($value['url']) && trim($value['url']) != '') {
                $content .= '<li class="menu" ><a  href="' . $value['url'] . '">' . $value['pic_title'] . '</a></li>';
            } else {
                $content .= '<li class="menu" ><a  href="javascript:void(0)">' . $value['pic_title'] . '</a></li>';
            }
        }
        return $content;
    }

    public function template_paragraph($params = '', $current_store_id = null)
    {
        $content = '<p> <br><br>可以在此模块中通过编辑器自由输入文字以及编排格式<br><br> </p>';
        return $content;
    }

    public function template_long_search($params = '', $current_store_id = null)
    {
        $content = '<form target=_blank action="' . $this->getBaseUrl('/retailer/store/viewSearch') . '"><label class="search-label">本店搜索</label>
                 <input class="keyword" type="text" name="name" value="" />&nbsp;&nbsp;
                <div style="display:flex;">
                <input class="price-from" type="text" name="price_from" value="" />
                &nbsp;~&nbsp;
                <input class="price-to" type="text" name="price_to" value="" />
                </div>
                <button class="search-button">搜索</button>
                </form>';
        return $content;
    }

    public function template_short_search($params = '', $current_store_id = null)
    {
        $content = '<div class="title"><h6>本店搜索</h6></div>
                <div class="search-table">
                    <form target=_blank action="' . $this->getBaseUrl('/retailer/store/viewSearch') . '">
                    <table>
                        <tr>
                            <td><input class="keyword" type="text" name="name" value="" placeholder="关键字" /></td>
                        </tr>
                        <tr>
                            <td><input class="price-from" type="text" name="price_from" value="" />&nbsp;&nbsp;<input class="price-to" type="text" name="price_to" value="" />
                </td>
                        </tr>
                        <tr>
                            <td><button class="search-button">搜索</button></td>
                        </tr>
                        <tr>
                            <td>热门:<a href="">小刀</a>&nbsp;<a href="">手机</a></td>
                        </tr>
                    </table>
                    </form>
                </div>';
        return $content;
    }

    public function template_product_class($params = '', $current_store_id = null)
    {
        $collection = new categoryCollection();
        $content = '<ul class="category_list">
                        <li><a href="">所有产品</a></li>';
        if (!empty($current_store_id)) {
            $collection->where(['store_id' => $current_store_id]);
            foreach ($collection as $category) {
                $content .= '<li class="dropdown">
                            <span>' . $category['default_name'] . '</span>
                        </li>';
            }
        }
        $content .= '</ul>';
        return $content;
    }

    public function template_sales_amount($params = '', $current_store_id = null)
    {
        if (!empty($params)) {
            $params = urldecode($params);
            $params = json_decode($params, true);
        }
        $select_row = empty($params) ? 3 : $params['select_row'];
        $condition['limit'] = $select_row;
        $products = new SalesProducts();
        $productsData = $products->getRetailerSalesProducts($condition, $current_store_id);
        $content = '<ul class="sales-amount">';
        foreach ($productsData as $key => $value) {
            $product = new \Redseanet\Catalog\Model\Product();
            $product->load($value['id']);
            $urls = $product->getUrl();
            $thumbnail = $products->getProduct($value['id'])->getThumbnail();
            if (strpos($thumbnail, 'http') === false) {
                $picURL = $this->getResourceUrl('image/' . $thumbnail);
            } else {
                $picURL = $thumbnail;
            }
            $content .= '<li>
                            <div class="left" >
                                <a href="' . $urls . '" target=_blank ><img class="pic" src="' . $picURL . '"  /></a>
                                </div>
                                <div class="right">
                                        <h6 class="product-name"><a href="' . $urls . '" target=_blank >' . $value['name'] . '</a></h6>
                                        <p class="price"><span class="actural">' . $products->getCurrency()->convert($value['price'], true) . '</span></p>                                      
                            </div>
                </li>';
        }
        $content .= '</ul>';
        return $content;
    }

    public function template_hot_product($params = '', $current_store_id = null)
    {
        if (!empty($params)) {
            $params = urldecode($params);
            $params = json_decode($params, true);
        }
        $hot_text = empty($params) ? '' : $params['hot_text'];
        $price_from = empty($params) ? '' : $params['price_from'];
        $price_to = empty($params) ? '' : $params['price_to'];
        $select_row = empty($params) ? 1 : $params['select_row'];
        $select_column = empty($params) ? 4 : (int) $params['select_column'];

        $product_ids = empty($params['product_ids']) ? '' : $params['product_ids'];
        $select_col_md = 'col-md-3 col-6';
        $pic_height = '225px';
        switch ($select_column) {
            case 3:
                $select_col_md = 'col-md-4 col-6';
                $pic_height = '313px';
                break;
            case 2:
                $select_col_md = 'col-md-6 col-6';
                $pic_height = '490px';
                break;
            case 1:
                $select_col_md = 'col-md-12 col-6';
                $pic_height = '';
                break;
            default:
                $select_col_md = 'col-md-3 col-6';
                $pic_height = '225px';
                break;
        }

        $condition['name'] = $hot_text;
        $condition['limit'] = $select_column * $select_row;
        $condition['price_from'] = $price_from;
        $condition['price_to'] = $price_to;
        $condition['product_ids'] = $product_ids;
        $products = new SalesProducts();
        $productsData = $products->getRetailerSalesProducts($condition, $current_store_id);

        $content = '<ul>';
        foreach ($productsData as $key => $value) {
            $product = new \Redseanet\Catalog\Model\Product();
            $product->load($value['id']);
            $urls = $product->getUrl();
            $thumbnail = $products->getProduct($value['id'])->getThumbnail();
            if (strpos($thumbnail, 'http') === false) {
                $picURL = $this->getResourceUrl('image/' . $thumbnail);
            } else {
                $picURL = $thumbnail;
            }

            $content .= '<li class="' . $select_col_md . '">
                            <div>
                                <a href="' . $urls . '" target=_blank ><img class="pic rounded"  src="' . $picURL . '"  /></a>
                                <p class="price"><span class="actural">' . $products->getCurrency()->convert($value['price'], true) . ' </span><span class="discount">' . $products->getCurrency()->convert($value['price'], true) . '</span></p>
                                <h6 class="product-name"><a href="' . $urls . '" target=_blank >' . $value['name'] . '</a></h6>
                                <p class="paid-count"></p>
                            </div>
                        </li>';
        }
        $content .= '</ul>';
        return $content;
    }

    public function func_getProductInfo($condition, $current_store_id = null)
    {
        $products = new SalesProducts();
        $productsData = $products->getRetailerSalesProducts($condition, $current_store_id);
        $productsDataCount = $products->getRetailerSalesProductsCount($condition, $current_store_id);
        foreach ($productsData as $key => $value) {
            $product = new \Redseanet\Catalog\Model\Product();
            $product->load($value['id']);
            $urls = $product->getUrl();
            $thumbnail = $products->getProduct($value['id'])->getThumbnail();
            if (strpos($thumbnail, 'http') === false) {
                $picURL = $this->getResourceUrl('image/' . $thumbnail);
            } else {
                $picURL = $thumbnail;
            }
            $productsData[$key]['productURL'] = $urls;
            $productsData[$key]['picURL'] = $picURL;
            $productsData[$key]['acturalPrice'] = $products->getCurrency()->convert($value['price'], true);
        }
        return ['data' => $productsData, 'count' => $productsDataCount];
    }

    public function template_store_recommend($params = '', $current_store_id = null)
    {
        if (!empty($params)) {
            $params = urldecode($params);
            $params = json_decode($params, true);
        }
        $hot_text = empty($params) ? '' : $params['hot_text'];
        $price_from = empty($params) ? '' : $params['price_from'];
        $price_to = empty($params) ? '' : $params['price_to'];
        $product_ids = empty($params['product_ids']) ? '' : $params['product_ids'];
        $condition['name'] = $hot_text;
        $condition['limit'] = 9;
        $condition['price_from'] = $price_from;
        $condition['price_to'] = $price_to;
        $condition['product_ids'] = $product_ids;
        $result = $this->func_getProductInfo($condition, $current_store_id);
        $products = $result['data'];
        $content = '';
        for ($i = 0; $i < 3; $i++) {
            $content .= '<div class="col-md-4 col-12">';

            if ($i == 1) {
                if (!empty($products[0])) {
                    $content .= '<div class="prompt-big">
                                <a href="' . $products[0]['productURL'] . '" target=_blank ><img class="pic" src="' . $products[0]['picURL'] . '"  /></a>
                                <p class="price"><span class="actural">' . $products[0]['acturalPrice'] . ' </span><span class="discount">' . $products[0]['acturalPrice'] . '</span></p>
                                <h6 class="product-name"><a href="' . $products[0]['productURL'] . '" target=_blank >' . $products[0]['name'] . '</a></h6>
                            </div>';
                } else {
                    $content .= '<div class="prompt-big"></div>';
                }
            } else {
                for ($j = 0; $j < 4; $j++) {
                    if ($i == 0) {
                        $index = $j + 1;
                    }
                    if ($i == 2) {
                        $index = $j + 5;
                    }
                    if (!empty($products[$index])) {
                        $content .= '<div class="col-md-6 col-6">
                        <div class="prompt-small"><a href="' . $products[$index]['productURL'] . '" target=_blank ><img class="pic" src="' . $products[$index]['picURL'] . '"  /></a></div>
                    </div>';
                    } else {
                        $content .= '<div class="col-md-6 col-6"></div>';
                    }
                }
            }

            $content .= '</div>';
        }

        return $content;
    }

    public function template_product_recommend($params = '', $current_store_id = null)
    {
        if (!empty($params)) {
            $params = urldecode($params);
            $params = json_decode($params, true);
        }
        $product_ids = empty($params['product_ids']) ? '' : $params['product_ids'];
        $hot_text = empty($params) ? '' : $params['hot_text'];
        $price_from = empty($params) ? '' : $params['price_from'];
        $price_to = empty($params) ? '' : $params['price_to'];
        $select_row = empty($params) ? 1 : $params['select_row'];
        $select_column = empty($params) ? 4 : (int) $params['select_column'];
        $select_col_md = 'col-md-3';
        $pic_height = '225px';
        switch ($select_column) {
            case 3:
                $select_col_md = 'col-md-4 col-6';
                $pic_height = '313px';
                break;
            case 2:
                $select_col_md = 'col-md-6 col-6';
                $pic_height = '490px';
                break;
            case 1:
                $select_col_md = 'col-md-12';
                $pic_height = '';
                break;
            default:
                $select_col_md = 'col-md-3 col-6';
                $pic_height = '225px';
                break;
        }
        $condition['name'] = $hot_text;
        $condition['limit'] = $select_column * $select_row;
        $condition['price_from'] = $price_from;
        $condition['price_to'] = $price_to;
        $condition['product_ids'] = $product_ids;
        //$condition['recomend_status'] = "1";
        $products = new SalesProducts();
        $productsData = $products->getRetailerSalesProducts($condition, $current_store_id);
        $content = '<ul class="product_recommend_ul">';
        foreach ($productsData as $key => $value) {
            $product = new \Redseanet\Catalog\Model\Product();
            $product->load($value['id']);
            $urls = $product->getUrl();
            $thumbnail = $products->getProduct($value['id'])->getThumbnail();
            if (strpos($thumbnail, 'http') === false) {
                $picURL = $this->getResourceUrl('image/' . $thumbnail);
            } else {
                $picURL = $thumbnail;
            }
            $content .= '<li class="' . $select_col_md . '">
                            <div>
                                <a href="' . $urls . '" target=_blank ><img class="pic" src="' . $picURL . '"  /></a>
                                <p class="price"><span class="actural" style="width:80%" >' . $products->getCurrency()->format($value['price']) . ' </span></p>
                                <h6 class="product-name"><a href="' . $urls . '" target=_blank >' . $value['name'] . '</a></h6>
                       
                            </div>
                        </li>';
        }
        $content .= '</ul>';
        return $content;
    }

    public function template_pic_carousel($params = '', $current_store_id = null)
    {
        if (!empty($params)) {
            $params = urldecode($params);
            $params = json_decode($params, true);
        }
        $current_template_id = empty($params) ? '' : $params['current_template_id'];
        $part_id = empty($params) ? '' : $params['part_id'];
        $content = '<div class="carousel_wrap"><ul class="hiSlider hiSlider3">';
        $result = $this->getStorePicInfo('store_carousel', $current_store_id, $current_template_id, $part_id);
        foreach ($result as $key => $value) {
            if (!empty($value['url']) && trim($value['url']) != '') {
                $content .= '<li class="hiSlider-item"><a href="' . $value['url'] . '" target=_blank ><img src="' . $this->getBaseUrl('/pub/resource/image/' . $value['real_name']) . '" alt="' . $value['pic_title'] . '"></a></li>';
            } else {
                $content .= '<li class="hiSlider-item"><img src="' . $this->getBaseUrl('/pub/resource/image/' . $value['real_name']) . '" alt="' . $value['pic_title'] . '"></li>';
            }
        }
        $content .= '</ul></div>';
        return $content;
    }
}
