<?php

namespace Redseanet\Lib\Http;

/**
 * Provides a PSR-7 implementation of a reusable raw request body
 *
 * @see https://github.com/slimphp/Slim/blob/3.x/Slim/Http/RequestBody.php
 */
class RequestBody extends Body
{
    public function __construct()
    {
        $stream = fopen('php://temp', 'w+');
        stream_copy_to_stream(fopen('php://input', 'r'), $stream);
        rewind($stream);

        parent::__construct($stream);
    }
}
