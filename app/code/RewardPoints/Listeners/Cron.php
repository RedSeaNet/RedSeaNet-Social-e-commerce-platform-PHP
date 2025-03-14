<?php

namespace Redseanet\RewardPoints\Listeners;

use Exception;
use Redseanet\Customer\Model\Collection\Customer as CustomerCollection;
use Redseanet\Customer\Model\Customer;
use Redseanet\RewardPoints\Model\Collection\Record as Collection;
use Redseanet\RewardPoints\Model\Record;
use Redseanet\Sales\Model\Collection\Order;
use Laminas\Db\Sql\Expression;
use Redseanet\Email\Model\Template as TemplateModel;
use PHPMailer\PHPMailer\Exception as EmailException;

class Cron {

    use \Redseanet\Lib\Traits\Container;

    private function sendMail($template, $to, $params = []) {
        try {
            $config = $this->getContainer()->get('config');
            $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
            $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
            if (empty($from)) {
                $collection = new TemplateCollection();
                $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                        ->where([
                            'code' => $config[$template],
                            'language_id' => Bootstrap::getLanguage()->getId()
                ]);
                if (!is_array($to)) {
                    $to = [$to, null];
                }
                if (count($collection)) {
                    $mailer = $this->getContainer()->get('mailer');
                    $mailTemplate = new TemplateModel($collection[0]);
                    $recipients = [];
                    $recipients[] = [$to[0], $to[1]];
                    $subject = $mailTemplate['subject'];
                    $content = $mailTemplate->getContent($params);
                    $mailer->send($recipients, $subject, $content, [], '', '', [], true, '', $from);
                }
            }
        } catch (EmailException $e) {
            $this->getContainer()->get('log')->logException($e);
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
        }
    }

    public function schedule() {
        $config = $this->getContainer()->get('config');
        if ($config['rewardpoints/general/enable'] && ($points = $config['rewardpoints/gathering/birthday'])) {
            try {
                $collection = new CustomerCollection();
                $collection->where(['status' => 1])
                        ->where->greaterThanOrEqualTo('birthday', date('Y-m-d 0:0:0'))
                        ->lessThanOrEqualTo('birthday', date('Y-m-d 23:59:59'));
                $collection->load(true, true);
                foreach ($collection as $customer) {
                    $this->sendMail('reward_points_birthday', [$customer['email'], $customer['username']], [
                        'username' => $customer['username'],
                        'count' => $points
                    ]);
                    $model = new Record([
                        'customer_id' => $customer['id'],
                        'count' => $points,
                        'comment' => 'Birthday Present',
                        'status' => 1
                    ]);
                    $model->save();
                }
            } catch (Exception $e) {
                
            }
        }
    }

    public function activation() {
        if ($days = $this->getContainer()->get('config')['rewardpoints/general/activating']) {
            $orders = new Order();
            $orders->columns(['id'])
                    ->join('sales_order_status', 'sales_order_status.id=sales_order.status_id', [], 'left')
                    ->join('sales_order_phase', 'sales_order_phase.id=sales_order_status.phase_id', [], 'left')
                    ->where(['sales_order_phase.code' => 'complete'])
            ->where->lessThanOrEqualTo('created_at', date('Y-m-d H:i:s', strtotime('-' . $days . 'days')));
            $collection = new Collection();
            $collection->in('order_id', $orders)
                    ->columns(['id'])
                    ->where(['status' => 0])
            ->where->greaterThan('count', 0);
            foreach ($collection as $record) {
                $record->setData('status', 1)->save();
            }
        }
    }

    private function getExpiredCount($record) {
        $collection = new Collection();
        $collection->columns(['customer_id', 'amount' => new Expression('sum(count)')])
                ->where([
                    'status' => 1,
                    'customer_id' => $record['customer_id']
                ])->group('customer_id')
                ->where->lessThan('count', 0)
                ->greaterThanOrEqualTo('id', $record['id']);
        $collection->load(false, true);
        $amount = count($collection) ? $collection[0]['amount'] : 0;
        return $record['count'] + $amount;
    }

    public function expiration() {
        $config = $this->getContainer()->get('config');
        if ($config['rewardpoints/general/enable'] && ($days = (int) $config['rewardpoints/general/expiration'])) {
            $date = date('Y-m-d H:i:s', strtotime('-' . $days . 'days'));
            $collection = new Collection();
            $collection->where(['status' => 1])
                    ->limit(100)
                    ->where->greaterThan('count', 0)
                    ->lessThanOrEqualTo('created_at', $date);
            foreach ($collection->load(false, true) as $item) {
                if (($expired = $this->getExpiredCount($item)) > 0) {
                    $customer = new Customer();
                    $customer->load($item['customer_id']);
                    $this->sendMail('reward_points_expiring', [$customer['email'], $customer['username']], [
                        'username' => $customer['username'],
                        'current' => $customer['rewardpoints'],
                        'count' => $expired
                    ]);
                    $record = new Record([
                        'customer_id' => $item['customer_id'],
                        'count' => 0 - $expired,
                        'comment' => 'Expiration',
                        'status' => 1
                    ]);
                    $record->save();
                }
            }
        }
    }

}
