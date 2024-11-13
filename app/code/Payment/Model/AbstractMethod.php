<?php

namespace Redseanet\Payment\Model;

abstract class AbstractMethod
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\Url;

    /**
     * @param array $data
     * @return bool|string
     */
    public function available($data = [])
    {
        $config = $this->getContainer()->get('config');
        $allowCurrency = true;
        if (empty($config['payment/' . static::METHOD_CODE . '/currency'])) {
            $allowCurrency = true;
        } elseif (is_array($config['payment/' . static::METHOD_CODE . '/currency'])) {
            $allowCurrency = in_array($this->getContainer()->get('currency')['code'], $config['payment/' . static::METHOD_CODE . '/currency']);
        } elseif (is_string($config['payment/' . static::METHOD_CODE . '/currency'])) {
            $allowCurrency = in_array($this->getContainer()->get('currency')['code'], explode(',', $config['payment/' . static::METHOD_CODE . '/currency']));
        }
        return $config['payment/' . static::METHOD_CODE . '/enable'] &&
                ($config['payment/' . static::METHOD_CODE . '/max_total'] === '' ||
                $config['payment/' . static::METHOD_CODE . '/max_total'] >= $data['total']) &&
                $config['payment/' . static::METHOD_CODE . '/min_total'] <= $data['total'] && $allowCurrency;
    }

    /**
     * @param array $orders
     * @return string
     */
    public function preparePayment($orders, $data = [])
    {
        return $this->getBaseUrl('checkout/success/?increment_id=' . (isset($data['increment_id']) ? $data['increment_id'] : ''));
    }

    /**
     * @return int
     */
    public function getNewOrderStatus()
    {
        return $this->getContainer()->get('config')['payment/' . static::METHOD_CODE . '/new_status'];
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        $description = $this->getContainer()->get('config')['payment/' . static::METHOD_CODE . '/description'];
        return $description ? nl2br($description) : '';
    }

    public function getLabel()
    {
        return $this->getContainer()->get('config')['payment/' . static::METHOD_CODE . '/label'];
    }

    public function saveData($cart, $data)
    {
        return $this;
    }

    public function syncNotice($data)
    {
        return '';
    }

    public function asyncNotice(array $data)
    {
        return '';
    }
}
