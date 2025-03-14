<?php

namespace Redseanet\Email\Listeners;

use Redseanet\Email\Model\Template;
use Redseanet\Email\Model\Collection\Queue as Collection;
use Redseanet\Email\Model\Queue as Model;

class Cron {

    use \Redseanet\Lib\Traits\Container;

    public function schedule() {
        $queue = new Collection();
        $queue->where(['status' => 0]);
        $mailer = $this->getContainer()->get('mailer');
        $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
        $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
        foreach ($queue as $item) {
            if (strtotime($item['scheduled_at']) <= time()) {
                $template = new Template();
                $template->load($item['id']);
                if ($template->getId()) {
                    $params = [];
                    $recipients = [];
                    $recipients[] = [$item['to'], $item['to']];
                    $subject = $template['subject'];
                    $content = $template->getContent($params);
                    $mailer->send($recipients, $subject, $content, [], '', '', [], true, '', $from);
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
