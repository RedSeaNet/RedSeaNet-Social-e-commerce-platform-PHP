<?php

namespace Redseanet\Lib\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * HTTP response. It manages the response status, headers, and body
 * according to the PSR-7 standard.
 */
class Response extends Message implements ResponseInterface
{
    /**
     * @var array Recommended Reason Phrases
     */
    protected $recommendedReasonPhrases = [
        // INFORMATIONAL CODES
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // SUCCESS CODES
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Infomation',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        // REDIRECTION CODES
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy', // Deprecated
        307 => 'Temporary Redirect',
        // CLIENT ERROR
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        // SERVER ERROR
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    /**
     * @var int Status code
     */
    protected $statusCode = 200;

    /**
     * @var string|null
     */
    protected $reasonPhrase = '';

    /**
     * @var array|string|\Redseanet\Lib\ViewModel\AbstractViewModel
     */
    protected $data = null;

    /**
     * @var Cookies
     */
    protected $cookies = null;

    public function __construct()
    {
        $this->headers = new Headers();
        $this->cookies = new Cookies();
        $this->body = new Body(fopen('php://temp', 'r+'));
    }

    /**
     * Get reason phrase based on status code
     *
     * @return string
     */
    public function getReasonPhrase(): string
    {
        if (!$this->reasonPhrase && isset($this->recommendedReasonPhrases[$this->statusCode])) {
            $this->reasonPhrase = $this->recommendedReasonPhrases[$this->statusCode];
        }
        return $this->reasonPhrase;
    }

    /**
     * Get HTTP status code
     *
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set status code
     *
     * @param int $code
     * @param string $reasonPhrase
     * @return \Redseanet\Lib\Http\Response
     * @throws \InvalidArgumentException
     */
    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        if (!isset($this->recommendedReasonPhrases[$code])) {
            throw new \InvalidArgumentException('Invalid status code provided: ' . $code);
        }
        $this->statusCode = (int) $code;
        $this->reasonPhrase = $reasonPhrase;
        return $this;
    }

    /**
     * Get HTTP status head
     *
     * @return string
     */
    public function renderStatusLine()
    {
        $status = sprintf(
            'HTTP/%s %d %s',
            $this->getProtocolVersion(),
            $this->getStatusCode(),
            $this->getReasonPhrase()
        );
        return trim($status);
    }

    /**
     * Set response body
     *
     * @param \Psr\Http\Message\StreamInterface $body
     * @return Response
     */
    public function withBody(\Psr\Http\Message\StreamInterface $body): \Psr\Http\Message\MessageInterface
    {
        if (is_resource($this->body)) {
            fclose($this->body);
        }
        return parent::withBody($body);
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Set cookie
     *
     * @param string $name
     * @param array|string $value
     * @return Response
     */
    public function withCookie($name, $value)
    {
        $this->cookies->set($name, $value);
        return $this;
    }

    /**
     * Get cookies
     *
     * @return Cookies
     */
    public function getCookies()
    {
        return $this->cookies;
    }
}
