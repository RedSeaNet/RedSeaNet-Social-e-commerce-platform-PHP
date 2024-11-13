<?php

namespace Redseanet\I18n\Model\Sync;

use Exception;
use Redseanet\I18n\Model\Currency as Model;

class Fixer extends AbstractService
{
    protected $sync_url = 'https://api.fixer.io/latest?base={{from}}&symbols={{to}}';

    public function sync($cur, $base)
    {
        $result = ['error' => 0, 'message' => []];
        try {
            $json = $this->request(str_replace(['{{from}}', '{{to}}'], [$base, implode(',', (array) $cur)], $this->sync_url), 'json');
            $json['rates'][$base] = 1;
            $this->beginTransaction();
            foreach ($json['rates'] as $code => $rate) {
                $model = new Model();
                $model->load($code, 'code');
                $model->setData([
                    'code' => $code,
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
