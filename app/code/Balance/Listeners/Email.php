<?php

namespace Redseanet\Balance\Listeners;

use Redseanet\Balance\Source\DrawType;
use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Listeners\ListenerInterface;
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

    public function afterStatusChanged($e)
    {
        $model = $e['model'];
        if ($model->getId() && $model['status']) {
            $model->load($model->getId());
            $customer = $model->getCustomer();
            $detail = json_decode($model['account'], true);
            $this->sendMail('balance/general/complete_email', [
                $customer['email'],
                $customer['username']
            ], [
                'username' => $customer['username'],
                'type' => $this->translate((new DrawType())->getSourceArray()[$model['type']], [], 'sales', @$model->getCustomer()->getLanguage()['code']),
                'created_at' => $model['created_at']
            ] + $detail);
        }
    }
}
