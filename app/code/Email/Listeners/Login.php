<?php

namespace Redseanet\Email\Listeners;

use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Lib\Listeners\ListenerInterface;
use PHPMailer\PHPMailer\Exception as EmailException;

class Login implements ListenerInterface {

    use \Redseanet\Lib\Traits\Container;

    public function welcome($event) {
        $data = $event["model"];
        $config = $this->getContainer()->get('config');
        $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
        $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
        try {
            if (!empty($from)) {
                $collection = new TemplateCollection();
                $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                        ->where([
                            'code' => $config['email/customer/confirm_template'],
                            'language_id' => $data['language_id']
                ]);
                if (count($collection) > 0) {
                    $mailer = $this->getContainer()->get('mailer');
                    $params = ['username' => $data['username']];
                    $mailTemplate = new TemplateModel($collection[0]);
                    $recipients = [];
                    $recipients[] = [$data['email'], $data['username']];
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

}
