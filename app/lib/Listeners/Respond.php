<?php

namespace Redseanet\Lib\Listeners;

use Traversable;

/**
 * Listen respond event
 */
class Respond implements ListenerInterface
{
    use \Redseanet\Lib\Traits\Container;

    public function respond($event)
    {
        $response = $this->getContainer()->get('response');
        $isHead = $this->getContainer()->get('request')->isHead() || $response->getStatusCode() >= 300 && $response->getStatusCode() < 400;
        $body = $response->getBody();
        $chunkSize = 4096;
        $contentLength = !empty($response->getHeader('Content-Length')['Content-Length']) ? $response->getHeader('Content-Length')['Content-Length'] : '';
        if (empty($contentLength)) {
            $contentLength = $body->getSize();
        }
        if (!$isHead && $body->isSeekable()) {
            if ($response->getStatusCode() === 206 && $range = $this->getContainer()->get('request')->getHeader('RANGE')['RANGE']) {
                try {
                    $response->withHeader('Accept-Ranges', 'bytes');
                    preg_match('/^bytes\=(?P<start>\d*)\-(?P<end>\d*)$/', $range, $matches);
                    $start = (int) $matches['start'];
                    $end = (int) $matches['end'];
                    if ($matches['start'] === '') {
                        $body->seek(-$end, SEEK_END);
                    } else {
                        $body->seek((int) $matches['start']);
                    }
                    if ($end < $start) {
                        $end = (int) $contentLength;
                    }
                    $response->withHeader('Content-Range', 'bytes ' . $start . '-' . $end . '/' . $contentLength);
                    $contentLength = $end - $start;
                } catch (\RuntimeException $e) {
                    $response->withStatus(416);
                }
            } else {
                $body->rewind();
            }
        }
        if (!headers_sent()) {
            header($response->renderStatusLine());
            if ($response->getProtocolVersion() >= 2) {
                header('status: ' . $response->getStatusCode());
            }
            foreach ($response->getHeaders() as $name => $values) {
                if (is_array($values) || $values instanceof Traversable) {
                    foreach ($values as $value) {
                        header(sprintf('%s: %s', $name, $value), false);
                    }
                } else {
                    header(sprintf('%s: %s', $name, $values), false);
                }
            }
            foreach ($response->getCookies()->toHeaders() as $value) {
                header(sprintf('Set-Cookie: %s', $value), false);
            }
        }
        if (!$isHead) {
            if (isset($contentLength)) {
                $totalChunks = ceil($contentLength / $chunkSize);
                $lastChunkSize = $contentLength % $chunkSize;
                $currentChunk = 0;
                while (!$body->eof() && $currentChunk < $totalChunks) {
                    if (++$currentChunk == $totalChunks && $lastChunkSize > 0) {
                        $chunkSize = $lastChunkSize;
                    }
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            } else {
                while (!$body->eof()) {
                    echo $body->read($chunkSize);
                    if (connection_status() != CONNECTION_NORMAL) {
                        break;
                    }
                }
            }
        }
    }
}
