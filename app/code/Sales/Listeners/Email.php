<?php

namespace Redseanet\Sales\Listeners;

use Redseanet\Customer\Model\Customer;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Listeners\ListenerInterface;
use Redseanet\Retailer\Model\Retailer;
use Redseanet\Sales\Model\Order;
use Redseanet\Sales\Source\Refund\Service;
use Redseanet\Sales\Source\Refund\Status;
use PHPMailer\PHPMailer\Exception as EmailException;

class Email implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\Translate;

    private function sendMail($template, $to, $params = [])
    {
        $config = $this->getContainer()->get('config');
        $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
        if (!empty($fromEmail)) {
            $mailer = $this->getContainer()->get('mailer');
            $languageId = Bootstrap::getLanguage()->getId();
            $collection = new TemplateCollection();
            $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                    ->where([
                        'code' => $config[$template],
                        'language_id' => $languageId
                    ]);
            if (count($collection)) {
                try {
                    $mailTemplate = new TemplateModel($collection[0]);
                    $recipients = [];
                    $recipients[] = [$to[0], $to[1]];
                    $subject = $mailTemplate['subject'];
                    $content = $mailTemplate->getContent($params);
                    $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
                    $mailer->send($recipients, $subject, $content, [], '', '', [], true, '', $from);
                } catch (EmailException $e) {
                    $this->getContainer()->get('log')->logException($e);
                }
            }
        }
    }

    private function getCustomer(Order $order)
    {
        $customer = $order->getCustomer();
        if ($customer) {
            return $customer;
        } else {
            $address = $order->getBillingAddress() ?: $order->getShippingAddress();
            if (!$address['email']) {
                return null;
            }
            return [
                'username' => $address['name'],
                'email' => $address['email']
            ];
        }
    }

    public function afterOrderPlaced($e)
    {
        $model = $e['model'];
        $customer = $this->getCustomer($model);
        if ($customer) {
            $items = $model->getItems(true);
            $qty = 0;
            foreach ($items as $item) {
                $qty += (float) $item['qty'];
            }
            $this->sendMail('checkout/email/order_placed_template', [
                $customer['email'],
                $customer['username']
            ], [
                'username' => $customer['username'],
                'id' => $model->offsetGet('increment_id'),
                'products' => $items[0]['product']['name'],
                'qty' => $qty,
                'billing_address' => $model['billing_address'],
                'shipping_address' => $model['shipping_address'],
                'subtotal' => $model['subtotal'],
                'tax' => $model['tax'],
                'discount' => $model['discount'],
                'shipping' => $model['shipping'],
                'total' => $model['total'],
                'created_at' => date('Y-m-d H:i'),
                'order' => ['model' => $model]
            ]);
        }
    }

    public function afterInvoiceSaved($e)
    {
        if ($e['isNew']) {
            $model = $e['model'];
            $items = $model->getItems(true);
            if (!$model instanceof Order) {
                $model = $model->getOrder();
            }
            $customer = $this->getCustomer($model);
            if ($customer) {
                $qty = 0;
                $productName = '';
                foreach ($items as $item) {
                    $qty += (float) $item['qty'];
                    if (empty($productName)) {
                        $productName = $item['product_name'];
                    }
                }
                $this->sendMail('checkout/email/invoice_template', [
                    $customer['email'],
                    $customer['username']
                ], [
                    'username' => $customer['username'],
                    'id' => $model->offsetGet('increment_id'),
                    'status' => $this->translate($model->getStatus()->offsetGet('name'), [], 'sales', @$model->getLanguage()['code']),
                    'products' => $productName,
                    'qty' => $qty,
                    'subtotal' => $model['subtotal'],
                    'tax' => $model['tax'],
                    'discount' => $model['discount'],
                    'shipping' => $model['shipping'],
                    'total' => $model['total'],
                    'created_at' => $model['created_at'],
                    'updated_at' => $model['updated_at'],
                    'order' => ['model' => $model]
                ]);
            }
        }
    }

    public function afterShipmentSaved($e)
    {
        if ($e['isNew']) {
            $model = $e['model'];
            $track = $e['track'] ?? [];
            $items = $model->getItems(true);
            if (!$model instanceof Order) {
                $model = $model->getOrder();
            }
            $customer = $this->getCustomer($model);
            if ($customer) {
                $qty = 0;
                foreach ($items as $item) {
                    $qty += (float) $item['qty'];
                }
                $this->sendMail('checkout/email/shipment_template', [
                    $customer['email'],
                    $customer['username']
                ], [
                    'username' => $customer['username'],
                    'name' => $model->getShippingAddress()['name'],
                    'id' => $model->offsetGet('increment_id'),
                    'carrier' => $track['carrier'] ?? '',
                    'tracking_number' => $track['tracking_number'] ?? '',
                    'status' => $this->translate($model->getStatus()->offsetGet('name'), [], 'sales', @$model->getLanguage()['code']),
                    'products' => $items[0]['product']['name'],
                    'qty' => $qty,
                    'subtotal' => $model['subtotal'],
                    'tax' => $model['tax'],
                    'discount' => $model['discount'],
                    'shipping' => $model['shipping'],
                    'total' => $model['total'],
                    'created_at' => $model['created_at'],
                    'updated_at' => $model['updated_at'],
                    'order' => ['model' => $model]
                ]);
            }
        }
    }

    public function orderStatusChanged($e)
    {
        if ($e['is_customer_notified']) {
            $model = $e['model'];
            if (!$model instanceof Order) {
                $model = $model->getOrder();
            }
            $items = $model->getItems(true);
            $qty = 0;
            foreach ($items as $item) {
                $qty += (float) $item['qty'];
            }
            $customer = $this->getCustomer($model);
            $this->sendMail('checkout/email/shipment_template', [
                $customer['email'],
                $customer['username']
            ], [
                'username' => $customer['username'],
                'id' => $model->offsetGet('increment_id'),
                'status' => $this->translate($model->getStatus()->offsetGet('name'), [], 'sales', @$model->getLanguage()['code']),
                'products' => $items[0]['product']['name'],
                'qty' => $qty,
                'subtotal' => $model['subtotal'],
                'tax' => $model['tax'],
                'discount' => $model['discount'],
                'shipping' => $model['shipping'],
                'total' => $model['total'],
                'created_at' => $model['created_at'],
                'updated_at' => $model['updated_at'],
                'order' => ['model' => $model]
            ]);
        }
    }

    public function rma($e)
    {
        $model = $e['model'];
        $order = $model->getOrder();
        $customer = $this->getCustomer($order);
        $items = $model->getItems(true);
        $qty = 0;
        foreach ($items as $item) {
            $qty += (float) $item['qty'];
        }
        $status = (new Status())->getSourceArray($model['service'])[$model['status']] ?? '';
        $params = [
            'username' => $customer['username'],
            'id' => $order->offsetGet('increment_id'),
            'products' => $items[0]['product']['name'],
            'qty' => $qty,
            'service' => $this->translate((new Service())->getSourceArray()[$model['service']], [], 'sales', @$order->getLanguage()['code']),
            'status' => $this->translate($status, [], 'sales', @$order->getLanguage()['code']),
            'placed_at' => $order['created_at'],
            'created_at' => $model['created_at'],
            'updated_at' => $model['updated_at'] ?: $model['created_at'],
            'rma' => ['model' => $model]
        ];
        $this->sendMail('checkout/email/rma_template', [
            $customer['email'],
            $customer['username']
        ], $params);
        if (class_exists('\\Redseanet\\Retailer\\Model\\Retailer')) {
            $retailer = new Retailer();
            $retailer->load($order['store_id'], 'store_id');
            if ($retailer->getId()) {
                $customer = new Customer();
                $customer->load($retailer['customer_id']);
                $this->sendMail('checkout/email/rma_template', [
                    $customer['email'],
                    $customer['username']
                ], $params);
            }
        }
    }
}
