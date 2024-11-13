<?php

namespace Redseanet\Bargain\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\AbstractHandler;
use Redseanet\Bargain\Model\Bargain as Model;
use Redseanet\I18n\Model\Currency;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Model\Language;
use Redseanet\Catalog\Model\Product;
use Redseanet\Catalog\Model\Collection\Product as productCollection;
use Redseanet\Customer\Model\Customer;
use Redseanet\Customer\Model\Collection\Customer as customerCollection;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Model\Order\Item as orderItem;
use Redseanet\Bargain\Model\Collection\Bargain as bargainCollection;
use Redseanet\Bargain\Model\BargainCase;
use Redseanet\Bargain\Model\Collection\BargainCase as bargainCaseCollection;
use Redseanet\Bargain\Model\BargainCaseHelp;
use Redseanet\Bargain\Model\Collection\BargainCaseHelp as bargainCaseHelpCollection;
use Laminas\Db\Sql\Select;
use Laminas\Db\Sql\Where;
use Laminas\Db\Sql\Expression;

class Bargain extends AbstractHandler
{
    use \Redseanet\Lib\Traits\Filter;

    use \Redseanet\Lib\Traits\Url;

    use \Redseanet\Lib\Traits\Translate;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Checkout\Traits\Checkout;

    use \Redseanet\Lib\Traits\Time;

    use \Redseanet\Forum\Traits\Wechat;

    public function bargainList($id, $token, $conditionData = [], $customerId = '', $languageId = 0, $currencyCode = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $resultData = [];
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $bargains = new bargainCollection($languageId);
        $bargains_user_help_total = new Select('bargain_case_help');
        $bargains_user_help_total->columns(['count' => new Expression('count(1)')])->where('bargain_case_help.bargain_id=bargain.id');
        $bargains->columns(['*', 'bargains_user_help_total' => $bargains_user_help_total]);
        $bargains->where('status=1');
        ///echo $bargains->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
        $tmpResultData = [];
        $i = 0;
        foreach ($bargains as $bargain) {
            $price = $currency->convert($bargain['price']);
            $min_price = $currency->convert($bargain['min_price']);
            $thumbnail = [];
            $thumbnailData = $bargain->getThumbnail();
            if (count($thumbnailData) > 0) {
                foreach ($thumbnailData as $thumbnailImage) {
                    $thumbnail[] = $this->getResourceUrl('image/' . $thumbnailImage['real_name']);
                }
            } else {
                $thumbnail[0] = $this->getPubUrl('frontend/images/placeholder.png');
            }
            $images = [];
            $imagesData = $bargain->getImages();
            if (count($imagesData) > 0) {
                foreach ($imagesData as $image) {
                    $images[] = $this->getResourceUrl('image/' . $image['real_name']);
                }
            } else {
                $images[0] = $this->getPubUrl('frontend/images/placeholder.png');
            }
            $tmpResultData[$i] = $bargain->toArray();
            $tmpResultData[$i]['price'] = $price;
            $tmpResultData[$i]['min_price'] = $min_price;
            $tmpResultData[$i]['thumbnail'] = $thumbnail;
            $tmpResultData[$i]['images'] = $images;
            $tmpResultData[$i]['content'] = $tmpResultData[$i]['content'][$languageId];
            $tmpResultData[$i]['description'] = $tmpResultData[$i]['description'][$languageId];
            $tmpResultData[$i]['name'] = $tmpResultData[$i]['name'][$languageId];
            $i++;
        }
        $resultData['bargains'] = $tmpResultData;
        $resultData['currency'] = $currency->toArray();
        $this->responseData = ['statusCode' => '200', 'data' => $resultData, 'message' => 'get bargain data successfully'];
        return $this->responseData;
    }

