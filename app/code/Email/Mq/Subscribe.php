<?php

namespace Redseanet\Email\Mq;

use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Lib\Model\Language;
use Redseanet\Lib\Mq\MqInterface;
use Redseanet\Email\Model\Subscriber;

class Subscribe implements MqInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function subscribe($data)
    {
        $subscriber = new Subscriber();
        $subscriber->load($data['email'], 'email');
        if (empty($data['subscribe'])) {
            if ($subscriber->getId()) {
                $subscriber->unsubscribe();
            }
        } else {
            $subscriber->setData([
                'email' => $data['email'],
                'language_id' => $data['language_id'],
                'status' => 1
            ])->save();
        }
    }
}
