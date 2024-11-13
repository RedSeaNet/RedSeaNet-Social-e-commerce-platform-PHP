<?php

namespace Redseanet\Email\Model\Api\Rpc;

use Exception;
use Redseanet\Api\Model\Api\Rpc\AbstractHandler;
use Redseanet\Customer\Model\Customer;
use Redseanet\Email\Model\Template as TemplateModel;
use Redseanet\Email\Model\Collection\Template as TemplateCollection;
use Redseanet\Lib\Bootstrap;
use PHPMailer\PHPMailer\Exception as EmailException;

class Email extends AbstractHandler
{
    /**
     * @param string $sessionId
     * @param int $customerId
     * @return array
     */
    public function sendEmailForText($id, $token, $customerId, $to, $subject = '', $content = '', $languageId = 0)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');
        try {
            $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
            if (!empty($fromEmail)) {
                $mailer = $this->getContainer()->get('mailer');
                try {
                    $recipients = [];
                    $recipients[] = [$to[0], $to[1]];
                    $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
                    $mailer->send($recipients, $subject, $content, [], '', '', [], true, '', $from);
                } catch (EmailException $e) {
                    $this->getContainer()->get('log')->logException($e);
                }
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'send email successfully'];
                return $this->responseData;
            } else {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Email setting incorrect, please contact adminstrator'];
                return $this->responseData;
            }
        } catch (EmailException $e) {
            $this->getContainer()->get('log')->logException($e);
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please try again later.' . $e];
            return $this->responseData;
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please try again later.' . $e];
            return $this->responseData;
        }
    }

    /**
     * @param string $sessionId
     * @param int $customerId
     * @return array
     */
    public function sendEmailUserTemplate($id, $token, $to, $template, $params = [], $languageId = 0)
    {
        $this->validateToken($id, $token, __FUNCTION__, false);
        if ($this->responseData['statusCode'] != '200') {
            return $this->responseData;
        }
        $config = $this->getContainer()->get('config');
        if ($languageId == 0) {
            $languageId = Bootstrap::getLanguage()->getId();
        }

        $collection = new TemplateCollection();
        $collection->join('email_template_language', 'email_template_language.template_id=email_template.id', [], 'left')
                ->where([
                    'code' => $template,
                    'language_id' => intval($languageId)
                ]);
        //echo $collection->getSqlString(Bootstrap::getContainer()->get("dbAdapter")->getPlatform());
        if (count($collection) == 0) {
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Email template do not exit'];
            return $this->responseData;
        }
        try {
            $fromEmail = $config['email/customer/sender_email'] ?: $config['email/default/sender_email'];
            if (!empty($fromEmail)) {
                $mailer = $this->getContainer()->get('mailer');
                $mailTemplate = new TemplateModel($collection[0]);
                $recipients = [];
                $recipients[] = [$to[0], $to[1]];
                $subject = $mailTemplate['subject'];
                $content = $mailTemplate->getContent($params);
                $from = [$fromEmail, $config['email/customer/sender_name'] ?: ($config['email/default/sender_name'] ?: null)];
                $mailer->send($recipients, $subject, $content, [], '', '', [], true, '', $from);
                $this->responseData = ['statusCode' => '200', 'data' => [], 'message' => 'send email successfully'];
                return $this->responseData;
            } else {
                $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'Email setting incorrect, please contact adminstrator'];
                return $this->responseData;
            }
        } catch (EmailException $e) {
            $this->getContainer()->get('log')->logException($e);
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please try again later.' . $e];
            return $this->responseData;
        } catch (Exception $e) {
            $this->getContainer()->get('log')->logException($e);
            $this->responseData = ['statusCode' => '403', 'data' => [], 'message' => 'An error detected. Please try again later.' . $e];
            return $this->responseData;
        }
    }
}
