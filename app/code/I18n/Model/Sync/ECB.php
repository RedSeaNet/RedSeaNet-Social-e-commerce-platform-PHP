<?php

namespace Redseanet\I18n\Model\Sync;

use Exception;
use Redseanet\I18n\Model\Currency as Model;

class ECB extends AbstractService
{
    protected $sync_url = 'https://www.ecb.europa.eu/stats/eurofxref/eurofxref-daily.xml';

    public function sync($cur, $base)
    {
        $result = ['error' => 0, 'message' => []];
        try {
            $xml = $this->request($this->sync_url, 'xml');
            $rates = ['EUR' => 1];
            foreach ($xml->Cube->Cube->Cube as $rate) {
                $rates[(string) $rate['currency']] = (float) $rate['rate'];
            }
            $this->beginTransaction();
            foreach ((array) $cur as $item) {
                if ($item === $base) {
                    $rate = 1;
                } else {
                    if (isset($rates[$item]) && isset($rates[$base])) {
                        $rate = function_exists('bcmul') ? bcmul(bcdiv(1, $rates[$base], 8), $rates[$item], 6) : 1 / $rates[$base] * $rates[$item];
                    } else {
                        $rate = 1;
                    }
                }
                $model = new Model();
                $model->load($item, 'code');
                $model->setData([
                    'code' => $item,
                    'rate' => $rate
                ])->save();
            }
            $this->commit();
            $result['message'][] = ['message' => $this->translate('Currency rates have been synchronized successfully.'), 'level' => 'success'];
        } catch (Exception $e) {
            $this->rollback();
            $this->getContainer()->get('log')->logException($e);
            $result['error'] = 1;
            $result['message'][] = ['message' => $this->translate('An error detected while synchronizing, please try again later.'), 'level' => 'danger'];
        }
        return $result;
    }
}
