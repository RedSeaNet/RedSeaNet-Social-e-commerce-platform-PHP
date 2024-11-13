<?php

namespace Redseanet\Lib\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use Redseanet\Lib\Exception\InvalidRequestMethod;

/**
 * HTTP request. It manages the request method, URI, headers, cookies, and body
 * according to the PSR-7 standard.
 */
class Request extends Message implements RequestInterface
{
    public const METHOD_OPTIONS = 'OPTIONS';
    public const METHOD_GET = 'GET';
    public const METHOD_HEAD = 'HEAD';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_CONNECT = 'CONNECT';
    public const METHOD_PATCH = 'PATCH';
    public const METHOD_PROPFIND = 'PROPFIND';

    /**
     * @var string
     */
    protected $method = self::METHOD_GET;

    /**
     * @var Uri
     */
    protected $uri = null;

    /**
     * @var UploadedFile
     */
    protected $uploadedFile = null;

    /**
     * @var array
     */
    protected $queryParams = null;

    /**
     * @var array
     */
    protected $post = null;

    /**
     * @var array
     */
    protected $cookies = null;

    /**
     * @param array $server
     */
    public function __construct($server = [])
    {
        if (empty($server)) {
            $server = $_SERVER;
        }
        $method = $server['REQUEST_METHOD'];
        $uri = Uri::createFromEnvironment($server);
        $headers = Headers::createFromEnvironment($server);
        $cookies = Cookies::parseHeader($headers->offsetGet('HTTP_COOKIE'));
        $body = new RequestBody();
        $uploadedFiles = UploadedFile::createFromEnvironment();
        $this->withProtocolVersion(substr($server['SERVER_PROTOCOL'], -3))
                ->withMethod($method)
                ->withHeaders($headers)
                ->withCookies($cookies)
                ->withUri($uri)
                ->withBody($body)
                ->withUploadedFile($uploadedFiles);
    }

    /**
     * @return array
     */
    public function getQuery($key = null, $default = '')
    {
        if (!$this->queryParams) {
            if ($this->uri === null) {
                return [];
            }
            parse_str($this->uri->getQuery(), $this->queryParams);
        }
        return is_null($key) ? $this->queryParams : ($this->queryParams[$key] ?? $default);
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return array
     * @throws \RuntimeException
     */
    public function getPost($key = null, $default = '')
    {
        if (!$this->post) {
            if (!$this->body) {
                return null;
            }
            if ($_POST) {
                $this->post = $_POST;
            } else {
                $body = (string) $this->getBody();
                $type = $this->getMediaType();
                if ($type === 'application/json') {
                    $parsed = json_decode($body, true);
                } elseif ($type === 'application/xml' || $type === 'text/xml') {
                    $backup = libxml_disable_entity_loader(true);
                    $parsed = simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA);
                    libxml_disable_entity_loader($backup);
                } elseif ($type === 'application/x-www-form-urlencoded' || $type === 'text/plain') {
                    parse_str($body, $parsed);
                } else {
                    $parsed = [];
                }
                if (!is_null($parsed) && !is_object($parsed) && !is_array($parsed)) {
                    $parsed = [];
                }
                $this->post = $parsed;
            }
        }
        return is_null($key) ? $this->post : ($this->post[$key] ?? $default);
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getRequestTarget(): string
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }

        if ($this->uri === null) {
            return '/';
        }

        $basePath = $this->uri->getBasePath();
        $path = $this->uri->getPath();
        $path = $basePath . '/' . ltrim($path, '/');

        $query = $this->uri->getQuery();
        if ($query) {
            $path .= '?' . $query;
        }
        $this->requestTarget = $path;

