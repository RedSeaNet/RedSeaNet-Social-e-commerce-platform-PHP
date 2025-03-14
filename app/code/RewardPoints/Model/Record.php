<?php

namespace Redseanet\RewardPoints\Model;

use Redseanet\Customer\Model\Customer;
use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Lib\Model\AbstractModel;
use Redseanet\Lib\Model\Language;
use Redseanet\Sales\Model\Order;
use PHPMailer\PHPMailer\Exception as EmailException;

class Record extends AbstractModel {

    use \Redseanet\RewardPoints\Traits\Recalc;

    use \Redseanet\Lib\Traits\Translate;

    protected function construct() {
        $this->init('reward_points', 'id', ['id', 'customer_id', 'order_id', 'count', 'comment', 'status']);
    }

    public function getCustomer() {
        if (!empty($this->storage['customer_id'])) {
            $customer = new Customer();
            $customer->load($this->storage['customer_id']);
            if ($customer->getId()) {
                return $customer;
            }
        }
        return [];
    }

    public function getOrder() {
        if (!empty($this->storage['order_id'])) {
            $order = new Order();
            $order->load($this->storage['order_id']);
            if ($order->getId()) {
                return $order;
            }
        }
        return null;
    }

    protected function afterSave() {
        if (!empty($this->storage['status'])) {
            $this->recalc($this->storage['customer_id']);
            $config = $this->getContainer()->get('config');
            try {
                $customer = $this->getCustomer();
                $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
                $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
                if ($customer) {
                    $collection = new TemplateCollection();
                    $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                            ->where([
                                'code' => $this->storage['comment'] === 'Birthday Present' ?
                                        $config['rewardpoints/notifications/birthday'] :
                                        ($this->storage['comment'] === 'Expiration' ?
                                                $config['rewardpoints/notifications/expiring'] :
                                                $config['rewardpoints/notifications/updated']),
                                'language_id' => $customer['language_id']
                    ]);
                    $days = strtotime('+' . $config['rewardpoints/general/expiration'] . ' days');
                    $mailer = $this->getContainer()->get('mailer');
                    $params = [
                        'type' => $this->translate($this->storage['count'] > 0 ? 'gathered' : 'used', [], 'rewardpoints', $language['code']),
                        'points' => abs($this->storage['count']),
                        'balance' => $customer['rewardpoints'],
                        'username' => $customer['username'],
                        'expiration' => date('Y-m-d', $days)
                    ];
                    $mailTemplate = new TemplateModel($collection[0]);
                    $recipients = [];
                    $recipients[] = [$customer['email'], $customer['username']];
                    $subject = $mailTemplate['subject'];
                    $content = $mailTemplate->getContent($params);
                    $mailer->send($recipients, $subject, $content, [], '', '', [], true, '', $from);
                }
            } catch (EmailException $e) {
                $this->getContainer()->get('log')->logException($e);
            }
        }
        parent::afterSave();
    }

}
