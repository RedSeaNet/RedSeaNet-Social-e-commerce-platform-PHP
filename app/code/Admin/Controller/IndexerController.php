<?php

namespace Redseanet\Admin\Controller;

use Exception;
use Redseanet\Lib\Controller\AuthActionController;
use Redseanet\Lib\Model\Collection\Eav\Type;
use Redseanet\Lib\Model\Cron;

class IndexerController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('admin_indexer');
    }

    public function rebuildAction()
    {
        $code = $this->getRequest()->getPost('id');
        $result = ['message' => [], 'error' => 0];
        if (!$code) {
            $code = array_merge((new Type())->toArray(), array_keys($this->getContainer()->get('config')['indexer']));
        }
        $manager = $this->getContainer()->get('indexer');
        $count = 0;
        touch(BP . 'maintence');
        try {
            foreach ((array) $code as $indexer) {
                $manager->reindex(is_string($indexer) ? $indexer : $indexer['code']);
                $count++;
            }
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
        }
        unlink(BP . 'maintence');
        $result['message'][] = ['message' => $this->translate('%d indexer(s) have been rebuild successfully.', [$count]), 'level' => 'success'];
        return $this->response($result, ':ADMIN/indexer/');
    }

    public function scheduleAction()
    {
        $code = $this->getRequest()->getPost('id');
        $result = ['message' => [], 'error' => 0];
        if (!$code) {
            $code = [];
            foreach ((array) array_merge((new Type())->toArray(), array_keys($this->getContainer()->get('config')['indexer'])) as $indexer) {
                $code[] = is_string($indexer) ? $indexer : $indexer['code'];
            }
        }
        $cron = new Cron();
        $cron->setData([
            'code' => 'Redseanet\\Admin\\Listeners\\Cron::reindex(' . implode(',', (array) $code) . ')',
            'scheduled_at' => date('Y-m-d H:i:s')
        ])->save();
        $result['message'][] = ['message' => $this->translate('The schedule has been saved.'), 'level' => 'success'];
        return $this->response($result, ':ADMIN/indexer/');
    }
}
