<?php

/**
 * Laminas Framework (http://framework.Laminas.com/)
 *
 * @link      http://github.com/Laminasframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Laminas Technologies USA Inc. (http://www.Laminas.com)
 * @license   http://framework.Laminas.com/license/new-bsd New BSD License
 */

namespace Redseanet\Api\Exception\Soap;

use BadMethodCallException as SPLBadMethodCallException;

/**
 * Exception thrown when unrecognized method is called via overloading
 */
class BadMethodCallException extends SPLBadMethodCallException implements ExceptionInterface
{
}
