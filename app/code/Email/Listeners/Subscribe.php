<?php

namespace Redseanet\Email\Listeners;

use Redseanet\Email\Model\Subscriber;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Listeners\ListenerInterface;

class Subscribe implements ListenerInterface
{
    public function subscribe($event)
    {
        $data = $event['data'];
        $subscriber = new Subscriber();
        if (!empty($data['email'])) {
            $subscriber->load($data['email'], 'email');
            if (empty($data['subscribe'])) {
                if ($subscriber->getId()) {
                    $subscriber->unsubscribe();
                }
            } else {
                $subscriber->setData([
                    'email' => $data['email'],
                    'language_id' => Bootstrap::getLanguage()->getId(),
                    'status' => 1
                ])->save();
            }
        }
    }
}
