<?php

namespace Redseanet\Lib\Http;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Abstract message (base class for Request and Response)
 * Defined in the PSR-7 MessageInterface.
 */
abstract class Message implements MessageInterface
{
    protected $version = '1.1';

    /**
     * @var Headers
     */
    protected $headers;

    /**
     * @var StreamInterface
     */
    protected $body;

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function getHeader(string $name): array
    {
        $header = $this->headers->offsetGet($name);
        if (empty($header)) {
            return [];
        } else {
            return [$name => $header];
        }
    }

    public function getHeaderLine(string $name): string
    {
        return $this->headers->offsetExists($name) ? ($name . ': ' . $this->headers->offsetGet($name) . '\r\n') : '';
    }

    public function getHeaders(): array
    {
        if (empty($this->headers)) {
            return [];
        } else {
            return $this->headers->headers;
        }
    }

    public function getProtocolVersion(): string
    {
        return $this->version;
    }

    public function hasHeader(string $name): bool
    {
        return $this->headers->offsetExists($name);
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        if (!$this->hasHeader($name)) {
            $this->withHeader($name, $value);
        }
        return $this;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        $this->body = $body;
        return $this;
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $this->headers->offsetSet($name, $value);
        return $this;
    }

    public function withHeaders(Headers $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        if (in_array($version, ['1.0', '1.1', '2.0'])) {
            $this->version = $version;
        } else {
            throw new InvalidArgumentException('Invalid HTTP version. Must be one of: 1.0, 1.1, 2.0');
        }
        return $this;
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $this->headers->offsetUnset($name);
        return $this;
    }

    /**
     * @abstract
     * @return string
     */
    abstract public function renderStatusLine();

    /**
     * @return string
     */
    public function __toString()
    {
        $str = $this->renderStatusLine() . "\r\n";
        $str .= $this->getHeaders()->__toString();
        $str .= "\r\n";
        $str .= $this->getBody();
        return $str;
    }
}
