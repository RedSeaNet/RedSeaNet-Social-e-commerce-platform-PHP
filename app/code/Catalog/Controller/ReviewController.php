<?php

namespace Redseanet\Catalog\Controller;

use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Controller\ActionController;
use Redseanet\Catalog\Model\Product\Review;
use TijsVerkoyen\Akismet\Exception;
use Redseanet\Lib\Session\Segment;

class ReviewController extends ActionController
{
    public function loadAction()
    {
        $root = $this->getLayout('catalog_review');
        $data = $this->getRequest()->getQuery();
        $content = $root->getChild('content');
        $content->getChild('review')->setVariable('id', $data['id']);
        $content->getChild('inquiry')->setVariable('id', $data['id']);
        $content->getChild('form')->setVariable('id', $data['id']);
        return empty($data['part']) ? $root : $root->getChild('content')->getChild($data['part']);
    }

    public function saveAction()
    {
        if ($this->getRequest()->isPost()) {
            $segment = new Segment('customer');
            if (!$this->getContainer()->get('config')['catalog/review/allow_guests'] && !$segment->get('hasLoggedIn')) {
                return $this->redirectReferer();
            }
            $data = $this->getRequest()->getPost();
            $result = $this->validateForm($data, ['product_id', 'content']);
            $data['customer_id'] = $segment->get('hasLoggedIn') ? $segment->get('customer')['id'] : null;
            $data['status'] = (int) $this->getContainer()->get('config')['catalog/review/status'];
            $data['language_id'] = Bootstrap::getLanguage()->getId();
            $review = new Review($data);
            try {
                $data['content'] = htmlspecialchars($data['content']);
                $review->save();
                $result['error'] = 0;
                $result['message'][] = ['message' => $this->translate('We have received your ' . (empty($data['order_id']) ? 'inquiry' : 'review') . '.'), 'level' => 'success'];
            } catch (Exception $e) {
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please contact us or try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result ?? ['error' => 0, 'message' => []], $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'catalog');
    }
}
