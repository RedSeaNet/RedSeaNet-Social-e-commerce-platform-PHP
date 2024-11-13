<?php

namespace Redseanet\Payment\ViewModel;

use Redseanet\Customer\Model\Collection\CreditCard;
use Redseanet\Lib\Session\Segment;
use Redseanet\Lib\ViewModel\Template;
use Redseanet\Payment\Source\CcType;

class SavedCc extends Template
{
    protected $month;

    public function __construct()
    {
        $this->template = 'payment/savedcc';
        $this->month = [
            $this->translate('January'),
            $this->translate('February'),
            $this->translate('March'),
            $this->translate('April'),
            $this->translate('May'),
            $this->translate('June'),
            $this->translate('July'),
            $this->translate('August'),
            $this->translate('September'),
            $this->translate('October'),
            $this->translate('November'),
            $this->translate('December')
        ];
    }

    public function getCards()
    {
        $segment = new Segment('customer');
        if ($segment->get('hasLoggedIn')) {
            $cards = new CreditCard();
            $cards->where(['customer_id' => $segment->get('customer')['id']]);
            return $cards;
        }
        return [];
    }

    public function getTypes()
    {
        $config = $this->getContainer()->get('config');
        $value = $config['payment/saved_cc/cctype'];
        $labels = (new CcType())->getSourceArray();
        $result = [];
        foreach (is_array($value) ? $value : explode(',', $value) as $key) {
            $result[$key] = $labels[$key];
        }
        return $result;
    }

    public function getYear()
    {
        return date('Y');
    }

    public function getMonth($month)
    {
        return $this->month[$month - 1];
    }
}
