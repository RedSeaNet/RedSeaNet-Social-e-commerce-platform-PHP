<?php

namespace Redseanet\Payment\Model;

use Exception;
use Redseanet\Customer\Model\CreditCard;
use Redseanet\Lib\Session\Segment;
use Redseanet\Payment\ViewModel\SavedCc as ViewModel;

class SavedCc extends AbstractMethod
{
    public const METHOD_CODE = 'saved_cc';

    public static $REGEXP = [
        'SO' => [
            '#^(6334[5-9]([0-9]{11}|[0-9]{13,14}))|(6767([0-9]{12}|[0-9]{14,15}))$#',
            '#^([0-9]{3}|[0-9]{4})?$#'
        ],
        'VI' => [
            '#^4[0-9]{12}([0-9]{3})?$#',
            '#^[0-9]{3}$#'
        ],
        'MC' => [
            '#^5[1-5][0-9]{14}$#',
            '#^[0-9]{3}$#'
        ],
        'AE' => [
            '#^3[47][0-9]{13}$#',
            '#^[0-9]{4}$#'
        ],
        'DI' => [
            '#^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}|5[0-9]{14}))$#',
            '#^[0-9]{3}$#'
        ],
        'JCB' => [
            '#^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}|5[0-9]{14}))$#',
            '#^[0-9]{3,4}$#'
        ],
        'DICL' => [
            '#^(30[0-5][0-9]{13}|3095[0-9]{12}|35(2[8-9][0-9]{12}|[3-8][0-9]{13})|36[0-9]{12}|3[8-9][0-9]{14}|6011(0[0-9]{11}|[2-4][0-9]{11}|74[0-9]{10}|7[7-9][0-9]{10}|8[6-9][0-9]{10}|9[0-9]{11})|62(2(12[6-9][0-9]{10}|1[3-9][0-9]{11}|[2-8][0-9]{12}|9[0-1][0-9]{11}|92[0-5][0-9]{10})|[4-6][0-9]{13}|8[2-8][0-9]{12})|6(4[4-9][0-9]{13}|5[0-9]{14}))$#',
            '#^[0-9]{3}$#'
        ],
        'SM' => [
            '#(^(5[0678])[0-9]{11,18}$)|(^(6[^05])[0-9]{11,18}$)|(^(601)[^1][0-9]{9,16}$)|(^(6011)[0-9]{9,11}$)|(^(6011)[0-9]{13,16}$)|(^(65)[0-9]{11,13}$)|(^(65)[0-9]{15,18}$)|(^(49030)[2-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49033)[5-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49110)[1-2]([0-9]{10}$|[0-9]{12,13}$))|(^(49117)[4-9]([0-9]{10}$|[0-9]{12,13}$))|(^(49118)[0-2]([0-9]{10}$|[0-9]{12,13}$))|(^(4936)([0-9]{12}$|[0-9]{14,15}$))#',
            '#^([0-9]{3}|[0-9]{4})?$#'
        ]
    ];

    public function available($data = [])
    {
        if (parent::available($data)) {
            if (empty($data)) {
                return true;
            }
            $segment = new Segment('customer');
            if (!empty($data['payment_data']['cc']) && $segment->get('hasLoggedIn')) {
                $card = new CreditCard();
                $card->load($data['payment_data']['cc']);
                if ($card->getId() && $card->offsetGet('customer_id') === $segment->get('customer')['id']) {
                    return true;
                }
            }
            $year = date('Y');
            if (empty($data['payment_data']['type'])) {
                $msg = 'Credit card type is required and cannot be empty.';
            } elseif (!isset(self::$REGEXP[$data['payment_data']['type']])) {
                $msg = 'Invalid credit card type.';
            } elseif (empty($data['payment_data']['number'])) {
                $msg = 'Credit card number is required and cannot be empty.';
            } elseif (empty($data['payment_data']['name'])) {
                $msg = 'Credit card name is required and cannot be empty.';
            } elseif (empty($data['payment_data']['exp_month']) || empty($data['payment_data']['exp_year']) ||
                    $data['payment_data']['exp_month'] < 1 && $data['payment_data']['exp_month'] > 12 ||
                    $data['payment_data']['exp_year'] < $year && $data['payment_data']['exp_month'] > $year + 10) {
                $msg = 'Invalid expiration date.';
            } elseif (empty($data['payment_data']['verification'])) {
                $msg = 'Credit card verification is required and cannot be empty.';
            } elseif (!preg_match(self::$REGEXP[$data['payment_data']['type']][0], $data['payment_data']['number'])) {
                $msg = 'Invalid credit card number.';
            } elseif (!preg_match(self::$REGEXP[$data['payment_data']['type']][1], $data['payment_data']['verification'])) {
                $msg = 'Invalid credit card verification.';
            }
            return $msg ?? true;
        }
        return false;
    }

    public function getDescription()
    {
        return new ViewModel();
    }

    public function saveData($cart, $data)
    {
        $info = $cart->offsetGet('additional');
        $segment = new Segment('customer');
        if (!empty($data['cc'])) {
            $cc = $data['cc'];
        } else {
            try {
                $card = new CreditCard();
                $card->setData($data + [
                    'customer_id' => $segment->get('hasLoggedIn') ? $segment->get('customer')['id'] : null
                ])->save();
                $cc = $card->getId();
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                throw new Exception('Invalid credit card infomation');
            }
        }
        if ($info) {
            $info = json_decode($info, true);
            $info['credit_card'] = $cc;
        } else {
            $info = ['credit_card' => $cc];
        }
        $cart->setData('additional', json_encode($info));
        return $this;
    }
}