        return $this->requestTarget;
    }

    /**
     * @return Uri
     */
    public function getUri(): UriInterface
    {
        if ($this->uri === null || is_string($this->uri)) {
            $this->uri = Uri::createFromString($this->uri);
        }
        return $this->uri;
    }

    /**
     * @return UploadedFile
     */
    public function getUploadedFile()
    {
        return $this->uploadedFile;
    }

    /**
     * @return Cookies
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    public function getCookie($key, $default = '')
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * @param string $method
     * @return Request
     * @throws InvalidRequestMethod
     */
    public function withMethod(string $method): RequestInterface
    {
        $method = strtoupper($method);
        if (!defined('static::METHOD_' . $method)) {
            throw new InvalidRequestMethod('Invalid HTTP method passed');
        }
        $this->method = $method;
        return $this;
    }

    /**
     * @param string $requestTarget
     * @return Request
     * @throws \InvalidArgumentException
     */
    public function withRequestTarget(string $requestTarget): RequestInterface
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new \InvalidArgumentException(
                'Invalid request target provided; must be a string and cannot contain whitespace'
            );
        }
        $this->requestTarget = $requestTarget;

        return $this;
    }

    /**
     * @param UriInterface $uri
     * @param boolean $preserveHost
     * @return Request
     */
    public function withUri(UriInterface $uri, bool $preserveHost = false): RequestInterface
    {
        $this->uri = $uri;
        if (!$preserveHost) {
            if ($uri->getHost() !== '') {
                $this->headers->offsetSet('Host', $uri->getHost());
            }
        } else {
            if ($this->uri->getHost() !== '' && (!$this->hasHeader('Host') || $this->getHeader('Host')['Host'] === null)) {
                $this->headers->offsetSet('Host', $uri->getHost());
            }
        }
        return $this;
    }

    /**
     * @param UploadedFile $uploadedFile
     * @return Request
     */
    public function withUploadedFile($uploadedFile)
    {
        $this->uploadedFile = $uploadedFile;
        return $this;
    }

    /**
     * @param array $cookies
     * @return Request
     */
    public function withCookies($cookies)
    {
        $this->cookies = $cookies;
        return $this;
    }

    /**
     * Is this an OPTIONS method request?
     *
     * @return bool
     */
    public function isOptions()
    {
        return ($this->method === self::METHOD_OPTIONS);
    }

    /**
     * Is this a PROPFIND method request?
     *
     * @return bool
     */
    public function isPropFind()
    {
        return ($this->method === self::METHOD_PROPFIND);
    }

    /**
     * Is this a GET method request?
     *
     * @return bool
     */
    public function isGet()
    {
        return ($this->method === self::METHOD_GET);
    }

    /**
     * Is this a HEAD method request?
     *
     * @return bool
     */
    public function isHead()
    {
        return ($this->method === self::METHOD_HEAD);
    }

    /**
     * Is this a POST method request?
     *
     * @return bool
     */
    public function isPost()
    {
        return ($this->method === self::METHOD_POST);
    }

    /**
     * Is this a PUT method request?
     *
     * @return bool
     */
    public function isPut()
    {
        return ($this->method === self::METHOD_PUT);
    }

    /**
     * Is this a DELETE method request?
     *
     * @return bool
     */
    public function isDelete()
    {
        return ($this->method === self::METHOD_DELETE);
    }

    /**
     * Is this a TRACE method request?
     *
     * @return bool
     */
    public function isTrace()
    {
        return ($this->method === self::METHOD_TRACE);
    }

    /**
     * Is this a CONNECT method request?
     *
     * @return bool
     */
    public function isConnect()
    {
        return ($this->method === self::METHOD_CONNECT);
    }

    /**
     * Is this a PATCH method request?
     *
     * @return bool
     */
    public function isPatch()
    {
        return ($this->method === self::METHOD_PATCH);
    }

    /**
     * Is the request a Javascript XMLHttpRequest?
     *
     * Should work with Prototype/Script.aculo.us, possibly others.
     *
     * @return bool
     */
    public function isXmlHttpRequest()
    {
        $header = (!empty($this->getHeader('X_REQUESTED_WITH')['X_REQUESTED_WITH']) ? $this->getHeader('X_REQUESTED_WITH')['X_REQUESTED_WITH'] : '');
        return $header === 'XMLHttpRequest';
    }

    /**
     * Is this a Flash request?
     *
     * @return bool
     */
    public function isFlashRequest()
    {
        $header = $this->getHeader('USER_AGENT')['USER_AGENT'];
        return stristr($header, ' flash');
    }

    /**
     * Get HTTP status head
     *
     * @return string
     */
    public function renderStatusLine()
    {
        return $this->method . ' ' . (string) $this->uri . ' HTTP/' . $this->version;
    }

    /**
     * Get content type
     *
     * @return string
     */
    protected function getMediaType()
    {
        $contentType = !empty($this->getHeader('CONTENT_TYPE')['CONTENT_TYPE']) ? $this->getHeader('CONTENT_TYPE')['CONTENT_TYPE'] : '';
        if ($contentType) {
            $contentTypeParts = preg_split('/\s*[;,]\s*/', $contentType);
            return strtolower($contentTypeParts[0]);
        }
        return null;
    }
}
