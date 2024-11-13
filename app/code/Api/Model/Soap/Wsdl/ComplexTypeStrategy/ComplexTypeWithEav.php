<?php

namespace Redseanet\Api\Model\Soap\Wsdl\ComplexTypeStrategy;

use Redseanet\Api\Exception\Soap as Exception;
use Redseanet\Api\Model\Soap\Wsdl;
use Redseanet\Lib\Model\Collection\Eav\Attribute;
use Redseanet\Lib\Model\Eav\Type;

class ComplexTypeWithEav extends DefaultComplexType
{
    /**
     * {@inheritDoc}
     */
    public function addComplexType($type)
    {
        $isArray = false;
        if (substr($type, -2) === '[]') {
            $type = substr($type, 0, -2);
            $isArray = true;
            $complex = true;
        }
        if (($soapType = $this->scanRegisteredTypes($type)) !== null) {
            $result = $soapType;
            $isArray = false;
        } elseif (class_exists($type)) {
            $result = parent::addComplexType($type);
        } elseif (in_array($type, ['string', 'str', 'long', 'int', 'integer', 'float', 'double', 'boolean', 'bool'])) {
            $result = $type;
            $complex = false;
        } else {
            $result = $this->addEavType($type);
        }
        if ($isArray) {
            $result = $this->addArrayType($result, $complex);
        }
        return $result;
    }

    protected function addArrayType($type, $complex = true)
    {
        $tns = Wsdl::TYPES_NS . ':';
        $dom = $this->getContext()->toDomDocument();
        $elementType = str_replace($tns, '', $type);
        $soapTypeName = 'ArrayOf' . ucfirst($elementType);
        $soapType = $tns . $soapTypeName;
        $this->getContext()->addType($type, $soapType);
        $complexType = $dom->createElementNS(Wsdl::XSD_NS_URI, 'complexType');
        $complexType->setAttribute('name', $soapTypeName);
        $sequence = $dom->createElementNS(Wsdl::XSD_NS_URI, 'sequence');
        $element = $dom->createElementNS(Wsdl::XSD_NS_URI, 'element');
        $element->setAttribute('name', $elementType);
        $element->setAttribute('type', ($complex ? $tns : '') . $elementType);
        $element->setAttribute('nillable', 'true');
        $element->setAttribute('minOccurs', 0);
        $element->setAttribute('maxOccurs', 'unbounded');
        $sequence->appendChild($element);
        $complexType->appendChild($sequence);
        $this->getContext()->getSchema()->appendChild($complexType);
        return $soapType;
    }

    protected function addEavType($type)
    {
        $entityType = new Type();
        $entityType->load($type, 'code');
        if (!$entityType->getId()) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Cannot add a complex type %s that is not an object or where '
                    . 'class could not be found in "ComplexTypeWithEav" strategy.',
                $type
            ));
        }
        $dom = $this->getContext()->toDomDocument();
        $soapTypeName = $this->getContext()->translateType($type);
        $soapType = Wsdl::TYPES_NS . ':' . $soapTypeName;
        $this->getContext()->addType($type, $soapType);
        $complexType = $dom->createElementNS(Wsdl::XSD_NS_URI, 'complexType');
        $complexType->setAttribute('name', $soapTypeName);
        $all = $dom->createElementNS(Wsdl::XSD_NS_URI, 'all');
        $attributes = new Attribute();
        $attributes->columns(['code', 'type', 'is_required'])
                ->where([
                    'type_id' => $entityType->getId()
                ])->where->notEqualTo('input', 'password');
        $attributes->load(true, true);
        $element = $dom->createElementNS(Wsdl::XSD_NS_URI, 'element');
        $element->setAttribute('name', 'id');
        $element->setAttribute('type', 'int');
        $element->setAttribute('nillable', 'true');
        $all->appendChild($element);
        foreach ($attributes as $attribute) {
            $element = $dom->createElementNS(Wsdl::XSD_NS_URI, 'element');
            $element->setAttribute('name', $attribute['code']);
            $element->setAttribute('type', $this->transformType($attribute['type']));
            if (!$attribute['is_required']) {
                $element->setAttribute('nillable', 'true');
            }
            $all->appendChild($element);
        }
        $complexType->appendChild($all);
        $this->getContext()->getSchema()->appendChild($complexType);
        return $soapType;
    }

    protected function transformType($type)
    {
        $context = $this->getContext();
        switch ($type) {
            case 'text':
            case 'varchar':
            case 'datetime':
                return $context->getType('string');
            case 'decimal':
                return $context->getType('float');
            case 'int':
                return $context->getType('int');
            default:
                return $context->getType($type);
        }
    }
}
