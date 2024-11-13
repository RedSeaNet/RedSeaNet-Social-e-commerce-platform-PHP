<?php

namespace Redseanet\Email\Listeners;

use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Listeners\ListenerInterface;

class Password implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function notify($event)
    {
        $customer = $event['model'];
        $data = $event['data'];
        $config = $this->getContainer()->get('config');
        if ($customer['modified_password'] && $from = $config['email/customer/sender_email'] ?: $config['email/default/sender_email']) {
            $collection = new TemplateCollection();
            $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                    ->where([
                        'code' => $config['email/customer/modified_template'],
                        'language_id' => $customer['language_id']
                    ]);
            $language = new Language();
            $language->load($customer['language_id']);
            $mailer = $this->getContainer()->get('mailer');
            $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
            $params = [
                'password' => $data['password'],
                'username' => $customer['username']
            ];
            $mailTemplate = new TemplateModel($collection[0]);
            $recipients = [];
            $recipients[] = [$customer['email'], $customer['username']];
            $subject = $mailTemplate['subject'];
            $content = $mailTemplate->getContent($params);
            $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
            $mailer->send($recipients, $subject, $content, [], '', '', [], true, '', $from);
        }
    }
}
