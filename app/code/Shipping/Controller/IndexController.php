<?php

namespace Redseanet\Shipping\Controller;

use Exception;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Sales\Source\ShippingMethod;
use Redseanet\Customer\Model\Address;

class IndexController extends ActionController
{
    public function getShippingAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            if (!isset($data['csrf']) || !$this->validateCsrfKey($data['csrf'])) {
                $result['message'][] = ['message' => $this->translate('The form submitted did not originate from the expected site.'), 'level' => 'danger'];
                $result['error'] = 1;
            } else {
                if (isset($data['store']) && $data['store'] != '') {
                    $result['store'] = $data['store'];
                    if (isset($data['isVirtual']) && $data['isVirtual'] != '') {
                        if ($data['isVirtual']) {
                            $result['methods'] = [];
                        } else {
                            if (isset($data['addressId']) && $data['addressId'] != '') {
                                $address = (new Address())->load($data['addressId']);
                                $total = 0;
                                $newItems = [];
                                if (isset($data['items']) && $data['items'] != '') {
                                    $items = json_decode($data['items'], true);
                                    foreach ($items as $item) {
                                        $item = json_decode($item, true);
                                        if (!$item['free_shipping'] && !$item['is_virtual']) {
                                            $total += $item['base_total'];
                                        }
                                        $newItems[] = $item;
                                    }
                                }
                                $result['methods'] = (new ShippingMethod())->getSourceArray($address, $newItems);
                                $html = '';
                                $currency = $this->getContainer()->get('currency');
                                if (count($result['methods']) > 0) {
                                    $html .= '<select name="shipping_method[' . $data['store'] . ']" id="shipping-method-' . $data['store'] . '">';
                                    $m = 0;
                                    foreach ($result['methods'] as $code => $value) {
                                        $html .= '<option value="' . $code . '"' . ($m == 0 ? ' selected="selected"' : '') . ' data-fee="' . $currency->convert($value['fee']) . '">';
                                        $html .= $this->translate($value['label']);
                                        $html .= '</option>';
                                        $m++;
                                    }
                                    $html .= '</select>';
                                } else {
                                    $html .= '<p>' . $this->translate('Sorry, no shipping methods are available for this order at this time.') . '</p>';
                                }
                                $result['html'] = $html;
                            } else {
                                $result['message'][] = ['message' => $this->translate('The address id can not be null.'), 'level' => 'danger'];
                                $result['error'] = 1;
                            }
                        }
                    } else {
                        $result['message'][] = ['message' => $this->translate('The isVirtual can not be null.'), 'level' => 'danger'];
                        $result['error'] = 1;
                    }
                } else {
                    $result['message'][] = ['message' => $this->translate('The store can not be null.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
            return $result;
        }
        return $this->notFoundAction();
    }
}
