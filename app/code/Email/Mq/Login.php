<?php

namespace Redseanet\Email\Mq;

use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Mq\MqInterface;

class Login implements MqInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function welcome($data)
    {
        $config = $this->getContainer()->get('config');
        $from = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
        $collection = new TemplateCollection();
        $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                ->where([
                    'code' => $config['email/customer/welcome_template'],
                    'language_id' => $data['language_id']
                ]);
        $language = new Language();
        $language->load($data['language_id']);
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send((new TemplateModel($collection[0]))
                        ->getMessage([
                            'password' => $data['password'],
                            'username' => $data['username']
                        ])
                        ->addFrom($from, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null))
                        ->addTo($data['email'], $data['username']));
    }
}
