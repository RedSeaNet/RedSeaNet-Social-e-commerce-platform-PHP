<?php

namespace Redseanet\Bargain\Controller;

use Redseanet\Lib\Controller\ActionController;
use Redseanet\Catalog\Model\Collection\Product as productCollection;
use Redseanet\Catalog\Model\Product;
use Redseanet\Bargain\Model\Bargain;
use Redseanet\Bargain\Model\Collection\Bargain as bargainCollection;
use Redseanet\Bargain\Model\Collection\BargainCase as bargainCaseCollection;
use Redseanet\Bargain\Model\Collection\BargainCaseHelp as bargainCaseHelpCollection;
use Redseanet\Bargain\Model\BargainCase;
use Redseanet\Bargain\Model\BargainCaseHelp;
use Redseanet\Lib\Session\Segment;
use Redseanet\Customer\Model\Collection\Customer;
use Redseanet\Lib\Bootstrap;

class IndexController extends ActionController
{
    use \Redseanet\Lib\Traits\DataCache;

    public function indexAction()
    {
        if ($id = $this->getRequest()->getQuery('bargain')) {
            $languageId = Bootstrap::getLanguage()->getId();
            $bargain = new Bargain();
            $bargain->load($id);
            if ($bargain->getId()) {
                $lessPrice = round($bargain['price'] - $bargain['min_price'], 2); //剩下需要砍的价格
                $alreadyPrice = 0; //已经砍的价格
                $coverPrice = 0; //用户可以砍掉的金额
                $pricePercent = 0; //已经砍的价格的百分比
                $hadHelpBargain = 0; //已经帮忙砍价的用户
                $currentPrice = $bargain['price'];
                $root = $this->getLayout('bargain_view');

                $segment = new Segment('customer');
                $hasLoggedIn = $segment->get('hasLoggedIn');

                $bargainCaseArray = [];
                $bargainCaseHelpAray = [];

                $bargain_case_id = $this->getRequest()->getQuery('bargain_case_id');
                $bargainCaseArray = [];
                if ($bargain_case_id != '') {
                    $bargainCaseObject = new bargainCaseCollection();
                    $bargainCaseObject->where(['id' => $bargain_case_id]);
                    $bargainCaseObject->load(true, true);
                    if (count($bargainCaseObject) > 0) {
                        $bargainCaseArray = $bargainCaseObject[0];
                    }
                } elseif ($hasLoggedIn) {
                    $customerId = $segment->get('customer')['id'];
                    $bargainCase = new bargainCaseCollection();
                    $bargainCase->where(['bargain_id' => $id, 'status' => 1, 'customer_id' => $customerId]);
                    $bargainCase->load(true, true);
                    if (count($bargainCase) > 0) {
                        $bargainCaseArray = $bargainCase[0];
                    }
                }
                if ((isset($bargainCaseArray['status']) && $bargainCaseArray['status'])) {
                    $bargainHelp = new Customer();
                    $bargainHelp->join('bargain_case_help', 'bargain_case_help.customer_id=main_table.id', ['customer_id', 'price', 'type', 'created_at'], 'left')
                            ->where([
                                'bargain_id' => $id,
                                'bargain_case_id' => $bargainCaseArray['id']
                            ]);
                    //echo $bargainHelp->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());exit;
                    $bargainHelp->load(false, true);
                    $bargainCaseArray['helper'] = $bargainHelp;
                    $alreadyPrice = $bargainCaseArray['price']; //已经砍的价格
                    $coverPrice = (float) bcsub((string) $bargainCaseArray['bargain_price'], (string) $bargainCaseArray['bargain_price_min'], 2); //用户可以砍掉的金额
                    if ($alreadyPrice > 0) {
                        $pricePercent = (int) bcmul((string) bcdiv((string) $alreadyPrice, (string) $coverPrice, 2), '100', 0); //已经砍的价格的百分比
                    }
                    $lessPrice = (float) bcsub((string) $coverPrice, (string) $alreadyPrice, 2); //剩下需要砍的价格
                    $hadHelpBargain = count($bargainCaseArray['helper']); //已经帮忙砍价的用户
                    $currentPrice = round($bargainCaseArray['bargain_price'] - $bargainCaseArray['price'], 2);
                }
                $bargain['lessPrice'] = $lessPrice;
                $bargain['alreadyPrice'] = $alreadyPrice;
                $bargain['coverPrice'] = $coverPrice;
                $bargain['pricePercent'] = $pricePercent;
                $bargain['hadHelpBargain'] = $hadHelpBargain;

                $bargain['currentPrice'] = $currentPrice;
                $root->getChild('head')->setDescription(preg_replace('/\<[^\>]+\>/', '', $bargain['description'][$languageId]));
                $root->getChild('main', true)->setVariable('model', $bargain)->setVariable('bargainCase', $bargainCaseArray);
                $breadcrumb = $root->getChild('breadcrumb', true);
                $breadcrumb->addCrumb([
                    'label' => $bargain['name'][$languageId]
                ]);
                return $root;
            }
        }
        return $this->redirectReferer();
    }

    public function listAction()
    {
        $root = $this->getLayout('bargain_list');
        $root->getChild('head')->setDescription('Bargains');
        $limit = 20;
        $collection = new bargainCollection();
        $collection->where('status=1');
        $collection->limit($limit);
        $bargains = $collection->load();
        $content = $root->getChild('content');

        $content->getChild('main')->setVariable('bargains', $bargains);
        return $root;
    }

