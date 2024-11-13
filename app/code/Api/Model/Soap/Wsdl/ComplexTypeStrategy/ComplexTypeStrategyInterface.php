<?php

/**
 * Laminas Framework (http://framework.Laminas.com/)
 *
 * @link      http://github.com/Laminasframework/zf2 for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Laminas Technologies USA Inc. (http://www.Laminas.com)
 * @license   http://framework.Laminas.com/license/new-bsd New BSD License
 */

namespace Redseanet\Api\Model\Soap\Wsdl\ComplexTypeStrategy;

use Redseanet\Api\Model\Soap\Wsdl;

/**
 * Interface strategies that generate an XSD-Schema for complex data types in WSDL files.
 */
interface ComplexTypeStrategyInterface
{
    /**
     * Method accepts the current WSDL context file.
     *
     * @param Wsdl $context
     */
    public function setContext(Wsdl $context);

    /**
     * Create a complex type based on a strategy
     *
     * @param  string $type
     * @return string XSD type
     */
    public function addComplexType($type);
}
