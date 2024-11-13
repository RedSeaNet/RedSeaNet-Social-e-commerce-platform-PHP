<?php

namespace Redseanet\Notifications\Traits;

use Redseanet\Notifications\Model\Notifications;
use Redseanet\Lib\Bootstrap;

trait NotificationsMethod
{
    protected function addNotifications($data = [])
    {
        $languageId = Bootstrap::getLanguage()->getId();
        $notifications = new Notifications();
        $data['language_id'] = $languageId;
        $notifications->setData($data);
        $notifications->save();
    }

    protected function updateNotifications($id, $data = [])
    {
        $notifications = new Notifications();
        $notifications->load($id);
        $notifications->setData($data);
        $notifications->save();
    }
}
