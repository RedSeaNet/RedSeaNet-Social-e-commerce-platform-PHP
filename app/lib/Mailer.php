<?php

namespace Redseanet\Lib;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    use \Redseanet\Lib\Traits\Container;

    public $mailer = null;

    /**
     * Allowed transportation class
     *
     * @var array
     */
    public static $ALLOWED_TRANSPORTATION = [
        'SmtpTransport' => 'SMTP',
        'SendmailTransport' => 'Sendmail',
        'MailTransport' => 'Mail'
    ];

    /**
     * SMTP configuration
     *
     * @var array
     */
    protected $SMTPParams = [
        'host' => 'smtp.mxhichina.com',
        'port' => 465,
        'security' => null
    ];

    /**
     * Sendmail configuration
     *
     * @var array
     */
    protected $SendmailParams = [
        'command' => '/usr/sbin/sendmail -bs'
    ];

    /**
     * Mailer configuration
     *
     * @var array|Config
     */
    protected $config;

    /**
     * @param array|Container $container
     */
    public function __construct($container = null)
    {
        if ($container instanceof Container) {
            $this->setContainer($container);
            $this->config = $this->getContainer()->get('config');
        } else {
            $this->config = $container;
        }
        $this->mailer = new PHPMailer(true);
        if (static::$ALLOWED_TRANSPORTATION[$this->config['email/transport/service']] === 'SMTP') {
            $encyption = $this->config['email/transport/security'];
            $host = $this->config['email/transport/host'];
            if ($encyption) {
                $host = $encyption . '://' . $host;
            }
            $port = $this->config['email/transport/port'];
            if (!$port) {
                $port = 25;
            }
            $this->mailer->Charset = 'utf-8';
            $this->mailer->Encoding = 'base64';
            //$this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
            $this->mailer->isSMTP();
            $this->mailer->Host = $host;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['email/transport/username'];
            $this->mailer->Password = $this->config['email/transport/password'];
            if (!empty($this->config['email/transport/auth']) && ($this->config['email/transport/auth'] == 'SSL' || $this->config['email/transport/auth'] == 'TLS')) {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            $this->mailer->Port = $port;
        } elseif (static::$ALLOWED_TRANSPORTATION[$this->config['email/transport/service']] === 'Sendmail') {
            $this->mailer->isSendmail();
            $this->mailer->Sendmail('/usr/sbin/sendmail -bs');
        }
    }

    private function changeFormatForMail($content)
    {
        return '=?UTF-8?B?' . base64_encode($content) . '?=';
    }

    public function send($recipients = [], $subject = '', $content = '', $replyTos = [], $cc = '', $bcc = '', $attachments = [], $isHtml = true, $altBody = '', $from = [])
    {
        if ($this->config['email/transport/enable']) {
            if (count($from) > 0) {
                $this->mailer->setFrom($from[0], $from[1]);
            } else {
                $this->mailer->setFrom($this->config['email/transport/username'], $this->config['email/transport/username']);
            }
            //Recipients
            for ($s = 0; $s < count($recipients); $s++) {
                $this->mailer->addAddress($recipients[$s][0], $recipients[$s][1]);
            }
            if (count($replyTos) > 0) {
                for ($s = 0; $s < count($replyTos); $s++) {
                    $this->mailer->addReplyTo($replyTos[$s][0], $replyTos[$s][1]);
                }
            }
            if (!empty($cc)) {
                $this->mailer->addCC($cc);
            }
            if (!empty($bcc)) {
                $this->mailer->addBCC($bcc);
            }
            //Attachments
            if (count($attachments) > 0) {
                for ($s = 0; $s < count($attachments); $s++) {
                    $this->mailer->addAttachment($attachments[$s][0], $attachments[$s][1]);
                }
            }
            //Content
            if ($isHtml) {
                $this->mailer->isHTML(true);
            }
            $this->mailer->Subject = $this->changeFormatForMail($subject);
            $this->mailer->Body = $this->changeFormatForMail($content);
            $this->mailer->AltBody = $altBody;
            $this->mailer->Body = $content;
            $this->mailer->send();
        } else {
            return ture;
        }
    }
}
