<?php

namespace Redseanet\Retailer\Controller;

use Exception;
use Redseanet\Retailer\Model\Application;
use Redseanet\Lib\Session\Segment;

class AccountController extends AuthActionController
{
    public function indexAction()
    {
        return $this->getLayout('retailer_account_dashboard');
    }

    public function applyAction()
    {
        return $this->getLayout('retailer_apply');
    }

    public function reapplyAction()
    {
        $model = new Application();
        $model->load((new Segment('customer'))->get('customer')['id']);
        $root = $this->getLayout('retailer_apply');
        $root->getChild('main', true)->setVariable('data', $model->toArray());
        return $root;
    }

    public function applyPostAction()
    {
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['phone', 'brand_type', 'product_type']);
            $files = $this->getRequest()->getUploadedFile();
            if (count($files) < ($data['store_type'] == 1 ? 2 : 1)) {
                $result['message'][] = ['message' => $this->translate('The lisence images are required and cannot be empty.', [], 'retailer'), 'level' => 'danger'];
                $result['error'] = 1;
            } elseif ($files['id1']->getSize() > 1048576 || $data['store_type'] == 1 && $files['id2']->getSize() > 1048576) {
                $result['message'][] = ['message' => $this->translate('You probably tried to upload a file that is too large.', [], 'retailer'), 'level' => 'danger'];
                $result['error'] = 1;
            }
            if ($result['error'] === 0) {
                $model = new Application($data);
                try {
                    $model->setData([
                        'customer_id' => (new Segment('customer'))->get('customer')['id'],
                        'lisence_1' => $files['id1']->getStream()->getContents(),
                        'lisence_2' => $data['store_type'] == 1 ? $files['id2']->getStream()->getContents() : null,
                        'status' => 0
                    ]);
                    $model->save();
                    $result['data'] = $model->getArrayCopy();
                    $result['message'][] = ['message' => $this->translate('An item has been saved successfully.'), 'level' => 'success'];
                } catch (Exception $e) {
                    $this->getContainer()->get('log')->logException($e);
                    $result['message'][] = ['message' => $this->translate('An error detected while saving. Please check the log report or try again.'), 'level' => 'danger'];
                    $result['error'] = 1;
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], 'retailer/account/processing/', 'customer');
    }

    public function processingAction()
    {
        return $this->getLayout('retailer_processing');
    }

    public function rewardAction()
    {
        return $this->getLayout('retailer_balance_statement');
    }
}
