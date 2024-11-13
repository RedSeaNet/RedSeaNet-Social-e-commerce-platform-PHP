<?php

namespace Redseanet\Lib\Exception;

use Exception;

/**
 * Missing file exception
 */
class MissingFileException extends Exception
{
    /**
     * @param string $filename      The missing file's name
     * @param int $code
     * @param Exception $previous
     */
    public function __construct($filename = '', $code = 0, Exception $previous = null)
    {
        parent::__construct('File not found: ' . $filename, $code, $previous);
    }
}