    public function joinAction()
    {
        $bargainId = $this->getRequest()->getQuery('bargain');
        $result = ['error' => 0, 'message' => []];
        if ($bargainId != '') {
            $id = intval($bargainId);
            $bargain = new Bargain();
            $bargain->load($id);
            if ($bargain->getId()) {
                $segment = new Segment('customer');
                $hasLoggedIn = $segment->get('hasLoggedIn');
                $customerId = $segment->get('customer')['id'];

                $bargainCaseCount = new bargainCaseCollection();
                $bargainCaseCount->where(['bargain_id' => $id, 'customer_id' => $customerId]);
                $bargainCaseCount->load(true, true);
                if (count($bargainCaseCount) >= $bargain['num']) {
                    $result['error'] = 1;
                    $result['message'][] = ['message' => $this->translate('You canot start new bargain, it only can start ') . $bargain['num'], 'level' => 'danger'];
                } else {
                    $checkExitBargain = false;
                    for ($b = 0; $b < count($bargainCaseCount); $b++) {
                        if ($bargainCaseCount[$b]['status'] == 1) {
                            $checkExitBargain = true;
                        }
                    }
                    if ($checkExitBargain) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('You have an active bargain'), 'level' => 'danger'];
                    } else {
                        $newBargainCase = [];
                        $newBargainCase['customer_id'] = $customerId;
                        $newBargainCase['bargain_id'] = $id;
                        $newBargainCase['bargain_price_min'] = $bargain['min_price'];
                        $newBargainCase['bargain_price'] = $bargain['price'];
                        $newBargainCase['price'] = 0;
                        $newBargainCase['status'] = 1;
                        $newBargainCase['mini_program_qr'] = '';
                        $newBargainCase['web_qr'] = '';
                        $newBargainCase['mp_qr'] = '';
                        $bargainCase = new BargainCase($newBargainCase);
                        $bargainCase->save();
                        $result['error'] = 0;
                        $result['message'] = [];
                    }
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'bargain');
    }

    public function helpAction()
    {
        $bargainId = $this->getRequest()->getQuery('bargain');
        $bargainCaseId = $this->getRequest()->getQuery('bargain_case_id');
        $result = ['error' => 0, 'message' => []];
        if ($bargainId != '') {
            $id = intval($bargainId);
            $bargain = new Bargain();
            $bargain->load($id);
            if ($bargain->getId()) {
                $segment = new Segment('customer');
                $hasLoggedIn = $segment->get('hasLoggedIn');
                $customerId = $segment->get('customer')['id'];

                $bargainCase = new BargainCase();
                $bargainCase->load($bargainCaseId);
                if ($bargainCase->getId()) {
                    $alreadyPrice = $bargainCase->price; //TODO 用户已经砍掉的价格
                    $helpBargainsA = new bargainCaseHelpCollection();
                    $helpBargainsA->where([
                        'bargain_id' => $bargainId,
                        'customer_id' => $customerId
                    ]);

                    $helpBargainsA->load(true, true);
                    if (count($helpBargainsA) > $bargain->bargain_num) {
                        $result['error'] = 1;
                        $result['message'][] = ['message' => $this->translate('You canot help the friend bargain, You have help %s friends', [$bargain->bargain_num]), 'level' => 'danger'];
                    } else {
                        //这个用户砍了这个砍价没有
                        $helpBargains = new bargainCaseHelpCollection();
                        $helpBargains->where([
                            'bargain_id' => $bargainId,
                            'bargain_case_id' => $bargainCaseId,
                            'customer_id' => $customerId
                        ]);
                        $helpBargains->load(true, true);

                        if (count($helpBargains) >= 1) {
                            $result['error'] = 1;
                            $result['message'][] = ['message' => $this->translate('You have help the friend in the bargain'), 'level' => 'danger'];
                        } else {
                            $coverPrice = bcsub((string) $bargain->price, (string) $bargain->min_price, 2);
                            $surplusPrice = bcsub((string) $coverPrice, (string) $alreadyPrice, 2); //TODO 用户剩余要砍掉的价格
                            if (0.00 === (float) $surplusPrice) {
                                $result['error'] = 1;
                                $result['message'][] = ['message' => $this->translate('You dont have price to bargain'), 'level' => 'danger'];
                            } else {
                                $newBargainCaseHelp = [];
                                $newBargainCaseHelp['customer_id'] = $customerId;
                                $newBargainCaseHelp['bargain_id'] = $id;
                                $newBargainCaseHelp['bargain_case_id'] = $bargainCaseId;
                                $newBargainCaseHelp['type'] = 0;
                                //这个砍价已经被砍了多少次
                                $hadHelpBargains = new bargainCaseHelpCollection();
                                $hadHelpBargains->where([
                                    'bargain_id' => $bargainId,
                                    'bargain_case_id' => $bargainCaseId
                                ]);
                                $hadHelpBargains->load(true, true);
                                $help_people_count = count($hadHelpBargains);
                                if (($bargain->people_num - $help_people_count) <= 1) {
                                    $newBargainCaseHelp['price'] = $surplusPrice;
                                } else {
                                    if ($bargainCase->customer_id == $customerId) {
                                        $newBargainCaseHelp['price'] = $bargain->randomFloat($surplusPrice, $bargain->people_num - $help_people_count, false);
                                        $newBargainCaseHelp['type'] = 1;
                                    } else {
                                        $newBargainCaseHelp['price'] = $bargain->randomFloat($surplusPrice, $bargain->people_num - $help_people_count, true);
                                    }
                                }
                                $bargainHelpObject = new bargainCaseHelp($newBargainCaseHelp);
                                $bargainHelpObject->save();
                                $this->fetchList('customer');
                                $bargain_case_price = bcadd((string) $alreadyPrice, (string) $newBargainCaseHelp['price'], 2);
                                $bargainCase->setData('price', (float) $bargain_case_price);
                                $bargainCase->save();
                                $result['error'] = 0;
                                $result['message'] = [];
                            }
                        }
                    }
                }
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'bargain');
    }
}
