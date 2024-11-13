<?php

namespace Redseanet\I18n\Model\Sync;

use Exception;

abstract class AbstractService
{
    use \Redseanet\Lib\Traits\Container;

    use \Redseanet\Lib\Traits\DB;

    use \Redseanet\Lib\Traits\Translate;

    abstract public function sync($cur, $base);

    protected function request($url, $type = 'json')
    {
        if ($type === 'json') {
            $result = json_decode(file_get_contents($url), true);
        } elseif ($type === 'csv') {
            $fp = @fopen($url, 'r');
            if (!$fp) {
                throw new Exception('Connection timed out.');
            }
            $result = fgetcsv($fp);
            fclose($fp);
        } elseif ($type === 'xml') {
            $result = simplexml_load_file($this->sync_url, null, LIBXML_NOERROR);
        } else {
            $result = file_get_contents($url);
        }
        return $result;
    }
}
