<?php

namespace Redseanet\Email\Mq;

use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Mq\MqInterface;
use Redseanet\Lib\Bootstrap;
use PHPMailer\PHPMailer\Exception as EmailException;

class Register implements MqInterface {

    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\Lib\Traits\Url;

    public function welcome($data) {
        $config = $this->getContainer()->get('config');
        $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
        $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
        //echo $collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        try {
            if (!empty($from)) {
                $template_name = $data['status'] ? 'email/customer/confirm_template' : 'email/customer/welcome_template';
                $collection = new TemplateCollection();
                $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                        ->where([
                            'email_template.code' => $config[$template_name],
                            'email_template_language.language_id' => $data['language_id']
                ]);
                if (count($collection) > 0) {
                    $params = ['username' => $data['username'], 'confirm' => $this->getBaseUrl('customer/account/confirm/?token=' . $data['token'])];
                    $mailer = $this->getContainer()->get('mailer');
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
