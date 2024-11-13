<?php

namespace Redseanet\Lib\Model;

class Cron extends AbstractModel
{
    protected function construct()
    {
        $this->init('core_schedule', 'id', ['id', 'messages', 'code', 'status', 'created_at', 'scheduled_at', 'executed_at', 'finished_at']);
    }
}
