<?php

namespace Redseanet\Email\Controller;

use Exception;
use Redseanet\Lib\Controller\ActionController;
use PHPMailer\PHPMailer\Exception as EmailException;

class ContactController extends ActionController
{
    public function indexAction()
    {
        $result = ['error' => 0, 'message' => []];
        if ($this->getRequest()->isPost()) {
            $data = $this->getRequest()->getPost();
            $content = '';
            foreach ($data as $key => $value) {
                $content .= $key . ': ' . htmlspecialchars(rawurldecode($value)) . '<br />';
            }
            try {
                $config = $this->getContainer()->get('config');
                $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
                if (!empty($fromEmail)) {
                    $mailer = $this->getContainer()->get('mailer');
                    $files = $this->getRequest()->getUploadedFile();
                    $attachments = [];
                    if ($files) {
                        foreach ($files as $file) {
                            $attachments[] = $file;
                        }
                    }
                    try {
                        $recipients = [];
                        $recipients[] = [$config['email/customer/sender_email'] ?: $config['email/default/sender_email'], $config['email/customer/sender_name'] ?: $config['email/default/sender_name']];
                        $subject = $this->translate('Contact Us');
                        $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
                        $mailer->send($recipients, $subject, $content, [], '', '', $attachments, true, '', $from);
                    } catch (EmailException $e) {
                        $this->getContainer()->get('log')->logException($e);
                    }
                }
            } catch (EmailException $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please try again later.'), 'level' => 'danger'];
            } catch (Exception $e) {
                $this->getContainer()->get('log')->logException($e);
                $result['error'] = 1;
                $result['message'][] = ['message' => $this->translate('An error detected. Please try again later.'), 'level' => 'danger'];
            }
        }
        return $this->response($result, $this->getRequest()->getHeader('HTTP_REFERER')['HTTP_REFERER'], 'customer');
    }
}
