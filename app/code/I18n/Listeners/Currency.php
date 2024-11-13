<?php

namespace Redseanet\I18n\Listeners;

use Redseanet\Lib\Listeners\ListenerInterface;
use Laminas\Db\Sql\Predicate\NotIn;

class Currency implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\DataCache;

    use \Redseanet\Lib\Traits\Translate;

    use \Redseanet\I18n\Traits\Currency;

    public function afterSave($event)
    {
        try {
            $this->getTableGateway('i18n_currency')->delete(new NotIn('code', $event['value']));
            foreach ($event['value'] as $code) {
                $this->upsert(['code' => $code], ['code' => $code]);
            }
            $this->flushList('i18n_currency');
        } catch (\Exception $e) {
        }
    }

    public function schedule()
    {
        $config = $this->getContainer()->get('config');
        $base = $config['i18n/currency/base'];
        $collection = $config['i18n/currency/enabled'];
        if (is_string($collection)) {
            $collection = explode(',', $collection);
        }
        return $this->sync($collection, $base)['message'][0]['message'];
    }
}