    public function getBargain($id, $token, $bargainId, $customerId = '', $bargainCaseId = '', $languageId = 0, $currencyCode = '')
    {
        $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'get bargain data successfully'];
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $resultData = [];
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $collection = new bargainCollection();
        //$collection->columns(["store_id"]);
        $collection->join('product_entity', 'product_entity.id=bargain.product_id', ['store_id'], 'left')
                ->join('bargain_language', 'bargain_language.bargain_id=bargain.id', ['name', 'description', 'content'], 'left')
                ->where(['bargain.status' => 1, 'bargain.id' => $bargainId, 'bargain_language.language_id' => $languageId])
                ->order('bargain.created_at DESC');
        if (count($collection) > 0) {
            if (date('Y-m-d H:i:s') > $collection[0]->stop_time) {
                $this->responseData['statusCode'] = '400';
                $this->responseData['message'] = 'The bargain have end';
            } else {
                if ($collection[0]->start_time > date('Y-m-d H:i:s')) {
                    $this->responseData['statusCode'] = '400';
                    $this->responseData['message'] = 'Please wait, The bargain do not start at ' . $collection[0]->start_time;
                } else {
                    $product = new Product();
                    $product->load($collection[0]['product_id']);
                    $thumbnail = [];
                    $thumbnailData = $collection[0]->getThumbnail();
                    if (count($thumbnailData) > 0) {
                        foreach ($thumbnailData as $thumbnailImage) {
                            $thumbnail[] = $this->getResourceUrl('image/' . $thumbnailImage['real_name']);
                        }
                    } else {
                        $thumbnail[0] = $this->getPubUrl('frontend/images/placeholder.png');
                    }
                    $images = [];
                    $imagesData = $collection[0]->getImages();
                    if (count($imagesData) > 0) {
                        foreach ($imagesData as $image) {
                            $images[] = $this->getResourceUrl('image/' . $image['real_name']);
                        }
                    } else {
                        $images[0] = $this->getPubUrl('frontend/images/placeholder.png');
                    }
                    $resultData = $collection[0]->toArray();
                    $resultData['thumbnail'] = $thumbnail;
                    $resultData['images'] = $images;
                    $resultData['name'] = $resultData['name'][$languageId];
                    $resultData['description'] = $resultData['description'][$languageId];
                    $resultData['content'] = $resultData['content'][$languageId];
                    $resultData['price'] = $currency->convert($resultData['price']);
                    $resultData['min_price'] = $currency->convert($resultData['min_price']);
                    $stop_time_diff_time = abs(strtotime($resultData['stop_time']) - time());
                    $resultData['time_counter'] = $this->secondTime2String($stop_time_diff_time);
                    $resultData['help_count'] = 0;
                    $resultData['already_price'] = 0;
                    $resultData['userBargainStatusHelp'] = 0;
                    $resultData['options'] = json_decode($resultData['options'], true);
                    $resultData['optionsname'] = '';
                    if (count($resultData['options']) > 0) {
                        $optionName = [];
                        foreach ($resultData['options'] as $option => $value) {
                            $tmpOption = $product->getOption($option, null, $languageId);
                            $tmpValueName = $product->getOption($option, $value, $languageId);
                            $optionName[] = $tmpOption['title'] . ':' . $tmpValueName;
                        }
                        $resultData['optionsname'] = implode(',', $optionName);
                    }
                    $bargainCaseCollection = new bargainCaseCollection();
                    if ($bargainCaseId != '') {
                        $bargainCaseCollection->where(['id' => $bargainCaseId, 'bargain_id' => $bargainId, 'status' => 1]);
                    } elseif ($customerId != '') {
                        $bargainCaseCollection->where(['customer_id' => $customerId, 'bargain_id' => $bargainId, 'status' => 1]);
                    }
                    if (count($bargainCaseCollection) > 0) {
                        $resultData['created_bargain'] = 1;
                        $bargainCase = $bargainCaseCollection[0]->toArray();
                        $bargainCase['current_price'] = $bargainCase['bargain_price'] - $bargainCase['price'];
                        $already_price = $bargainCase['price'];
                        $coverPrice = (float) bcsub((string) $bargainCase['bargain_price'], (string) $bargainCase['bargain_price_min'], 2); //用户可以砍掉的金额
                        $price = (float) bcsub((string) $coverPrice, (string) $already_price, 2);
                        $price_percent = 0;
                        if ($already_price > 0) {
                            $price_percent = (int) bcmul((string) bcdiv((string) $already_price, (string) $coverPrice, 2), '100', 0);
                        }
                        if (!empty($bargainCase['mini_program_qr'])) {
                            $bargainCase['mini_program_qr'] = $this->getUploadedUrl('pub/upload/bargain/' . $bargainCase['mini_program_qr']);
                        }
                        $the_user_help_count = 0;
                        $resultData['already_price'] = $already_price;
                        $resultData['price_percent'] = $price_percent;
                        $resultData['price'] = $price;
                        $bargainCaseHelpList = new customerCollection();
                        $bargainCaseHelpList->columns(['id', 'username', 'avatar']);
                        $bargainCaseHelpList->join('bargain_case_help', 'bargain_case_help.customer_id=main_table.id', ['bargain_case_id', 'price', 'type', 'created_at', 'customer_id'], 'left')
                                ->where(['bargain_case_help.bargain_case_id' => $bargainCase['id']])
                                ->order('bargain_case_help.created_at DESC');
                        $bargainHelpArray = [];
                        $bargainCaseHelpList->load(true, true);
                        for ($b = 0; $b < count($bargainCaseHelpList); $b++) {
                            $bargainHelpArray[$b] = $bargainCaseHelpList[$b];
                            if (isset($bargainCaseHelpList[$b]['avatar']) && $bargainCaseHelpList[$b]['avatar'] != '') {
                                $avatar = $this->getBaseUrl('pub/upload/customer/avatar/' . $bargainCaseHelpList[$b]['avatar']);
                            } else {
                                $avatar = $this->getPubUrl('frontend/images/avatar-holderplace.jpg');
                            }
                            $bargainHelpArray[$b]['avatar'] = $avatar;

                            if ($customerId == $bargainHelpArray[$b]['id']) {
                                $the_user_help_count++;
                            }
                        }
                        $resultData['help_count'] = count($bargainHelpArray);
                        $bargainCase['helps'] = $bargainHelpArray;
                        $resultData['bargain_case'] = $bargainCase;
                        $resultData['userBargainStatusHelp'] = (($the_user_help_count >= $resultData['people_num'] || $price == 0) ? 0 : 1);
                    } else {
                        $resultData['created_bargain'] = 0;
                        $resultData['bargain_case'] = [];
                    }
                    $resultData['currency'] = $currency->toArray();
                    $this->responseData['data'] = $resultData;
                    $this->responseData['message'] = 'get the bargain data successfully';
                }
            }
        } else {
            $this->responseData['statusCode'] = '400';
            $this->responseData['message'] = 'do not have the bargain';
        }
        return $this->responseData;
    }

    public function startBargain($id, $token, $bargainId, $customerId = '', $languageId = 0, $currencyCode = '')
    {
        $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'get bargain data successfully'];
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $collection = new bargainCollection();
        $collection->where(['bargain.status' => 1, 'bargain.id' => $bargainId])->where->greaterThanOrEqualTo('stop_time', date('Y-m-d H:i:s'))->lessThan('start_time', date('Y-m-d H:i:s'));
        //$collection->load(true, true);
        if (count($collection) > 0) {
            $bargainDetail = $collection[0];
            $bargainCases = new bargainCaseCollection();
            $bargainCases->where(['customer_id' => $customerId, 'bargain_id' => $bargainId]);

            if (count($bargainCases) > $bargainDetail['num']) {
                $this->responseData['statusCode'] = '400';
                $this->responseData['message'] = 'You canot start new bargain, it only can start ';
            } else {
                $bargain_user_count_check = true;
                for ($bc = 0; $bc < count($bargainCases); $bc++) {
                    if ($bargainCases[$bc]['status'] == 1) {
                        $bargain_user_count_check = false;
                    }
                }
                if (!$bargain_user_count_check) {
                    $this->responseData['statusCode'] = '400';
                    $this->responseData['message'] = 'You have an active bargain';
                } else {
                    $newBargainCase = [];
                    $newBargainCase['customer_id'] = $customerId;
                    $newBargainCase['bargain_id'] = $bargainId;
                    $newBargainCase['bargain_price_min'] = $bargainDetail['min_price'];
                    $newBargainCase['bargain_price'] = $bargainDetail['price'];
                    $newBargainCase['price'] = 0;
                    $newBargainCase['status'] = 1;
                    $newBargainCase['mini_program_qr'] = '';
                    $newBargainCase['web_qr'] = '';
                    $newBargainCase['mp_qr'] = '';
                    $bargainCase = new BargainCase($newBargainCase);
                    $bargainCase->save();
                    $bargainCaseId = $bargainCase->getId();

                    $qr_res = $this->getQrCode(['scene' => '1-' . $bargainId . '-' . $bargainCaseId, 'page' => 'pages/index/active-router/active-router', 'width' => 280]);
                    //var_dump($qr_res);
                    Bootstrap::getContainer()->get('log')->logException(new \Exception(json_encode($qr_res)));
                    if (isset($qr_res['qr']) && $qr_res['qr'] != '') {
                        $image_name = 'bargain-' . $bargainId . '-' . $bargainCaseId . '-' . time() . '.png';
                        if (!is_dir(BP . 'pub/upload/bargain/')) {
                            mkdir(BP . 'pub/upload/bargain/', 0777, true);
                        }
                        $path = BP . 'pub/upload/bargain/' . $image_name;
                        file_put_contents($path, $qr_res['qr']);
                        $bargainCase->setData('mini_program_qr', $image_name);
                        $bargainCase->save();
                    }
                    $this->responseData['data'] = ['bargain_case_id' => $bargainCaseId];
                    $this->responseData['statusCode'] = '200';
                    $this->responseData['message'] = 'You have started new bargain successfully';
                }
            }
        } else {
            $this->responseData['statusCode'] = '400';
            $this->responseData['message'] = 'The bargain have end';
        }
        return $this->responseData;
    }

    public function helpBargain($id, $token, $bargainId, $customerId = '', $bargainCaseId = '', $languageId = 0, $currencyCode = '')
    {
        $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'get bargain data successfully'];
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');

        $collection = new bargainCollection();
        $collection->where(['bargain.status' => 1, 'bargain.id' => $bargainId])->where->greaterThanOrEqualTo('stop_time', date('Y-m-d H:i:s'))->lessThan('start_time', date('Y-m-d H:i:s'));
        //$collection->load(true, true);
        if (count($collection) > 0) {
            $bargainDetail = $collection[0];
            $bargainCases = new bargainCaseCollection();
            $bargainCases->where(['bargain_id' => $bargainId, 'id' => $bargainCaseId]);
            if (count($bargainCases) > 0) {
                $bargainCase = $bargainCases[0];
                $alreadyPrice = $bargainCase['price']; //TODO 用户已经砍掉的价格
                $hadHelpBagainList = new bargainCaseHelpCollection();
                $hadHelpBagainList->where(['bargain_id' => $bargainId, 'customer_id' => $customerId]);
                if (count($hadHelpBagainList) > $bargainDetail['bargain_num']) {
                    $this->responseData['statusCode'] = '400';
                    $this->responseData['message'] = 'You canot help the friend bargain, You have help :number friends';
                } else {
                    $hadHelpBagainCaseList = new bargainCaseHelpCollection();
                    $hadHelpBagainCaseList->where(['bargain_id' => $bargainId, 'customer_id' => $customerId, 'bargain_case_id' => $bargainCaseId]);
                    if (count($hadHelpBagainCaseList) > 0) {
                        $this->responseData['statusCode'] = '400';
                        $this->responseData['message'] = 'You have help the friend in the bargain';
                    } else {
                        $coverPrice = bcsub((string) $bargainDetail['price'], (string) $bargainDetail['min_price'], 2);
                        $surplusPrice = bcsub((string) $coverPrice, (string) $alreadyPrice, 2); //TODO 用户剩余要砍掉的价格
                        if (0.00 === (float) $surplusPrice) {
                            $this->responseData['statusCode'] = '400';
                            $this->responseData['message'] = 'You dont have price to bargain';
                        } else {
                            $newBargainCaseHelp = [];
                            $newBargainCaseHelp['customer_id'] = $customerId;
                            $newBargainCaseHelp['bargain_id'] = $bargainId;
                            $newBargainCaseHelp['bargain_case_id'] = $bargainCaseId;
                            $newBargainCaseHelp['type'] = 0;
                            //这个砍价已经被砍了多少次
                            $hadHelpBargains = new bargainCaseHelpCollection();
                            $hadHelpBargains->where([
                                'bargain_id' => $bargainId,
                                'bargain_case_id' => $bargainCaseId
                            ]);
                            $hadHelpBargains->load(true, true);
                            $help_people_count = count($hadHelpBargains);
                            if (($bargainDetail['people_num'] - $help_people_count) <= 1) {
                                $newBargainCaseHelp['price'] = $surplusPrice;
                            } else {
                                if ($bargainCase['customer_id'] == $customerId) {
                                    $newBargainCaseHelp['price'] = $bargainDetail->randomFloat($surplusPrice, $bargainDetail['people_num'] - $help_people_count, false);
                                    $newBargainCaseHelp['type'] = 1;
                                } else {
                                    $newBargainCaseHelp['price'] = $bargainDetail->randomFloat($surplusPrice, $bargainDetail['people_num'] - $help_people_count, true);
                                }
                            }
                            $bargainHelpObject = new BargainCaseHelp($newBargainCaseHelp);
                            $bargainHelpObject->save();
                            $bargain_case_price = bcadd((string) $alreadyPrice, (string) $newBargainCaseHelp['price'], 2);
                            $bargainCase->setData('price', (float) $bargain_case_price);
                            $bargainCase->save();
                            $this->responseData['statusCode'] = '200';
                            $this->responseData['message'] = 'You  help your friend successfully';
                        }
                    }
                }
            } else {
                $this->responseData['statusCode'] = '400';
                $this->responseData['message'] = 'The bargain case dont exit';
            }
        } else {
            $this->responseData['statusCode'] = '400';
            $this->responseData['message'] = 'The bargain have end';
        }
        return $this->responseData;
    }

    public function bargainOrder($id, $token, $customerId, $bargainId = '', $bargainCaseId = '', $data = [], $languageId = 0, $currencyCode = '')
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        if ($languageId === 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }

        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        if ($currencyCode == '') {
            $currencyCode = $base;
        }
        $currency = new Currency();
        $currency->load($currencyCode, 'code');
        $customer = new Customer();
        $customer->load($customerId);

        try {
            $this->beginTransaction();
            $bargain = new Model();
            $bargain->load($bargainId);

            $bargainCase = new BargainCase();
            $bargainCase->load($bargainCaseId);

            $product = new Product();
            $product->load($bargain['product_id']);
            $baseCurrency = $this->getContainer()->get('config')['i18n/currency/base'];

            $optionNames = [];
            $options = json_decode($bargain['options'], true);
            foreach ($product->getOptions() as $option) {
                $optionNames[] = $option['title'] . ':' . (in_array($option['input'], ['select', 'radio', 'checkbox', 'multiselect']) ?
                        $option->getValue($options[$option->getId()]) : $options[$option->getId()]);
            }
            $bargain['options_name'] = implode(',', $optionNames);
            $items = [];
            $items[] = [
                'product_id' => $bargain['product_id'],
                'product_name' => $bargain['name'][$languageId],
                'options' => $bargain['options'],
                'options_name' => $bargain['options_name'],
                'qty' => $data['qty'],
                'store_id' => $product['store_id'],
                'sku' => $bargain['sku'],
                'is_virtual' => $product['is_virtual'],
                'base_price' => $bargain['min_price'],
                'price' => $currency->convert($bargain['min_price']),
                'base_discount' => 0,
                'discount' => 0,
                'base_tax' => 0,
                'tax' => 0,
                'base_total' => $bargain['min_price'] * $data['qty'],
                'total' => $currency->convert($bargain['min_price'] * $data['qty']),
                'warehouse_id' => $data['warehouse_id'],
                'weight' => 0
            ];
            $billingAddress = $this->validBillingAddress($data);
            $paymentMethod = $this->validPayment(['total' => $bargain['min_price'] * $data['qty']] + $data);
            $base_shipping = 0;
            $shipping_method = $data['shipping_method'][$product['store_id']];
            $customer_note = isset($data['comment']) ? json_encode($data['comment']) : '{}';
            $billing_address_id = '';
            $billing_address = '';
            $shipping_address_id = '';
            $shipping_address = '';
            if ($product['is_virtual']) {
                if ($billingAddress) {
                    $billing_address_id = $data['billing_address_id'];
                    $billing_address = $billingAddress->display(false);
                }
            } else {
                $shippingAddress = $this->validShippingAddress($data);
                $shipping_address_id = $data['shipping_address_id'];
                $shipping_address = isset($shippingAddress) ? $shippingAddress->display(false) : '';
                if ($billingAddress) {
                    $billing_address_id = $data['billing_address_id'];
                    $billing_address = $billingAddress->display(false);
                } else {
                    $billing_address_id = $data['shipping_address_id'];
                    $billing_address = $shippingAddress->display(false);
                }
                $base_shipping = $this->getShippingMethod($shipping_method)->getShippingRate($items);
            }

            $key = $data['warehouse_id'] . '-' . $product['store_id'];
            $orders = [];
            $additional = [];
            $additional['bargain'] = $bargainId;
            $additional['bargain_case'] = $bargainCaseId;
            $discount_detail = ['Bargain' => ['id' => $bargainId, 'bargain_case' => $bargainCaseId, 'detail' => $bargain]];
            $orderArray = [
                'status_id' => $paymentMethod->getNewOrderStatus(),
                'customer_id' => $customer->getId(),
                'language_id' => $languageId,
                'billing_address_id' => $billing_address_id,
                'shipping_address_id' => $shipping_address_id,
                'warehouse_id' => $data['warehouse_id'],
                'base_total_refunded' => 0,
                'store_id' => $product['store_id'],
                'billing_address' => $billing_address,
                'shipping_address' => $shipping_address,
                'total_refunded' => 0,
                'is_virtual' => $product['is_virtual'],
                'free_shipping' => '',
                'base_currency' => $baseCurrency,
                'currency' => $currencyCode,
                'base_subtotal' => $bargain['min_price'] * $data['qty'],
                'shipping_method' => $shipping_method,
                'payment_method' => $data['payment_method'],
                'base_shipping' => $base_shipping,
                'shipping' => $currency->convert($base_shipping),
                'subtotal' => $currency->convert($bargain['min_price'] * $data['qty']),
                'base_discount' => 0,
                'discount' => 0,
                'discount_detail' => json_encode($discount_detail),
                'base_tax' => 0,
                'tax' => 0,
                'base_total' => $bargain['min_price'] * $data['qty'] + $base_shipping,
                'total' => $currency->convert($bargain['min_price'] * $data['qty'] + $base_shipping),
                'base_total_paid' => 0,
                'total_paid' => 0,
                'customer_note' => $customer_note,
                'coupon' => '',
                'additional' => json_encode($additional)
            ];
            $order = new Order($orderArray);
            $orders[$key] = $order->save();
            $orderId = $order->getId();

            foreach ($items as $item) {
                $item = new orderItem($item);
                $item->setData('order_id', $orderId)->setId(null)->save();
            }
            $bargain->setData('stock', $bargain['stock'] - 1);
            $bargain->save();
            $bargainCase->setData('status', 3);
            $bargainCase->save();
            $result['redirect'] = $paymentMethod->preparePayment($orders);
            if (!empty($data['openid']) && $data['payment_method'] === \Redseanet\Payment\Model\WeChatPay::METHOD_CODE) {
                $orders['openid'] = $data['openid'];
            }
            if (isset($orders['openid'])) {
                $result['prepay'] = (new Segment('payment'))->get('wechatpay');
            }
            $this->commit();
            $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => $this->translate('you have bought the bargain successfully.')];
            return $this->responseData;
        } catch (ClickFarming $e) {
            $this->rollback();
            $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('Click farming check failed.')];
            return $this->responseData;
        } catch (OutOfStock $e) {
            $this->rollback();
            $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('The requested quantity for "%s" is not available.', [(new Product())->load($bulkItemCollection[0]['product_id'])['name']])];
            return $this->responseData;
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            $this->rollback();
            $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('An error detected. Please contact us or try again later.')];
            return $this->responseData;
        }

        $this->responseData = ['statusCode' => '400', 'data' => [], 'message' => $this->translate('An error detected. Please contact us or try again later.')];
        return $this->responseData;
    }
}
