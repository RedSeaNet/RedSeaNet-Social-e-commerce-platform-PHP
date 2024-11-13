<?php

namespace Redseanet\Email\Mq;

use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Mq\MqInterface;
use Redseanet\Lib\Bootstrap;

class Register implements MqInterface
{
    use \Redseanet\Lib\Traits\Container;
    use \Redseanet\Lib\Traits\Url;

    public function welcome($data)
    {
        $config = $this->getContainer()->get('config');
        $from = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
        $template_name = $data['status'] ? 'email/customer/confirm_template' : 'email/customer/welcome_template';
        $collection = new TemplateCollection();
        echo $template_name;
        $params = ['username' => $data['username'], 'confirm' => $this->getBaseUrl('customer/account/confirm/?token=' . $data['token'])];
        $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                ->where([
                    'email_template.code' => $config[$template_name],
                    'email_template_language.language_id' => $data['language_id']
                ]);
        //echo $collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        $language = new Language();
        $language->load($data['language_id']);
        $mailer = $this->getContainer()->get('mailer');
        $mailer->send((new TemplateModel($collection[0]))
                        ->getMessage($params)
                        ->addFrom($from, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null))
                        ->addTo($data['email'], $data['username']));
    }
}
