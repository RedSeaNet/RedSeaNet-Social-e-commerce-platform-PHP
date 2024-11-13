<?php

namespace Redseanet\Lib\Controller;

use Redseanet\Cms\Model\Page;
use Redseanet\Lib\Bootstrap;
use Redseanet\Lib\Http\Response;
use Redseanet\Lib\Session\Csrf;
use Redseanet\Lib\Session\Segment;

/**
 * Controller for normal request
 */
abstract class ActionController extends AbstractController
{
    use \Redseanet\Lib\Traits\Translate;

    use \Redseanet\Lib\Traits\Url;

    /**
     * @var Csrf
     */
    protected $csrf = null;

    /**
     * @var array
     */
    protected $responseData = [];

    public function notFoundAction()
    {
        $this->getResponse()->withStatus(404);
        $index = $this->getContainer()->get('indexer')->select('cms_url', Bootstrap::getLanguage()->getId(), ['path' => ($this->getContainer()->get('config')['route']['default']['prefix'] ?? '') . 'not-found']);
        if (count($index)) {
            $page = new Page();
            $page->load($index[0]['page_id']);
            $root = $this->getLayout('cms_page');
            $root->addBodyClass('page-not-found');
            $head = $root->getChild('head');
            $head->setTitle($page['title'])
                    ->setKeywords($page['keywords'])
                    ->setDescription($page['description']);
            $root->getChild('page', true)->setPageModel($page);
            return $root;
        }
        return $this->getResponse();
    }

    /**
     * Redirect to referer location
     *
     * @param string $location If there is no referer header
     * @param string $code HTTP Status Code 301|302
     * @return Response
     */
    protected function redirectReferer($location = '/', $code = 302)
    {
        $referer = $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'];
        if ($referer === $this->getRequest()->getUri()->__toString()) {
            $referer = false;
        }
        if (!$referer && strpos($location, '//') === false) {
            $location = strpos($location, ':ADMIN') === false ? $this->getBaseUrl($location) : $this->getAdminUrl($location);
        }
        return $this->redirect($referer ?: $location, $code);
    }

    /**
     * Redirect to the specified location
     *
     * @param string $location
     * @param string $code HTTP Status Code 301|302
     * @return Response
     */
    protected function redirect($location = '/', $code = 302)
    {
        if (strpos($location, '//') === false) {
            $location = strpos($location, ':ADMIN') === false ? $this->getBaseUrl($location) : $this->getAdminUrl($location);
        }
        return $this->getResponse()->withHeader('Location', $location)->withStatus($code);
    }

    /**
     * Validate csrf key for forms
     *
     * @param string $value
     * @return string
     */
    protected function validateCsrfKey($value)
    {
        if (is_null($this->csrf)) {
            $this->csrf = new Csrf();
        }
        return $this->csrf->isValid($value);
    }

    /**
     * Validate captcha value for forms
     *
     * @param string $value
     * @param Segment $segment
     * @return boolean
     */
    protected function validateCaptcha($value, $segment = 'core')
    {
        $segment = new Segment($segment);
        return $segment->get('captcha') == strtoupper($value);
    }

    /**
     * Add message to session
     *
     * @param string|array $message
     * @param Segment|string $segment
     */
    protected function addMessage($message, $level = 'info', $segment = 'core')
    {
        if (is_string($segment)) {
            $segment = new Segment($segment);
        }
        $segment->addMessage(is_string($message) ? [['message' => $message, 'level' => $level]] : $message);
    }

    /**
     * Get message from session
     *
     * @param Segment|string $segment
     * @return array
     */
    protected function getMessage($segment = 'core')
    {
        if (is_string($segment)) {
            $segment = new Segment($segment);
        }
        return (array) $segment->getMessage();
    }

    /**
     * Get layout
     *
     * @param string $handler
     * @param bool $render
     * @return \Redseanet\Lib\ViewModel\Root|array
     */
    protected function getLayout($handler, $render = true)
    {
        return $this->getContainer()->get('layout')->getLayout($handler, $render);
    }

    /**
     * Response in different way
     *
     * @param array $result
     * @param string $url
     * @param string $segment
     * @return Response|array
     */
    protected function response($result, $url, $segment = 'admin', $customerId = '')
    {
        $this->responseData = $result;
        if ($this->getRequest()->isXmlHttpRequest()) {
            if ($result['error'] && isset($result['error_url'])) {
                $result['redirect'] = $result['error_url'];
            } elseif (!$result['error'] && isset($result['success_url'])) {
                $result['redirect'] = $result['success_url'];
            }
            if (isset($result['redirect']) || isset($result['reload'])) {
                $this->addMessage($result['message'], 'danger', $segment);
            }
            return $result;
        } else {
            $this->addMessage($result['message'], 'danger', $segment, $customerId);
            if ($result['error']) {
                if ($this->getRequest()->isPost()) {
                    $segment = new Segment('core');
                    $segment->set('form_data', $this->getRequest()->getPost());
                }
                $response = $this->redirectReferer($url);
            } else {
                if ($this->getRequest()->isPost()) {
                    $segment = new Segment('core');
                    $segment->offsetUnset('form_data');
                }
                $response = $this->redirect($result['success_url'] ?? $url);
            }
            if (isset($result['cookie'])) {
                foreach ($result['cookie'] as $cookie) {
                    $key = $cookie['key'];
                    unset($cookie['key']);
                    $response->withCookie($key, $cookie);
                }
            }
            return $response;
        }
    }

    /**
     * Validate form data
     *
     * @param array $data
     * @param array $required
     * @param bool $captcha
     * @return array
     */
    protected function validateForm(array $data, array $required = [], $captcha = false)
    {
        $result = ['error' => 0, 'message' => []];
        if (!isset($data['csrf']) || !$this->validateCsrfKey($data['csrf'])) {
            $result['message'][] = ['message' => $this->translate('The form submitted did not originate from the expected site.'), 'level' => 'danger'];
            $result['error'] = 1;
        }
        foreach ($required as $item) {
            if (!isset($data[$item]) || !is_numeric($data[$item]) && empty($data[$item])) {
                $result['message'][] = ['message' => $this->translate('The %s field is required and can not be empty.', [$this->translate(ucfirst($item))]), 'level' => 'danger'];
                $result['error'] = 1;
            }
        }
        if ($captcha && (empty($data['captcha']) || !$this->validateCaptcha($data['captcha'], $captcha))) {
            $result['message'][] = ['message' => $this->translate('The captcha value is wrong.'), 'level' => 'danger'];
            $result['error'] = 1;
        }
        if (isset($data['success_url'])) {
            $result['success_url'] = rawurldecode($data['success_url']);
        }
        if (isset($data['error_url'])) {
            $result['error_url'] = rawurldecode($data['error_url']);
        }
        return $result;
    }
}
