<?php

namespace Redseanet\Retailer\ViewModel;

class Shipping extends AbstractViewModel
{
    protected $methods = null;

    public function getMethods()
    {
        if (is_null($this->methods)) {
            $this->methods = $this->getConfig()['system']['shipping']['children'];
        }
        return $this->methods;
    }

    public function getInputBoxes($code)
    {
        foreach ($this->methods[$code]['children'] as $key => $item) {
            if ($key === 'model') {
                yield '';
            } else {
                $class = empty($item['view_model']) ? '\\Redseanet\\Lib\\ViewModel\\Template' : $item['view_model'];
                if (in_array($item['type'], ['select', 'checkbox', 'radio', 'multiselect']) && !isset($item['options']) && isset($item['source'])) {
                    $item['options'] = (new $item['source']())->getSourceArray();
                }
                $item['value'] = $this->getConfig()['s' . $this->getRetailer()['store_id'] . '/shipping/' . $code . '/' . $key];
                $box = new $class();
                $box->setVariables([
                    'key' => $code . '/' . $key,
                    'item' => $item,
                    'parent' => $this
                ]);
                yield $box->setTemplate('page/renderer/' . $item['type'], false);
            }
        }
    }
}
