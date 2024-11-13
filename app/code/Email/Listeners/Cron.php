<?php

namespace Redseanet\Email\Listeners;

use Redseanet\Email\Model\Template;
use Redseanet\Email\Model\Collection\Queue as Collection;
use Redseanet\Email\Model\Queue as Model;

class Cron
{
    use \Redseanet\Lib\Traits\Container;

    public function schedule()
    {
        $queue = new Collection();
        $queue->where(['status' => 0]);
        $mailer = $this->getContainer()->get('mailer');
        foreach ($queue as $item) {
            if (strtotime($item['scheduled_at']) <= time()) {
                $template = new Template();
                $template->load($item['id']);
                if ($template->getId()) {
                    $mailer->send($template->getMessage()->addFrom($item['from'])->addTo($item['to']));
                }
                $model = new Model([
                    'id' => $item['id'],
                    'status' => 1,
                    'finished_at' => date('Y-m-d H:i:s')
                ]);
                $model->save();
            }
        }
    }
}
